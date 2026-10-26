<?php
require 'config.php';

function migrate_passwords_for_table($conn, $tableName) {
    $selectSql = "SELECT id, password";
    if ($tableName === 'students') {
        $selectSql = "SELECT id, student_id, password";
    }

    $selectSql .= " FROM $tableName WHERE password IS NOT NULL AND password <> ''";
    $result = $conn->query($selectSql);

    if (!$result) {
        echo "Failed to read $tableName: " . $conn->error . PHP_EOL;
        return 0;
    }

    $updated = 0;

    while ($row = $result->fetch_assoc()) {
        $storedPassword = $row['password'];

        if (is_password_hash($storedPassword)) {
            continue;
        }

        $newHash = password_hash($storedPassword, PASSWORD_DEFAULT);

        if ($tableName === 'students') {
            $stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
            $stmt->bind_param('ss', $newHash, $row['student_id']);
        } else {
            $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $newHash, $row['id']);
        }

        if ($stmt->execute()) {
            $updated++;
        } else {
            echo "Failed to update $tableName id=" . ($row['id'] ?? 'unknown') . ": " . $stmt->error . PHP_EOL;
        }

        $stmt->close();
    }

    echo "Table $tableName: migrated $updated legacy password(s)." . PHP_EOL;
    return $updated;
}

$total = 0;
$total += migrate_passwords_for_table($conn, 'admin');
$total += migrate_passwords_for_table($conn, 'students');

echo "Total migrated: $total" . PHP_EOL;
$conn->close();
