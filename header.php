<?php
require_once __DIR__.'/config.php';
$pageTitle = $pageTitle ?? 'Hostel Group Orders';
$unread = 0; $popupNotifications = [];
if (logged_in()) {
    try { expire_manual_requests(); } catch (Throwable $ignore) {}
    $ps = db()->prepare('SELECT * FROM notifications WHERE user_id=? AND popup_seen_at IS NULL ORDER BY created_at');
    $ps->execute([$_SESSION['user_id']]); $popupNotifications = $ps->fetchAll();
    if ($popupNotifications) { $ids = array_column($popupNotifications, 'id'); $marks = implode(',', array_fill(0, count($ids), '?')); $up = db()->prepare("UPDATE notifications SET popup_seen_at=NOW() WHERE id IN ($marks) AND user_id=?"); $up->execute(array_merge($ids, [$_SESSION['user_id']])); }

    $s = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $s->execute([$_SESSION['user_id']]);
    $unread = (int)$s->fetchColumn();
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($pageTitle)?></title><link rel="stylesheet" href="style.css"></head><body>
<header class="top"><a class="brand" href="index.php">Hostel Group Orders</a><nav>
<?php if(logged_in()): ?>
<a href="index.php">Dashboard</a><a href="new_order.php">Place Order</a><a href="groups.php">Group History</a><a href="notifications.php">Notifications (<?= $unread ?>)</a><a href="profile.php">Profile</a>
<?php if(is_admin()): ?><a href="admin.php">Admin</a><?php endif; ?>
<a href="logout.php">Logout</a>
<?php else: ?><a href="login.php">Login</a><a href="register.php">Register</a><?php endif; ?>
</nav></header><main class="container"><?php show_flash(); ?>
<?php if ($popupNotifications): ?><div class="popup-backdrop" id="newGroupPopup"><div class="popup-card"><h2>New notification</h2><?php foreach($popupNotifications as $pn): ?><p><b><?=e($pn['title'])?></b><br><?=e($pn['message'])?></p><?php if($pn['group_id']): ?><a href="group.php?id=<?=(int)$pn['group_id']?>">View Group-<?= (int)$pn['group_id'] ?></a><?php endif; ?><?php endforeach; ?><button type="button" onclick="document.getElementById('newGroupPopup').remove()">OK, understood</button></div></div><?php endif; ?>
