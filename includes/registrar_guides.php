<?php

declare(strict_types=1);

/**
 * Ensure the upload directory for registrar guides exists and return its absolute path.
 */
function registrar_guides_upload_dir(): string
{
    $uploadDir = __DIR__ . '/../uploads/registrar_guides';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Unable to create registrar guides upload directory.');
        }
    }
    return $uploadDir;
}

/**
 * Build the public path that can be used to download a stored guide.
 */
function registrar_guides_public_path(string $fileName): string
{
    return 'uploads/registrar_guides/' . $fileName;
}

/**
 * Creates the registrar_guides table if it does not already exist.
 */
function registrar_guides_ensure_schema(mysqli $conn): void
{
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS registrar_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_level VARCHAR(64) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    uploaded_by VARCHAR(100) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Unable to ensure registrar guides table: ' . $conn->error);
    }
}

/**
 * Insert a guide record and return its new primary key.
 */
function registrar_guides_insert(
    mysqli $conn,
    string $gradeLevel,
    string $fileName,
    string $originalName,
    int $fileSize,
    ?string $uploadedBy = null
): int {
    $stmt = $conn->prepare(
        'INSERT INTO registrar_guides (grade_level, file_name, original_name, file_size, uploaded_by)
         VALUES (?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('sssis', $gradeLevel, $fileName, $originalName, $fileSize, $uploadedBy);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Unable to store registrar guide: ' . $error);
    }

    $newId = $stmt->insert_id;
    $stmt->close();

    return (int) $newId;
}

/**
 * Fetch guides, optionally filtered by grade level and/or uploader.
 *
 * @return array<int, array<string, mixed>>
 */
function registrar_guides_fetch_all(mysqli $conn, ?string $gradeLevel = null, ?string $uploadedBy = null): array
{
    $conditions = [];
    $types = '';
    $params = [];

    if ($gradeLevel !== null && $gradeLevel !== '') {
        $conditions[] = 'grade_level = ?';
        $types .= 's';
        $params[] = $gradeLevel;
    }

    if ($uploadedBy !== null && $uploadedBy !== '') {
        $conditions[] = 'uploaded_by = ?';
        $types .= 's';
        $params[] = $uploadedBy;
    }

    $sql = 'SELECT * FROM registrar_guides';

    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $orderBy = ($gradeLevel !== null && $gradeLevel !== '') ? 'uploaded_at DESC' : 'grade_level, uploaded_at DESC';
    $sql .= ' ORDER BY ' . $orderBy;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Unable to fetch registrar guides: ' . $error);
    }

    $result = $stmt->get_result();
    $files = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $files ?: [];
}

/**
 * Find a guide record by primary key.
 */
function registrar_guides_find(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM registrar_guides WHERE id = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Unable to fetch registrar guide: ' . $error);
    }

    $result = $stmt->get_result();
    $guide = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $guide ?: null;
}

/**
 * Remove a guide record by id.
 */
function registrar_guides_delete(mysqli $conn, int $id): bool
{
    $stmt = $conn->prepare('DELETE FROM registrar_guides WHERE id = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $id);
    $deleted = $stmt->execute();
    $stmt->close();

    return (bool) $deleted;
}
