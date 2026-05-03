<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}
require_once '../backend/dbconn.php';
require_once '../backend/fifo_stock_helper.php';

$uid = (int) $_SESSION['admin_id'];
$hasExtra = fifo_notifications_has_link_columns($conn);
$cols = $hasExtra
    ? 'id, message, order_id, is_read, created_at,
       COALESCE(notification_type, \'order\') AS notification_type,
       link_url, COALESCE(related_ingredient_id, 0) AS related_ingredient_id'
    : 'id, message, order_id, is_read, created_at';

$stmt = $conn->prepare("SELECT {$cols} FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 200");
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}
$stmt->close();

function notif_href(array $r, bool $hasExtra): string {
    if ($hasExtra && !empty($r['link_url'])) {
        return (string) $r['link_url'];
    }
    $oid = (int) ($r['order_id'] ?? 0);
    if ($oid > 0) {
        return 'orders.php';
    }
    return 'inventory.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn · Notifications</title>
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="js/active-page.js"></script>
    <style>
        body { font-family:'Poppins',sans-serif; background:radial-gradient(circle at top,rgba(212,175,55,.12),transparent 28%),linear-gradient(180deg,#050505 0%,#0a0a0a 45%,#111827 100%); color:#fff; min-height:100vh; }
        .main-content { margin-left:260px; padding:24px; transition:margin-left .3s ease; }
        .main-content.expanded { margin-left:0; }
        .glass { background:rgba(17,24,39,.78); border:1px solid rgba(212,175,55,.18); border-radius:24px; box-shadow:0 20px 60px rgba(0,0,0,.35); backdrop-filter:blur(18px); }
        @media (max-width:1024px) { .main-content { margin-left:0; padding:16px; } }
    </style>
</head>
<body>
<?php include '../navigation/admin-navbar.php'; ?>
<?php include '../navigation/admin-sidebar.php'; ?>

<main class="main-content">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="glass p-6">
            <h1 class="text-2xl font-bold text-yellow-200">All notifications</h1>
            <p class="mt-1 text-sm text-gray-400">Low-stock alerts open Inventory; order messages open Orders.</p>
        </div>
        <div class="glass overflow-hidden">
            <?php if (!$rows): ?>
                <p class="p-10 text-center text-gray-500 text-sm">No notifications yet.</p>
            <?php else: ?>
                <ul class="divide-y divide-white/5">
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $href = htmlspecialchars(notif_href($r, $hasExtra), ENT_QUOTES, 'UTF-8');
                        $msg = htmlspecialchars((string) $r['message'], ENT_QUOTES, 'UTF-8');
                        $when = htmlspecialchars((string) ($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $unread = (int) ($r['is_read'] ?? 0) === 0;
                        $type = $hasExtra ? (string) ($r['notification_type'] ?? 'order') : 'order';
                        ?>
                        <li>
                            <a href="<?= $href ?>"
                               class="flex flex-col gap-1 px-5 py-4 hover:bg-yellow-500/5 transition <?= $unread ? 'border-l-2 border-yellow-400/80' : '' ?>">
                                <span class="text-xs uppercase tracking-wider text-yellow-500/70"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-sm text-gray-200"><?= $msg ?></span>
                                <span class="text-xs text-gray-500"><?= $when ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>lucide.createIcons();</script>
</body>
</html>
