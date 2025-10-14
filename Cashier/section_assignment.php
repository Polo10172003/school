<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/adviser_assignments.php';

/**
 * Helper functions for automatic section assignment triggered during cashier operations.
 */

function cashier_assign_section_if_needed(mysqli $conn, int $studentId): ?array
{
    $stmt = $conn->prepare('SELECT year, section, adviser, course FROM students_registration WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $stmt->bind_result($year, $currentSection, $currentAdviser, $course);
    if (!$stmt->fetch()) {
        $stmt->close();
        return null;
    }
    $stmt->close();

    $currentSection = trim((string) $currentSection);
    if ($currentSection !== '' && strcasecmp($currentSection, 'To be assigned') !== 0) {
        return null; // already assigned
    }

    $assignment = cashier_determine_section_assignment($conn, (string) $year, (string) $course);
    if (!$assignment) {
        return null;
    }

    [$section, $adviser] = $assignment;
    $update = $conn->prepare('UPDATE students_registration SET section = ?, adviser = ? WHERE id = ?');
    if (!$update) {
        return null;
    }
    $update->bind_param('ssi', $section, $adviser, $studentId);
    $update->execute();
    $update->close();

    return ['section' => $section, 'adviser' => $adviser];
}

function cashier_determine_section_assignment(mysqli $conn, string $year, ?string $course = null): ?array
{
    $normalizedYear = cashier_section_normalize_grade($year);
    $gradeVariants = cashier_section_grade_synonyms($year);

    if (in_array($normalizedYear, ['Pre-Prime 1', 'Pre-Prime 2', 'Kindergarten'], true)) {
        $hersheyCount = cashier_section_sum_count($conn, $gradeVariants, 'Hershey');
        if ($hersheyCount < 20) {
            return cashier_section_pack_assignment($conn, $normalizedYear, 'Hershey');
        }
        return cashier_section_pack_assignment($conn, $normalizedYear, 'Kisses');
    }

    if (in_array($normalizedYear, ['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'], true)) {
        $sectionACount = cashier_section_sum_count($conn, $gradeVariants, 'Section A');
        if ($sectionACount < 30) {
            return cashier_section_pack_assignment($conn, $normalizedYear, 'Section A');
        }
        return cashier_section_pack_assignment($conn, $normalizedYear, 'Section B');
    }

    if (in_array($normalizedYear, ['Grade 7','Grade 8','Grade 9','Grade 10'], true)) {
        $sectionACount = cashier_section_sum_count($conn, $gradeVariants, 'Section A');
        if ($sectionACount < 40) {
            return cashier_section_pack_assignment($conn, $normalizedYear, 'Section A');
        }
        return cashier_section_pack_assignment($conn, $normalizedYear, 'Section B');
    }

    if (in_array($normalizedYear, ['Grade 11','Grade 12'], true)) {
        $strand = trim((string) $course);
        if ($strand === '') {
            return null;
        }
        $section = $strand . ' - Section 1';
        return cashier_section_pack_assignment($conn, $normalizedYear, $section);
    }

    return null;
}

/**
 * Bundle the section with the adviser configured by administrators.
 *
 * @return array{0:string,1:string}|null
 */
function cashier_section_pack_assignment(mysqli $conn, string $gradeLevel, string $section): ?array
{
    $section = trim($section);
    if ($section === '') {
        return null;
    }

    $adviser = adviser_assignments_adviser_for_section($conn, $gradeLevel, $section);
    if ($adviser === null || $adviser === '') {
        $adviser = 'To be assigned';
    }

    return [$section, $adviser];
}

function cashier_section_sum_count(mysqli $conn, array $gradeVariants, string $section): int
{
    $total = 0;
    foreach ($gradeVariants as $variant) {
        $query = $conn->prepare('SELECT COUNT(*) FROM students_registration WHERE year = ? AND section = ?');
        if (!$query) {
            continue;
        }
        $query->bind_param('ss', $variant, $section);
        $query->execute();
        $query->bind_result($count);
        if ($query->fetch()) {
            $total += (int) $count;
        }
        $query->close();
    }
    return $total;
}

function cashier_section_normalize_grade(string $grade): string
{
    $grade = trim($grade);
    if ($grade === '') {
        return '';
    }
    $grade = str_ireplace(['Kinder 1', 'Kinder I'], 'Kindergarten', $grade);
    $grade = str_ireplace(['Kinder 2', 'Kinder II'], 'Kindergarten', $grade);
    return $grade;
}

function cashier_section_grade_synonyms(string $grade): array
{
    $normalized = cashier_section_normalize_grade($grade);
    if ($normalized === 'Kindergarten') {
        return ['Kindergarten', 'Kinder 1', 'Kinder 2'];
    }
    return [$normalized];
}
