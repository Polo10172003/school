<?php
declare(strict_types=1);

/**
 * Ensure the student_accounts profile has core fields filled in.
 *
 * @param mysqli      $conn
 * @param array<mixed> $studentRow Expected keys: student_number, emailaddress/email, firstname, lastname.
 */
function ensure_student_account_profile(mysqli $conn, array $studentRow): void
{
    $studentNumber = trim((string) ($studentRow['student_number'] ?? ''));
    $email = trim((string) ($studentRow['emailaddress'] ?? ($studentRow['email'] ?? '')));
    $firstname = trim((string) ($studentRow['firstname'] ?? ''));
    $lastname = trim((string) ($studentRow['lastname'] ?? ''));

    if ($studentNumber === '' && $email === '') {
        return;
    }

    $accountRow = null;

    if ($studentNumber !== '') {
        $lookup = $conn->prepare('SELECT id, student_number, firstname, lastname, email FROM student_accounts WHERE student_number = ? LIMIT 1');
        if ($lookup) {
            $lookup->bind_param('s', $studentNumber);
            if ($lookup->execute()) {
                $accountRow = $lookup->get_result()->fetch_assoc();
            }
            $lookup->close();
        }
    }

    if (!$accountRow && $email !== '') {
        $lookupEmail = $conn->prepare('SELECT id, student_number, firstname, lastname, email FROM student_accounts WHERE email = ? LIMIT 1');
        if ($lookupEmail) {
            $lookupEmail->bind_param('s', $email);
            if ($lookupEmail->execute()) {
                $accountRow = $lookupEmail->get_result()->fetch_assoc();
            }
            $lookupEmail->close();
        }
    }

    if (!$accountRow) {
        $insert = $conn->prepare('INSERT INTO student_accounts (student_number, firstname, lastname, email, is_first_login) VALUES (?, ?, ?, ?, 1)');
        if ($insert) {
            $studentNumberParam = $studentNumber !== '' ? $studentNumber : null;
            $firstnameParam = $firstname !== '' ? $firstname : null;
            $lastnameParam = $lastname !== '' ? $lastname : null;
            $emailParam = $email !== '' ? $email : null;
            $insert->bind_param('ssss', $studentNumberParam, $firstnameParam, $lastnameParam, $emailParam);
            $insert->execute();
            $insert->close();
        }
        return;
    }

    $updates = [];
    $types = '';
    $values = [];

    $existingNumber = trim((string) ($accountRow['student_number'] ?? ''));
    $existingFirstname = trim((string) ($accountRow['firstname'] ?? ''));
    $existingLastname = trim((string) ($accountRow['lastname'] ?? ''));
    $existingEmail = trim((string) ($accountRow['email'] ?? ''));

    if ($studentNumber !== '' && $existingNumber === '') {
        $updates[] = 'student_number = ?';
        $values[] = $studentNumber;
        $types .= 's';
    }
    if ($firstname !== '' && $existingFirstname === '') {
        $updates[] = 'firstname = ?';
        $values[] = $firstname;
        $types .= 's';
    }
    if ($lastname !== '' && $existingLastname === '') {
        $updates[] = 'lastname = ?';
        $values[] = $lastname;
        $types .= 's';
    }
    if ($email !== '' && $existingEmail === '') {
        $updates[] = 'email = ?';
        $values[] = $email;
        $types .= 's';
    }

    if (empty($updates)) {
        return;
    }

    $types .= 'i';
    $values[] = (int) $accountRow['id'];

    $sql = 'UPDATE student_accounts SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $updateStmt = $conn->prepare($sql);
    if ($updateStmt) {
        $updateStmt->bind_param($types, ...$values);
        $updateStmt->execute();
        $updateStmt->close();
    }
}
