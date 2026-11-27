<?php
require_once 'config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manual_order.php');
    exit;
}

$gid = (int)($_POST['group_id'] ?? 0);
$uid = (int)$_SESSION['user_id'];

$threshold = (float)($_POST['threshold'] ?? 0);
$price = (float)($_POST['price'] ?? 0);
$note = trim($_POST['note'] ?? '');

$hours = (int)($_POST['patience_hours'] ?? 0);
$minutes = (int)($_POST['patience_minutes'] ?? 0);
$totalMinutes = $hours * 60 + $minutes;

if (
    $gid <= 0 ||
    $threshold <= 0 ||
    $price <= 0 ||
    $hours < 0 ||
    $minutes < 0 ||
    $minutes > 59 ||
    $totalMinutes < 1 ||
    $totalMinutes > 10080
) {
    flash('Details sahi bharein. Patience time 1 minute se 7 din ke beech hona chahiye.');
    header('Location: manual_order.php');
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();

    // Only Manual groups can be joined through this page.
    $s = $pdo->prepare("
        SELECT *
        FROM app_groups
        WHERE id = ?
        AND source_mode = 'Manual'
        AND status = 'Open'
        FOR UPDATE
    ");
    $s->execute([$gid]);
    $group = $s->fetch();

    if (!$group) {
        throw new RuntimeException('Ye Manual group ab available nahi hai.');
    }

    // Prevent joining the same group twice.
    $s = $pdo->prepare("
        SELECT COUNT(*)
        FROM group_members
        WHERE group_id = ? AND user_id = ?
    ");
    $s->execute([$gid, $uid]);

    if ((int)$s->fetchColumn() > 0) {
        throw new RuntimeException('Aap pehle se is group ke member hain.');
    }

    $hostel = $group['hostel_name'];
    $app = $group['app_name'];

    // Add the joining student as a member.
    $s = $pdo->prepare("
        INSERT INTO group_members
        (group_id, user_id, threshold_value)
        VALUES (?, ?, ?)
    ");
    $s->execute([$gid, $uid, $threshold]);

    // Add student's purchase to this group.
    $s = $pdo->prepare("
        INSERT INTO orders
        (user_id, item_name, quantity, price, note,
         status, app_name, group_id)
        VALUES (?, '', 1, ?, ?, 'Active', ?, ?)
    ");
    $s->execute([$uid, $price, $note, $app, $gid]);

    $oid = (int)$pdo->lastInsertId();

    // Save this student's manual request with its own expiry time.
    $s = $pdo->prepare("
        INSERT INTO manual_requests
        (user_id, hostel_name, app_name, threshold_value,
         purchase_amount, note, patience_minutes, expires_at,
         status, group_id, order_id)
        VALUES (?, ?, ?, ?, ?, ?, ?,
                DATE_ADD(NOW(), INTERVAL ? MINUTE),
                'Pending', ?, ?)
    ");
    $s->execute([
        $uid, $hostel, $app, $threshold,
        $price, $note, $totalMinutes,
        $totalMinutes, $gid, $oid
    ]);

    // Recalculate group target using the lowest member threshold.
    $s = $pdo->prepare("
        SELECT MIN(threshold_value)
        FROM group_members
        WHERE group_id = ?
    ");
    $s->execute([$gid]);
    $target = (float)$s->fetchColumn();

    // Calculate combined purchase amount.
    $s = $pdo->prepare("
        SELECT COALESCE(SUM(price), 0)
        FROM orders
        WHERE group_id = ?
        AND status = 'Active'
    ");
    $s->execute([$gid]);
    $total = (float)$s->fetchColumn();

    $s = $pdo->prepare("
        UPDATE app_groups
        SET target_threshold = ?
        WHERE id = ?
    ");
    $s->execute([$target, $gid]);

    $completed = false;

    if ($total >= $target) {
        $s = $pdo->prepare("
            UPDATE app_groups
            SET status = 'Completed', completed_at = NOW()
            WHERE id = ?
            AND status = 'Open'
        ");
        $s->execute([$gid]);

        if ($s->rowCount()) {
            $completed = true;

            // Completed requests should no longer expire.
            $s = $pdo->prepare("
                UPDATE manual_requests
                SET status = 'Grouped'
                WHERE group_id = ?
                AND status = 'Pending'
            ");
            $s->execute([$gid]);

            // Notify every group member.
            $s = $pdo->prepare("
                SELECT user_id
                FROM group_members
                WHERE group_id = ?
            ");
            $s->execute([$gid]);

            foreach ($s->fetchAll(PDO::FETCH_COLUMN) as $memberId) {
                notify_user(
                    (int)$memberId,
                    "Group-{$gid} complete",
                    "{$hostel} · {$app}: combined purchase ₹" .
                    number_format($total, 2) .
                    " reached target ₹" .
                    number_format($target, 2) . ".",
                    $gid
                );
            }
        }
    }

    $pdo->commit();

    if ($completed) {
        flash("Group-{$gid} complete! Sabhi members ko notification bhej di gayi.");
    } else {
        flash(
            "Aap Group-{$gid} mein successfully join ho gaye. Combined amount ₹" .
            number_format($total, 2) . " / ₹" .
            number_format($target, 2)
        );
    }

    header('Location: group.php?id=' . $gid);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash($e->getMessage());
    header('Location: manual_order.php');
    exit;
}