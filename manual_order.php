<?php
require_once 'config.php';
require_login();
expire_manual_requests();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hostel = $_POST['hostel_name'] ?? '';
    $app = $_POST['app_name'] ?? '';
    $threshold = (float)($_POST['threshold'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    $hours = (int)($_POST['patience_hours'] ?? 0);
    $minutes = (int)($_POST['patience_minutes'] ?? 0);
    $totalMinutes = $hours * 60 + $minutes;

    if (
        !in_array($hostel, ['Tagore', 'VS'], true) ||
        !in_array($app, ['Blinkit', 'Zepto'], true) ||
        $threshold <= 0 ||
        $price <= 0 ||
        $totalMinutes < 1 ||
        $totalMinutes > 10080 ||
        $hours < 0 ||
        $minutes < 0 ||
        $minutes > 59
    ) {
        flash('Details sahi bharein. Patience time 1 minute se 7 din ke beech hona chahiye.');
        header('Location: manual_order.php');
        exit;
    }

    $uid = (int)$_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // Create a new Manual group. Do not auto-join an existing group.
        $s = $pdo->prepare("
            INSERT INTO app_groups
            (hostel_name, app_name, source_mode, target_threshold, status)
            VALUES (?, ?, 'Manual', ?, 'Open')
        ");
        $s->execute([$hostel, $app, $threshold]);

        $gid = (int)$pdo->lastInsertId();

        // Add listing owner as the first group member.
        $s = $pdo->prepare("
            INSERT INTO group_members
            (group_id, user_id, threshold_value)
            VALUES (?, ?, ?)
        ");
        $s->execute([$gid, $uid, $threshold]);

        // Add owner's purchase.
        $s = $pdo->prepare("
            INSERT INTO orders
            (user_id, item_name, quantity, price, note, status, app_name, group_id)
            VALUES (?, '', 1, ?, ?, 'Active', ?, ?)
        ");
        $s->execute([$uid, $price, $note, $app, $gid]);

        $oid = (int)$pdo->lastInsertId();

        // Keep the listing Pending until it expires or group completes.
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

        // Calculate combined amount.
        $s = $pdo->prepare("
            SELECT COALESCE(SUM(price), 0)
            FROM orders
            WHERE group_id = ? AND status = 'Active'
        ");
        $s->execute([$gid]);
        $total = (float)$s->fetchColumn();

        $completed = false;

        if ($total >= $threshold) {
            $s = $pdo->prepare("
                UPDATE app_groups
                SET status = 'Completed', completed_at = NOW()
                WHERE id = ? AND status = 'Open'
            ");
            $s->execute([$gid]);

            if ($s->rowCount()) {
                $completed = true;

                $pdo->prepare("
                    UPDATE manual_requests
                    SET status = 'Grouped'
                    WHERE group_id = ? AND status = 'Pending'
                ")->execute([$gid]);

                notify_user(
                    $uid,
                    "Group-{$gid} complete",
                    "{$hostel} · {$app}: combined purchase ₹" .
                    number_format($total, 2) .
                    " reached target ₹" . number_format($threshold, 2) . ".",
                    $gid
                );
            }
        }

        $pdo->commit();

        if ($completed) {
            flash("Group-{$gid} complete! Aapka order threshold tak pahunch gaya.");
            header('Location: group.php?id=' . $gid);
        } else {
            flash("Aapki listing Group-{$gid} mein add ho gayi. Ab doosre students is group ko join kar sakte hain.");
            header('Location: manual_order.php');
        }
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('Error: ' . $e->getMessage());
        header('Location: manual_order.php');
        exit;
    }
}

// Only open Manual groups are available to join.
$s = $pdo->query("
    SELECT g.*,
        (SELECT COUNT(*)
         FROM group_members gm
         WHERE gm.group_id = g.id) AS members,
        (SELECT COALESCE(SUM(o.price), 0)
         FROM orders o
         WHERE o.group_id = g.id
         AND o.status = 'Active') AS total
    FROM app_groups g
    WHERE g.source_mode = 'Manual'
    AND g.status = 'Open'
    ORDER BY g.id DESC
");
$openGroups = $s->fetchAll();

$pageTitle = 'Manual Order';
require 'header.php';
?>

<div class="card narrow">
    <h1>Manual Order Listing</h1>

    <p>
        Apni purchase details add karein. Aapka apna group banega
        aur aap uske first member honge. Doosre students baad mein
        aapke group ko join kar sakte hain.
    </p>

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

        <label>Patience time</label>
        <div class="two">
            <div>
                <small>Hours</small>
                <input type="number" name="patience_hours"
                       min="0" max="168" value="0" required>
            </div>
            <div>
                <small>Minutes</small>
                <input type="number" name="patience_minutes"
                       min="0" max="59" value="30" required>
            </div>
        </div>

        <label>Note (optional) – Contact detail for group members</label>
        <textarea name="note"></textarea>

        <button type="submit">Create My Group</button>
    </form>
</div>

<h2>Available Manual Groups</h2>

<?php foreach ($openGroups as $g): ?>
    <div class="card">
        <h3>
            Group-<?= (int)$g['id'] ?> ·
            <?= e($g['hostel_name']) ?> ·
            <?= e($g['app_name']) ?>
        </h3>

        <p>
            <?= (int)$g['members'] ?> students ·
            Combined purchase ₹<?= e(number_format((float)$g['total'], 2)) ?> ·
            Target ₹<?= e(number_format((float)$g['target_threshold'], 2)) ?>
        </p>

        <?php
        $gmq = $pdo->prepare("
            SELECT u.name, u.roll_no, gm.threshold_value,
                   COALESCE(SUM(o.price), 0) AS purchase
            FROM group_members gm
            JOIN users u ON u.id = gm.user_id
            LEFT JOIN orders o
                ON o.group_id = gm.group_id
                AND o.user_id = gm.user_id
                AND o.status = 'Active'
            WHERE gm.group_id = ?
            GROUP BY gm.user_id, gm.threshold_value, u.name, u.roll_no
        ");
        $gmq->execute([$g['id']]);
        $members = $gmq->fetchAll();



        $check = $pdo->prepare("
            SELECT COUNT(*) FROM group_members
            WHERE group_id = ? AND user_id = ?
        ");
        $check->execute([$g['id'], $_SESSION['user_id']]);
        $isMember = (bool)$check->fetchColumn();
        ?>

        <div class="tablewrap">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Roll No.</th>
                    <th>Threshold</th>
                    <th>Purchase</th>
                </tr>
                <?php foreach ($members as $m): ?>
                    <tr>
                        <td><?= e($m['name']) ?></td>
                        <td><?= e($m['roll_no']) ?></td>
                        <td>₹<?= e(number_format((float)$m['threshold_value'], 2)) ?></td>
                        <td>₹<?= e(number_format((float)$m['purchase'], 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <?php if (!$isMember): ?>
            <form method="post" action="manual_join.php">
                <input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>">

                <label>Your threshold amount (₹)</label>
                <input type="number" name="threshold" min="1" step="0.01" required>

                <label>Your purchase amount (₹)</label>
                <input type="number" name="price" min="0.01" step="0.01" required>

                <label>How long should your listing remain active?</label>
                <div class="two">
                    <div>
                        <small>Hours</small>
                        <input type="number" name="patience_hours"
                               min="0" max="168" value="0" required>
                    </div>
                    <div>
                        <small>Minutes</small>
                        <input type="number" name="patience_minutes"
                               min="0" max="59" value="30" required>
                    </div>
                </div>

                <label>Note (optional)</label>
                <textarea name="note"></textarea>

                <button type="submit">Join This Group</button>
            </form>
        <?php else: ?>
            <p>You are already a member of this group.</p>
        <?php endif; ?>

        <a class="btn" href="group.php?id=<?= (int)$g['id'] ?>">View Group</a>
    </div>
<?php endforeach; ?>

<?php if (!$openGroups): ?>
    <div class="card">
        Abhi koi open Manual group nahi hai. Aap apna group create kar sakte hain.
    </div>
<?php endif; ?>

<script>
setTimeout(() => location.reload(), 30000);
</script>

<?php require 'footer.php'; ?>