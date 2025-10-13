<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../includes/registrar_guides.php';

$guideId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($guideId <= 0) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Invalid request</title></head><body><p>Invalid guide selected.</p></body></html>';
    exit;
}

try {
    registrar_guides_ensure_schema($conn);
    $guide = registrar_guides_find($conn, $guideId);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Error</title></head><body><p>Unable to load the selected guide.</p></body></html>';
    exit;
} finally {
    $conn->close();
}

if (!$guide) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Not found</title></head><body><p>The requested guide could not be located.</p></body></html>';
    exit;
}

$relativePath = registrar_guides_public_path($guide['file_name']);
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$absoluteUrl = $scheme . '://' . $host . '/' . ltrim($relativePath, '/');

$viewerUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($absoluteUrl);
$canEmbed = strncmp($absoluteUrl, 'https://', 8) === 0;

$pageTitle = 'Preview: ' . ($guide['original_name'] ?? 'Workbook');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <style>
    :root {
      color-scheme: light;
      font-family: "Inter", "Segoe UI", -apple-system, BlinkMacSystemFont, sans-serif;
      background: #f8fafc;
      color: #0f172a;
    }
    body {
      margin: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    header {
      padding: 20px 28px 12px;
      background: #ffffff;
      box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
      position: sticky;
      top: 0;
      z-index: 10;
    }
    h1 {
      margin: 0;
      font-size: 20px;
      font-weight: 700;
    }
    .subtitle {
      margin: 6px 0 0;
      color: #475569;
      font-size: 14px;
    }
    main {
      flex: 1 1 auto;
      padding: 16px;
    }
    iframe {
      width: 100%;
      height: 80vh;
      border: none;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.05);
    }
    .fallback {
      margin-top: 16px;
      font-size: 14px;
      color: #475569;
    }
    .fallback a {
      color: #145a32;
      font-weight: 600;
      text-decoration: none;
    }
    .fallback a:hover {
      text-decoration: underline;
    }
    .notice {
      padding: 16px 18px;
      background: rgba(20, 90, 50, 0.08);
      border-radius: 12px;
      font-size: 15px;
      line-height: 1.5;
    }
  </style>
</head>
<body>
  <header>
    <h1><?= htmlspecialchars($guide['original_name']) ?></h1>
    <p class="subtitle">Uploaded <?= htmlspecialchars(date('M j, Y g:i A', strtotime($guide['uploaded_at'] ?? 'now'))) ?></p>
  </header>
  <main>
    <?php if ($canEmbed): ?>
      <iframe src="<?= htmlspecialchars($viewerUrl) ?>" title="Workbook preview" loading="lazy" allowfullscreen></iframe>
      <p class="fallback">
        Trouble loading? <a href="<?= htmlspecialchars($absoluteUrl) ?>" target="_blank" rel="noopener">Open the workbook directly</a>.
      </p>
    <?php else: ?>
      <div class="notice">
        Inline preview requires the site to use HTTPS so that the viewer can access the workbook. You can still
        <a href="<?= htmlspecialchars($absoluteUrl) ?>" target="_blank" rel="noopener">open the file in a new tab</a>.
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
