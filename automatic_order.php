
<?php
require_once 'config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hostel = $_POST['hostel_name'] ?? '';
    $app = $_POST['app_name'] ?? '';
    $threshold = (float)($_POST['threshold'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    $uid = (int)$_SESSION['user_id'];

    if (
        !in_array($hostel, ['Tagore', 'VS'], true) ||
        !in_array($app, ['Blinkit', 'Zepto'], true) ||
        $threshold <= 0 ||
        $price <= 0
    ) {
        flash('Saari details sahi bharein. Amount zero se zyada hona chahiye.');
    } else {

        $pdo = db();
        $pdo->beginTransaction();

        try {

            // Find any compatible open group (Automatic or Manual).
            $s = $pdo->prepare("
                SELECT * FROM app_groups
                WHERE hostel_name = ?
                AND app_name = ?
                AND status = 'Open'
                ORDER BY id DESC
                LIMIT 1
                FOR UPDATE
            ");
            $s->execute([$hostel, $app]);
            $g = $s->fetch();

            if (!$g) {
                $s = $pdo->prepare("
                    INSERT INTO app_groups
                    (hostel_name, app_name, source_mode, target_threshold, status)
                    VALUES (?, ?, 'Automatic', ?, 'Open')
                ");
                $s->execute([$hostel, $app, $threshold]);
                $gid = (int)$pdo->lastInsertId();
            } else {
                $gid = (int)$g['id'];
            }

            // Add automatic student.
            $s = $pdo->prepare("
                INSERT INTO group_members
                (group_id, user_id, threshold_value)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                threshold_value = LEAST(threshold_value, VALUES(threshold_value))
            ");
            $s->execute([$gid, $uid, $threshold]);

            // Add automatic order.
            $s = $pdo->prepare("
                INSERT INTO orders
                (user_id, item_name, quantity, price, note, status, app_name, group_id)
                VALUES (?, '', 1, ?, ?, 'Active', ?, ?)
            ");
            $s->execute([$uid, $price, $note, $app, $gid]);

            // Automatically collect compatible pending manual listings.
            $s = $pdo->prepare("
                SELECT *
                FROM manual_requests
                WHERE hostel_name = ?
                AND app_name = ?
                AND status = 'Pending'
                AND group_id IS NULL
                AND expires_at > NOW()
                ORDER BY id
                FOR UPDATE
            ");
            $s->execute([$hostel, $app]);
            $manualRequests = $s->fetchAll();

            foreach ($manualRequests as $r) {

                $s = $pdo->prepare("
                    INSERT INTO group_members
                    (group_id, user_id, threshold_value)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    threshold_value = LEAST(threshold_value, VALUES(threshold_value))
                ");
                $s->execute([
                    $gid,
                    $r['user_id'],
                    $r['threshold_value']
                ]);

                if (empty($r['order_id'])) {

                    $s = $pdo->prepare("
                        INSERT INTO orders
                        (user_id, item_name, quantity, price, note, status, app_name, group_id)
                        VALUES (?, '', 1, ?, ?, 'Active', ?, ?)
                    ");
                    $s->execute([
                        $r['user_id'],
                        $r['purchase_amount'],
                        $r['note'],
                        $r['app_name'],
                        $gid
                    ]);

                    $oid = (int)$pdo->lastInsertId();

                } else {
                    $oid = (int)$r['order_id'];
                }

                // Mark listing as assigned to a group.
                $s = $pdo->prepare("
                    UPDATE manual_requests
                    SET group_id = ?, order_id = ?, status = 'Grouped'
                    WHERE id = ?
                ");
                $s->execute([$gid, $oid, $r['id']]);
            }

            // Calculate common target.
            $s = $pdo->prepare("
                SELECT MIN(threshold_value)
                FROM group_members
                WHERE group_id = ?
            ");
            $s->execute([$gid]);
            $target = (float)$s->fetchColumn();

            // Calculate combined Automatic + Manual orders.
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

            // Complete group if target reached.
            $completed = false;

            if ($total >= $target) {

                $s = $pdo->prepare("
                    UPDATE app_groups
                    SET status = 'Completed', completed_at = NOW()
                    WHERE id = ? AND status = 'Open'
                ");
                $s->execute([$gid]);

                if ($s->rowCount()) {
                    $completed = true;

                    $s = $pdo->prepare("
                        SELECT user_id
                        FROM group_members
                        WHERE group_id = ?
                    ");
                    $s->execute([$gid]);

                    foreach ($s->fetchAll(PDO::FETCH_COLUMN) as $memberId) {
                        notify_user(
                            (int)$memberId,
                            'Group-' . $gid . ' complete',
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
                    "Order successfully add ho gaya. Group-{$gid}: ₹" .
                    number_format($total, 2) . " / ₹" .
                    number_format($target, 2)
                );
            }

            header('Location: group.php?id=' . $gid);
            exit;

        } catch (Throwable $e) {

            $pdo->rollBack();
            throw $e;
        }
    }
}

$pageTitle = 'Automatic Order';
require 'header.php';
?>

<div class="card narrow">
    <h1>Automatic Order</h1>

    <form method="post">

        <label>Hostel</label>
        <select name="hostel_name" required>
            <option value="">Select hostel</option>
            <option>Tagore</option>
            <option>VS</option>
        </select>

        <label>Shopping App Name</label>
        <select name="app_name" required>
            <option value="">Select app</option>
            <option>Blinkit</option>
            <option>Zepto</option>
        </select>

        <label>Your threshold amount (₹)</label>
        <input type="number" name="threshold" min="1" step="0.01" required>

        <label>Total purchase amount (₹)</label>
        <input type="number" name="price" min="0.01" step="0.01" required>

        <label>Note (optional) (Contact detail for group member)</label>
        <textarea name="note"></textarea>

        <button>Submit Order</button>

    </form>
</div>

<?php require 'footer.php'; ?>
