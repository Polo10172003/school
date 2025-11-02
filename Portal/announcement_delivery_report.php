<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';

if (empty($_SESSION['admin_username'])) {
    header('Location: ' . (APP_BASE_PATH ?? '/') . 'admin_login.php');
    exit();
}

$logPath = __DIR__ . '/../temp/announcement_delivery.log';
$filterId = isset($_GET['announcement_id']) ? (int) $_GET['announcement_id'] : null;
$entries = [];

if (is_file($logPath) && is_readable($logPath)) {
    $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        $lines = array_reverse($lines);
        foreach ($lines as $line) {
            if (!preg_match('/^\\[(?<timestamp>[^\\]]+)\\]\\s+announcement_id=(?<id>\\d+)\\s+to=(?<email>\\S+)\\s+name=(?<name>.*)$/', $line, $matches)) {
                continue;
            }
            $announcementId = (int) $matches['id'];
            if ($filterId !== null && $filterId > 0 && $announcementId !== $filterId) {
                continue;
            }
            $entries[] = [
                'timestamp' => $matches['timestamp'],
                'id'        => $announcementId,
                'email'     => $matches['email'],
                'name'      => trim($matches['name']),
            ];
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Announcement Delivery Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #044a2d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #e3e3e3;
            text-align: left;
        }
        th {
            background-color: #e7f4ef;
            color: #044a2d;
        }
        tr:hover {
            background-color: #f0faf6;
        }
        .filters {
            margin-bottom: 20px;
        }
        .empty-state {
            margin-top: 20px;
            padding: 16px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            color: #856404;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 16px;
            color: #044a2d;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <a class="back-link" href="../admin_dashboard.php#announcements">&larr; Back to Admin Dashboard</a>
    <h1>Announcement Delivery Report</h1>

    <form class="filters" method="get">
        <label>
            Announcement ID:
            <input type="number" name="announcement_id" value="<?= htmlspecialchars((string) ($filterId ?? ''), ENT_QUOTES) ?>" placeholder="e.g. 42" min="1">
        </label>
        <button type="submit">Filter</button>
        <a href="<?= htmlspecialchars(basename(__FILE__), ENT_QUOTES) ?>">Clear</a>
    </form>

    <?php if (empty($entries)): ?>
        <div class="empty-state">
            <?= is_file($logPath)
                ? 'No delivery entries found for the selected criteria. Trigger an announcement to generate log entries.'
                : 'No delivery log found. An announcement must be sent before data is available.' ?>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Announcement ID</th>
                    <th>Recipient Email</th>
                    <th>Recipient Name</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['timestamp'], ENT_QUOTES) ?></td>
                        <td><?= (int) $entry['id'] ?></td>
                        <td><?= htmlspecialchars($entry['email'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($entry['name'], ENT_QUOTES) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
