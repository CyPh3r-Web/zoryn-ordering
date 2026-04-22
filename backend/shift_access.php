<?php
if (!function_exists('zoryn_get_cashier_shift_access')) {
    /**
     * Evaluate cashier shift access for the current day and time.
     *
     * Returns:
     * - has_shift_today: bool
     * - is_within_shift: bool
     * - active_shift_id: int|null
     * - message: string
     */
    function zoryn_get_cashier_shift_access(mysqli $conn, int $userId): array {
        $response = [
            'has_shift_today' => false,
            'is_within_shift' => false,
            'is_grace_period' => false,
            'active_shift_id' => null,
            'status' => 'no_shift',
            'seconds_until_end' => null,
            'grace_seconds_left' => null,
            'message' => 'No shift assigned for today.'
        ];

        $stmt = $conn->prepare("
            SELECT shift_id, shift_date, start_time, end_time
            FROM cashier_shifts
            WHERE user_id = ?
              AND shift_date = CURDATE()
            ORDER BY start_time ASC
            LIMIT 1
        ");
        if (!$stmt) {
            $response['message'] = 'Unable to validate shift schedule.';
            return $response;
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if (!$row) {
            return $response;
        }

        $response['has_shift_today'] = true;
        $response['active_shift_id'] = (int) $row['shift_id'];

        try {
            $tz = new DateTimeZone('Asia/Manila');
            $now = new DateTimeImmutable('now', $tz);
            $start = new DateTimeImmutable($row['shift_date'] . ' ' . $row['start_time'], $tz);
            $end = new DateTimeImmutable($row['shift_date'] . ' ' . $row['end_time'], $tz);
            $graceEnd = $end->modify('+5 minutes');

            if ($now < $start) {
                $response['status'] = 'before_shift';
                $response['message'] = 'Your shift has not started yet.';
                return $response;
            }

            if ($now <= $end) {
                $response['is_within_shift'] = true;
                $response['status'] = 'active';
                $response['seconds_until_end'] = max(0, $end->getTimestamp() - $now->getTimestamp());
                $response['message'] = 'Shift is active.';
                return $response;
            }

            if ($now <= $graceEnd) {
                $response['is_grace_period'] = true;
                $response['status'] = 'grace';
                $response['grace_seconds_left'] = max(0, $graceEnd->getTimestamp() - $now->getTimestamp());
                $response['message'] = 'Shift ended. You have 5 minutes to submit your cash count.';
                return $response;
            }

            $response['status'] = 'ended';
            $response['message'] = 'Your shift has ended.';
        } catch (Throwable $e) {
            $response['message'] = 'Unable to validate shift schedule.';
        }

        return $response;
    }
}

