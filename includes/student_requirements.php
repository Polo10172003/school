<?php
declare(strict_types=1);

/**
 * Helper utilities for tracking student requirement submissions.
 */

/**
 * Ensure the student requirements table exists (idempotent).
 */
function student_requirements_ensure_schema(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $tableExists = false;
    if ($result = $conn->query("SHOW TABLES LIKE 'student_requirement_records'")) {
        $tableExists = $result->num_rows > 0;
        $result->free();
    }

    if (!$tableExists) {
        $collations = ['utf8mb4_uca1400_ai_ci', 'utf8mb4_unicode_ci', 'utf8mb4_general_ci'];
        $created = false;
        foreach ($collations as $collation) {
            $collation = trim($collation);
            if ($collation === '') {
                continue;
            }
            $createSql = "
                CREATE TABLE IF NOT EXISTS student_requirement_records (
                    student_id INT NOT NULL,
                    form_137_received TINYINT(1) NOT NULL DEFAULT 0,
                    psa_received TINYINT(1) NOT NULL DEFAULT 0,
                    good_moral_received TINYINT(1) NOT NULL DEFAULT 0,
                    baptismal_received TINYINT(1) NOT NULL DEFAULT 0,
                    marriage_contract_received TINYINT(1) NOT NULL DEFAULT 0,
                    requirement_scope VARCHAR(32) NOT NULL DEFAULT 'auto',
                    updated_by VARCHAR(190) DEFAULT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (student_id),
                    CONSTRAINT fk_student_requirement_student
                        FOREIGN KEY (student_id) REFERENCES students_registration(id)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE {$collation}
            ";

            if ($conn->query($createSql)) {
                $created = true;
                break;
            }

            error_log('[requirements] Failed to create table with collation ' . $collation . ': ' . $conn->error);

            if ($result = $conn->query("SHOW TABLES LIKE 'student_requirement_records'")) {
                $tableExists = $result->num_rows > 0;
                $result->free();
                if ($tableExists) {
                    $created = true;
                    break;
                }
            }
        }

        if (!$created) {
            throw new RuntimeException('Unable to create student requirement records table: ' . $conn->error);
        }
    }

    $columns = [];
    if ($result = $conn->query('SHOW COLUMNS FROM student_requirement_records')) {
        while ($row = $result->fetch_assoc()) {
            $columns[strtolower((string) ($row['Field'] ?? ''))] = true;
        }
        $result->free();
    }

    if (!isset($columns['requirement_scope'])) {
        if (!$conn->query("ALTER TABLE student_requirement_records ADD COLUMN requirement_scope VARCHAR(32) NOT NULL DEFAULT 'auto' AFTER marriage_contract_received")) {
            error_log('[requirements] Failed adding requirement_scope column: ' . $conn->error);
        }
    }
    if (!isset($columns['updated_by'])) {
        if (!$conn->query("ALTER TABLE student_requirement_records ADD COLUMN updated_by VARCHAR(190) DEFAULT NULL AFTER requirement_scope")) {
            error_log('[requirements] Failed adding updated_by column: ' . $conn->error);
        }
    }

    $ensured = true;
}

/**
 * Map logical requirement keys to database columns.
 *
 * @return array<string,string>
 */
function student_requirements_key_map(): array
{
    return [
        'form_137'          => 'form_137_received',
        'psa'               => 'psa_received',
        'good_moral'        => 'good_moral_received',
        'baptismal'         => 'baptismal_received',
        'marriage_contract' => 'marriage_contract_received',
    ];
}

/**
 * Human-friendly labels for requirement keys.
 *
 * @return array<string,string>
 */
function student_requirements_labels(): array
{
    return [
        'form_137'          => 'Form 137 (Report Card)',
        'psa'               => 'PSA Birth Certificate',
        'good_moral'        => 'Certificate of Good Moral',
        'baptismal'         => 'Baptismal Certificate',
        'marriage_contract' => "Parents' Marriage Contract",
    ];
}

/**
 * Retrieve readable label for a requirement key.
 */
function student_requirements_label(string $key): string
{
    $labels = student_requirements_labels();
    return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
}

/**
 * Determine the requirement scope based on grade level.
 */
function student_requirements_determine_scope(string $gradeLevel): string
{
    $normalized = strtolower(preg_replace('/[^a-z0-9]/', '', $gradeLevel));
    if ($normalized === '') {
        return 'k_to_12';
    }

    $earlyExactMatches = [
        'preprime1',
        'preprime2',
        'kindergarten',
    ];

    if (in_array($normalized, $earlyExactMatches, true)) {
        return 'early_child';
    }

    return 'k_to_12';
}

/**
 * Resolve the effective scope, preferring stored scope when available.
 */
function student_requirements_resolve_scope(string $gradeLevel, array $normalizedRecord): string
{
    $gradeScope = student_requirements_determine_scope($gradeLevel);
    $storedScope = strtolower(trim((string) ($normalizedRecord['scope'] ?? '')));

    if ($gradeScope === 'early_child') {
        return 'early_child';
    }

    if ($storedScope === 'k_to_12') {
        return 'k_to_12';
    }

    if ($storedScope === '' || $storedScope === 'auto') {
        return $gradeScope;
    }

    return $gradeScope;
}

/**
 * Provide a human-readable description for a scope.
 */
function student_requirements_scope_label(string $scope): string
{
    return $scope === 'early_child' ? 'Pre-Prime to Kinder' : 'Grade 1 to 12';
}

/**
 * List required keys for the given scope.
 *
 * @return string[]
 */
function student_requirements_required_keys(string $scope): array
{
    if ($scope === 'early_child') {
        return ['psa'];
    }

    return ['form_137', 'psa', 'good_moral'];
}

/**
 * List optional keys for the given scope.
 *
 * @return string[]
 */
function student_requirements_optional_keys(string $scope): array
{
    return ['baptismal', 'marriage_contract'];
}

/**
 * Fetch requirement rows for multiple students keyed by student_id.
 *
 * @param int[] $studentIds
 * @return array<int,array<string,mixed>>
 */
function student_requirements_fetch_map(mysqli $conn, array $studentIds): array
{
    $studentIds = array_values(array_filter(array_map('intval', $studentIds), static function (int $id): bool {
        return $id > 0;
    }));

    if (empty($studentIds)) {
        return [];
    }

    $idList = implode(',', $studentIds);
    $sql = "SELECT * FROM student_requirement_records WHERE student_id IN ($idList)";

    $map = [];
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $studentId = isset($row['student_id']) ? (int) $row['student_id'] : 0;
            if ($studentId > 0) {
                $map[$studentId] = $row;
            }
        }
        $result->free();
    }

    return $map;
}

/**
 * Fetch a single requirement record.
 */
function student_requirements_fetch_single(mysqli $conn, int $studentId): ?array
{
    $studentId = max(0, $studentId);
    if ($studentId === 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT * FROM student_requirement_records WHERE student_id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

/**
 * Normalise database row into a convenient structure.
 *
 * @return array{values:array<string,bool>,scope:string,updated_at:?string,updated_by:?string}
 */
function student_requirements_normalize_record(?array $record): array
{
    $keyMap = student_requirements_key_map();
    $values = [];
    foreach ($keyMap as $key => $column) {
        $values[$key] = isset($record[$column]) ? (bool) ((int) $record[$column]) : false;
    }

    return [
        'values'     => $values,
        'scope'      => isset($record['requirement_scope']) ? (string) $record['requirement_scope'] : 'auto',
        'updated_at' => isset($record['updated_at']) ? (string) $record['updated_at'] : null,
        'updated_by' => isset($record['updated_by']) ? (string) $record['updated_by'] : null,
    ];
}

/**
 * Build summary metadata for UI.
 *
 * @return array{
 *   scope:string,
 *   scope_label:string,
 *   required_keys:array<int,string>,
 *   optional_keys:array<int,string>,
 *   missing_keys:array<int,string>,
 *   missing_labels:array<int,string>,
 *   complete:bool,
 *   status_label:string,
 *   status_class:string,
 *   updated_at:?string,
 *   updated_by:?string
 * }
 */
function student_requirements_calculate_summary(string $scope, array $normalizedRecord): array
{
    $values = $normalizedRecord['values'] ?? [];
    $requiredKeys = student_requirements_required_keys($scope);
    $optionalKeys = student_requirements_optional_keys($scope);

    $missingKeys = [];
    foreach ($requiredKeys as $key) {
        if (empty($values[$key])) {
            $missingKeys[] = $key;
        }
    }

    $complete = count($missingKeys) === 0;

    $missingLabels = array_map('student_requirements_label', $missingKeys);
    $statusLabel = $complete ? 'Complete' : ('Missing: ' . implode(', ', $missingLabels));
    if (!$complete && empty($missingLabels)) {
        $statusLabel = 'Pending';
    }

    $statusClass = $complete ? 'success' : 'pending';

    return [
        'scope'         => $scope,
        'scope_label'   => student_requirements_scope_label($scope),
        'required_keys' => $requiredKeys,
        'optional_keys' => $optionalKeys,
        'missing_keys'  => $missingKeys,
        'missing_labels'=> $missingLabels,
        'complete'      => $complete,
        'status_label'  => $statusLabel,
        'status_class'  => $statusClass,
        'updated_at'    => $normalizedRecord['updated_at'] ?? null,
        'updated_by'    => $normalizedRecord['updated_by'] ?? null,
    ];
}

/**
 * Build field configuration for the modal UI.
 *
 * @return array<int,array{key:string,label:string,required:bool,value:bool}>
 */
function student_requirements_build_fields(string $scope, array $values): array
{
    $definitions = student_requirements_labels();
    $requiredKeys = student_requirements_required_keys($scope);
    $optionalKeys = student_requirements_optional_keys($scope);

    $orderedKeys = $scope === 'early_child'
        ? array_merge($requiredKeys, $optionalKeys)
        : array_merge(['form_137', 'good_moral', 'psa'], $optionalKeys);

    $deduped = [];
    foreach ($orderedKeys as $key) {
        if (!in_array($key, $deduped, true)) {
            $deduped[] = $key;
        }
    }

    $fields = [];
    foreach ($deduped as $key) {
        $fields[] = [
            'key'      => $key,
            'label'    => $definitions[$key] ?? ucwords(str_replace('_', ' ', $key)),
            'required' => in_array($key, $requiredKeys, true),
            'value'    => !empty($values[$key]),
        ];
    }

    return $fields;
}

/**
 * Build payload that merges values, scope, fields and summary.
 *
 * @return array{
 *   scope:string,
 *   scope_label:string,
 *   values:array<string,bool>,
 *   fields:array<int,array{key:string,label:string,required:bool,value:bool}>,
 *   summary:array<string,mixed>,
 *   updated_at:?string,
 *   updated_by:?string
 * }
 */
function student_requirements_build_payload(string $gradeLevel, ?array $rawRecord): array
{
    $normalized = student_requirements_normalize_record($rawRecord);
    $scope = student_requirements_resolve_scope($gradeLevel, $normalized);

    $payload = [
        'scope'       => $scope,
        'scope_label' => student_requirements_scope_label($scope),
        'values'      => $normalized['values'],
        'fields'      => student_requirements_build_fields($scope, $normalized['values']),
        'summary'     => student_requirements_calculate_summary($scope, $normalized),
        'updated_at'  => $normalized['updated_at'],
        'updated_by'  => $normalized['updated_by'],
    ];

    return $payload;
}

/**
 * Attach requirement summaries to each student array.
 *
 * @param array<int,array<string,mixed>> $students
 * @return array<int,array<string,mixed>>
 */
function student_requirements_append_summary(mysqli $conn, array $students): array
{
    if (empty($students)) {
        return $students;
    }

    $ids = [];
    foreach ($students as $row) {
        $id = isset($row['id']) ? (int) $row['id'] : 0;
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    $map = student_requirements_fetch_map($conn, $ids);

    foreach ($students as &$row) {
        $studentId = isset($row['id']) ? (int) $row['id'] : 0;
        $gradeLevel = '';
        if (!empty($row['year'])) {
            $gradeLevel = (string) $row['year'];
        } elseif (!empty($row['grade_level'])) {
            $gradeLevel = (string) $row['grade_level'];
        }

        $record = $map[$studentId] ?? null;
        $payload = student_requirements_build_payload($gradeLevel, $record);

        $row['requirements_summary'] = $payload['summary'];
        $row['requirements_scope'] = $payload['scope'];
        $row['requirements_scope_label'] = $payload['scope_label'];
    }
    unset($row);

    return $students;
}

/**
 * Persist requirement values for a student.
 *
 * @param array<string,bool> $values
 */
function student_requirements_save(
    mysqli $conn,
    int $studentId,
    array $values,
    string $scope,
    ?string $updatedBy = null
): bool {
    $studentId = max(0, $studentId);
    if ($studentId === 0) {
        return false;
    }

    student_requirements_ensure_schema($conn);

    $keyMap = student_requirements_key_map();
    $columns = array_values($keyMap);
    $allKeys = array_keys($keyMap);

    $resolvedValues = [];
    foreach ($allKeys as $key) {
        $resolvedValues[$key] = !empty($values[$key]);
    }

    $placeholders = implode(',', array_fill(0, count($columns) + 3, '?'));
    $updateFragments = [];
    foreach ($columns as $column) {
        $updateFragments[] = "{$column} = VALUES({$column})";
    }
    $updateFragments[] = "requirement_scope = VALUES(requirement_scope)";
    $updateFragments[] = "updated_by = VALUES(updated_by)";
    $updateSql = implode(', ', $updateFragments);

    $sql = "
        INSERT INTO student_requirement_records
            (student_id, " . implode(', ', $columns) . ", requirement_scope, updated_by)
        VALUES ($placeholders)
        ON DUPLICATE KEY UPDATE {$updateSql}
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('[requirements] Failed to prepare save statement: ' . $conn->error);
        return false;
    }

    $studentIdParam = $studentId;
    $form137Param = $resolvedValues['form_137'] ? 1 : 0;
    $psaParam = $resolvedValues['psa'] ? 1 : 0;
    $goodMoralParam = $resolvedValues['good_moral'] ? 1 : 0;
    $baptismalParam = $resolvedValues['baptismal'] ? 1 : 0;
    $marriageParam = $resolvedValues['marriage_contract'] ? 1 : 0;
    $scopeParam = $scope;
    $updatedByParam = $updatedBy !== null ? (string) $updatedBy : null;

    $types = 'i' . str_repeat('i', count($columns)) . 'ss';
    $stmt->bind_param(
        $types,
        $studentIdParam,
        $form137Param,
        $psaParam,
        $goodMoralParam,
        $baptismalParam,
        $marriageParam,
        $scopeParam,
        $updatedByParam
    );
    $ok = $stmt->execute();
    if (!$ok) {
        error_log('[requirements] Failed to save record: ' . $stmt->error);
    }
    $stmt->close();

    return $ok;
}
