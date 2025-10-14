<?php

declare(strict_types=1);

/**
 * Central utilities for managing adviser assignments per grade level and section.
 */

/**
 * Ensure that the section_advisers table exists.
 */
function adviser_assignments_ensure_table(mysqli $conn): bool
{
    $sql = "
        CREATE TABLE IF NOT EXISTS section_advisers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            grade_level VARCHAR(64) NOT NULL,
            section VARCHAR(128) NOT NULL,
            adviser VARCHAR(128) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_grade_section (grade_level, section)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    return $conn->query($sql) === true;
}

/**
 * Retrieve all adviser assignments ordered by grade and section.
 *
 * @return array<int, array{ id:int, grade_level:string, section:string, adviser:string }>
 */
function adviser_assignments_fetch(mysqli $conn): array
{
    adviser_assignments_ensure_table($conn);

    $rows = [];
    $sql = 'SELECT id, grade_level, section, adviser FROM section_advisers ORDER BY grade_level, section';
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'id'          => (int) ($row['id'] ?? 0),
                'grade_level' => trim((string) ($row['grade_level'] ?? '')),
                'section'     => trim((string) ($row['section'] ?? '')),
                'adviser'     => trim((string) ($row['adviser'] ?? '')),
            ];
        }
        $result->close();
    }

    return $rows;
}

/**
 * Find a single adviser assignment.
 *
 * @return array{id:int, grade_level:string, section:string, adviser:string}|null
 */
function adviser_assignments_find(mysqli $conn, string $gradeLevel, string $section): ?array
{
    adviser_assignments_ensure_table($conn);

    $stmt = $conn->prepare('SELECT id, grade_level, section, adviser FROM section_advisers WHERE grade_level = ? AND section = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ss', $gradeLevel, $section);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'id'          => (int) ($row['id'] ?? 0),
        'grade_level' => trim((string) ($row['grade_level'] ?? '')),
        'section'     => trim((string) ($row['section'] ?? '')),
        'adviser'     => trim((string) ($row['adviser'] ?? '')),
    ];
}

/**
 * Find a single adviser assignment by its primary key.
 *
 * @return array{id:int, grade_level:string, section:string, adviser:string}|null
 */
function adviser_assignments_get(mysqli $conn, int $id): ?array
{
    adviser_assignments_ensure_table($conn);

    $stmt = $conn->prepare('SELECT id, grade_level, section, adviser FROM section_advisers WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'id'          => (int) ($row['id'] ?? 0),
        'grade_level' => trim((string) ($row['grade_level'] ?? '')),
        'section'     => trim((string) ($row['section'] ?? '')),
        'adviser'     => trim((string) ($row['adviser'] ?? '')),
    ];
}

/**
 * Insert or update an adviser assignment.
 */
function adviser_assignments_upsert(mysqli $conn, string $gradeLevel, string $section, string $adviser): bool
{
    adviser_assignments_ensure_table($conn);

    $gradeLevel = trim($gradeLevel);
    $section    = trim($section);
    $adviser    = trim($adviser);

    if ($gradeLevel === '' || $section === '' || $adviser === '') {
        return false;
    }

    $stmt = $conn->prepare(
        'INSERT INTO section_advisers (grade_level, section, adviser) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE adviser = ?, updated_at = CURRENT_TIMESTAMP'
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ssss', $gradeLevel, $section, $adviser, $adviser);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        adviser_assignments_update_students($conn, $gradeLevel, $section, $adviser);
    }

    return $ok;
}

/**
 * Delete an adviser assignment by id.
 */
function adviser_assignments_delete(mysqli $conn, int $id): bool
{
    adviser_assignments_ensure_table($conn);

    $stmt = $conn->prepare('DELETE FROM section_advisers WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

/**
 * Delete an adviser assignment by unique pair.
 */
function adviser_assignments_delete_by_pair(mysqli $conn, string $gradeLevel, string $section): bool
{
    adviser_assignments_ensure_table($conn);

    $stmt = $conn->prepare('DELETE FROM section_advisers WHERE grade_level = ? AND section = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $gradeLevel, $section);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

/**
 * Retrieve adviser assigned for a grade + section combination.
 */
function adviser_assignments_adviser_for_section(mysqli $conn, string $gradeLevel, string $section): ?string
{
    $assignment = adviser_assignments_find($conn, $gradeLevel, $section);
    return $assignment ? $assignment['adviser'] : null;
}

/**
 * Return a list of adviser name suggestions for the provided grade level.
 *
 * @return array<int, string>
 */
function adviser_assignments_adviser_options(mysqli $conn, string $gradeLevel): array
{
    adviser_assignments_ensure_table($conn);

    $options = [];
    $stmt = $conn->prepare('SELECT DISTINCT adviser FROM section_advisers WHERE grade_level = ? ORDER BY adviser');
    if ($stmt) {
        $stmt->bind_param('s', $gradeLevel);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $name = trim((string) ($row['adviser'] ?? ''));
                if ($name !== '' && !in_array($name, $options, true)) {
                    $options[] = $name;
                }
            }
        }
        $stmt->close();
    }

    return $options;
}

/**
 * Update an existing adviser assignment by id.
 */
function adviser_assignments_update(mysqli $conn, int $id, string $gradeLevel, string $section, string $adviser, ?string &$error = null): bool
{
    adviser_assignments_ensure_table($conn);

    $gradeLevel = trim($gradeLevel);
    $section    = trim($section);
    $adviser    = trim($adviser);

    if ($gradeLevel === '' || $section === '' || $adviser === '') {
        $error = 'Grade level, section, and adviser are required.';
        return false;
    }

    $stmt = $conn->prepare('UPDATE section_advisers SET grade_level = ?, section = ?, adviser = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    if (!$stmt) {
        $error = $conn->error;
        return false;
    }

    $stmt->bind_param('sssi', $gradeLevel, $section, $adviser, $id);
    $ok = $stmt->execute();
    if (!$ok) {
        if ($conn->errno === 1062) {
            $error = 'Another adviser is already assigned to that grade and section.';
        } else {
            $error = $conn->error;
        }
    }
    $stmt->close();

    if ($ok) {
        adviser_assignments_update_students($conn, $gradeLevel, $section, $adviser);
    }

    return $ok;
}

/**
 * Return section labels configured for the provided grade level.
 *
 * @return array<int, string>
 */
function adviser_assignments_sections_for_grade(mysqli $conn, string $gradeLevel): array
{
    adviser_assignments_ensure_table($conn);

    $sections = [];
    $stmt = $conn->prepare('SELECT DISTINCT section FROM section_advisers WHERE grade_level = ? ORDER BY section');
    if ($stmt) {
        $stmt->bind_param('s', $gradeLevel);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $label = trim((string) ($row['section'] ?? ''));
                if ($label !== '' && !in_array($label, $sections, true)) {
                    $sections[] = $label;
                }
            }
        }
        $stmt->close();
    }

    return $sections;
}

/**
 * Return known variants for a given grade level to keep assignments in sync.
 *
 * @return array<int, string>
 */
function adviser_assignments_year_variants(string $gradeLevel): array
{
    $gradeLevel = trim($gradeLevel);
    if ($gradeLevel === '') {
        return [];
    }

    if (strcasecmp($gradeLevel, 'Kindergarten') === 0) {
        return ['Kindergarten', 'Kinder 1', 'Kinder 2', 'Kinder I', 'Kinder II'];
    }

    return [$gradeLevel];
}

/**
 * Update adviser field for students already placed in the given grade + section.
 */
function adviser_assignments_update_students(mysqli $conn, string $gradeLevel, string $section, string $adviser): void
{
    $yearOptions = adviser_assignments_year_variants($gradeLevel);
    foreach ($yearOptions as $year) {
        $stmt = $conn->prepare('UPDATE students_registration SET adviser = ? WHERE year = ? AND section = ?');
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('sss', $adviser, $year, $section);
        $stmt->execute();
        $stmt->close();
    }
}
