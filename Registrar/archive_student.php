<?php
declare(strict_types=1);

include __DIR__ . '/../db_connection.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: registrar_dashboard.php');
    exit();
}

$transactionStarted = false;

try {
    if (!($conn instanceof mysqli)) {
        throw new RuntimeException('Database connection not initialised.');
    }

    $conn->set_charset('utf8mb4');
    @$conn->query("SET NAMES 'utf8mb4'");
    @$conn->query("SET CHARACTER SET 'utf8mb4'");

    if (!$conn->begin_transaction()) {
        throw new RuntimeException('Failed to start database transaction.');
    }
    $transactionStarted = true;

    $fetch = $conn->prepare('SELECT * FROM students_registration WHERE id = ? LIMIT 1');
    if (!$fetch) {
        throw new RuntimeException('Failed to prepare student lookup.');
    }
    $fetch->bind_param('i', $id);
    $fetch->execute();
    $result = $fetch->get_result();
    $student = $result ? $result->fetch_assoc() : null;
    $fetch->close();

    if (!$student) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        echo "<script>alert('Student record not found.'); window.location='registrar_dashboard.php';</script>";
        exit();
    }

    if (array_key_exists('portal_status', $student)) {
        $student['portal_status'] = 'pending';
    }

    $columns = array_keys($student);
    if (count($columns) === 0) {
        throw new RuntimeException('Unable to determine columns for archiving.');
    }

    $columnList = implode(', ', array_map(
        static function (string $column): string {
            return '`' . str_replace('`', '``', $column) . '`';
        },
        $columns
    ));
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $values = array_values($student);
    $types = str_repeat('s', count($values));

    $insert = $conn->prepare("INSERT INTO archived_students ($columnList) VALUES ($placeholders)");
    if (!$insert) {
        throw new RuntimeException('Failed to prepare archive insert.');
    }

    $params = array_merge([$types], $values);
    $refs = [];
    foreach ($params as $index => $value) {
        $refs[$index] = &$params[$index];
    }
    call_user_func_array([$insert, 'bind_param'], $refs);
    $insert->execute();
    $insert->close();

    $emails = [];
    if (!empty($student['emailaddress'])) {
        $emails[] = (string) $student['emailaddress'];
    }
    if (!empty($student['email'])) {
        $emails[] = (string) $student['email'];
    }
    $emails = array_unique(array_filter($emails));

    foreach ($emails as $email) {
        $deleteAccount = $conn->prepare('DELETE FROM student_accounts WHERE email = ?');
        if ($deleteAccount) {
            $deleteAccount->bind_param('s', $email);
            $deleteAccount->execute();
            $deleteAccount->close();
        }
    }

    if (!empty($student['student_number'])) {
        $studentNumber = (string) $student['student_number'];
        $deleteByNumber = $conn->prepare('DELETE FROM student_accounts WHERE student_number = ?');
        if ($deleteByNumber) {
            $deleteByNumber->bind_param('s', $studentNumber);
            $deleteByNumber->execute();
            $deleteByNumber->close();
        }
    }

    $delete = $conn->prepare('DELETE FROM students_registration WHERE id = ?');
    if (!$delete) {
        throw new RuntimeException('Failed to prepare student removal.');
    }
    $delete->bind_param('i', $id);
    $delete->execute();
    $delete->close();

    $conn->commit();
    $transactionStarted = false;

    echo "<script>alert('Student archived successfully.'); window.location='registrar_dashboard.php?msg=archived';</script>";
    exit();
} catch (Throwable $exception) {
    if ($transactionStarted && ($conn instanceof mysqli)) {
        $conn->rollback();
    }
    error_log('[registrar] archive_student failure: ' . $exception->getMessage());
    echo "<script>alert('Failed to archive the student. Please try again.'); window.location='registrar_dashboard.php';</script>";
    exit();
} finally {
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
?>
