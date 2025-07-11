<?php
// upload_image.php

if ($_FILES['file']['error'] == UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = basename($_FILES['file']['name']);
    $targetFile = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
        // Return JSON with location of the uploaded image
        echo json_encode(['location' => $targetFile]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to move uploaded file.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error.']);
}
?>
