<?php
include 'config.php';

$error = "";

if (isset($_POST['login'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session. Please try again.";
    } else {
        $student_id = trim($_POST['student_id'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT student_id, full_name, password FROM students WHERE student_id = ? AND status = 'Approved' LIMIT 1");
        $stmt->bind_param('s', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if ($student && verify_password($password, $student['password'])) {
            if (!is_password_hash($student['password'])) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upgradeStmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                $upgradeStmt->bind_param('ss', $newHash, $student['student_id']);
                $upgradeStmt->execute();
            }

            session_regenerate_id(true);
            $_SESSION['student_id'] = $student['student_id'];
            $_SESSION['student_name'] = $student['full_name'];
            header("Location: student_dashboard.php");
            exit();
        } else {
            $error = "Invalid Student ID or Password, or account not approved yet.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-box {
            background-color: #1a1a1a;
            padding: 40px;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            margin: 100px auto;
            border: 1px solid #333;
        }
        .login-box h2 {
            text-align: center;
            color: #00d4ff;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
        }
        input {
            width: 100%;
            padding: 12px;
            background-color: #0f0f0f;
            border: 1px solid #444;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
        }
        input:focus {
            outline: none;
            border-color: #00d4ff;
        }
        button {
            width: 100%;
            padding: 14px;
            background-color: #00d4ff;
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #00b8e0;
        }
        .error {
            color: #ff4d4d;
            text-align: center;
            margin-bottom: 15px;
        }
        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #00d4ff;
            text-decoration: none;
        }
        .note {
            text-align: center;
            color: #888;
            font-size: 0.9rem;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Student Login</h2>

        <?php if ($error != "") { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
            <div class="form-group">
                <label>Student ID</label>
                <input type="text" name="student_id" placeholder="Example: STU2024001" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" name="login">Login</button>
        </form>

        <a href="index.php" class="back">← Back to Home</a>
    </div>
</body>
</html>