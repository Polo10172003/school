<?php
/**
 * Remove pending registration records that have been inactive beyond the grace period.
 */
function cleanupExpiredRegistrations(mysqli $conn, int $expiryDays = 14): void
{
    $sql = "DELETE FROM students_registration
             WHERE enrollment_status = 'waiting'
               AND created_at IS NOT NULL
               AND created_at < (NOW() - INTERVAL ? DAY)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('i', $expiryDays);
    $stmt->execute();
    $stmt->close();
}
