
<?php
declare(strict_types=1);
session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'hostel_ordering';
const DB_USER = 'root';
const DB_PASS = 'YOUR_DATABASE_PASSWORD';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    return $pdo;
}

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function is_admin(): bool {
    return !empty($_SESSION['is_admin']);
}

function require_login(): void {
    if (!logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Admin access required.');
    }
}

function flash(string $m): void {
    $_SESSION['flash'] = $m;
}

function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        echo '<div class="notice">'.e($_SESSION['flash']).'</div>';
        unset($_SESSION['flash']);
    }
}

function notify_user(
    int $userId,
    string $title,
    string $message,
    int $groupId
): void {
    $s = db()->prepare(
        'INSERT INTO notifications(user_id,group_id,title,message) VALUES(?,?,?,?)'
    );
    $s->execute([$userId,$groupId,$title,$message]);
}

function expire_manual_requests(): void {
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $s = $pdo->query("SELECT * FROM manual_requests WHERE status='Pending' AND expires_at<=NOW() FOR UPDATE");
        $expired = $s->fetchAll();

        foreach ($expired as $r) {
            if (!empty($r['order_id'])) {
                $d = $pdo->prepare('DELETE FROM orders WHERE id=?');
                $d->execute([$r['order_id']]);
            }

            $gid = (int)($r['group_id'] ?? 0);

            if ($gid) {
                $c = $pdo->prepare("SELECT COUNT(*) FROM manual_requests WHERE group_id=? AND status='Pending' AND expires_at>NOW() AND user_id=?");
                $c->execute([$gid, $r['user_id']]);

                if (!(int)$c->fetchColumn()) {
                    $d = $pdo->prepare('DELETE FROM group_members WHERE group_id=? AND user_id=?');
                    $d->execute([$gid, $r['user_id']]);
                }

                $m = $pdo->prepare('SELECT COUNT(*) FROM group_members WHERE group_id=?');
                $m->execute([$gid]);

                if (!(int)$m->fetchColumn()) {
                    $u = $pdo->prepare("UPDATE app_groups SET status='Failed' WHERE id=? AND status='Open'");
                    $u->execute([$gid]);
                } else {
                    $t = $pdo->prepare('SELECT MIN(threshold_value) FROM group_members WHERE group_id=?');
                    $t->execute([$gid]);
                    $target = (float)$t->fetchColumn();

                    $u = $pdo->prepare('UPDATE app_groups SET target_threshold=? WHERE id=?');
                    $u->execute([$target, $gid]);
                }
            }

            $u = $pdo->prepare("UPDATE manual_requests SET status='Failed' WHERE id=?");
            $u->execute([$r['id']]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
