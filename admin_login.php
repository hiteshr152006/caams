<?php
include 'config.php';

$error = "";

if (isset($_POST['login'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && verify_password($password, $admin['password'])) {
            if (!is_password_hash($admin['password'])) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upgradeStmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
                $upgradeStmt->bind_param('si', $newHash, $admin['id']);
                $upgradeStmt->execute();
            }

            session_regenerate_id(true);
            $_SESSION['admin'] = $admin['username'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: var(--bg);
            color: var(--text);
            padding: 20px 14px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
        .login-box {
            background-color: #1a1a1a;
            padding: 36px 28px;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            border: 1px solid #333;
            box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        .login-box h2 {
            text-align: center;
            color: #00d4ff;
            margin-bottom: 24px;
            font-size: 1.5rem;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #ccc;
            font-size: 0.9rem;
            font-weight: 500;
        }
        input {
            width: 100%;
            max-width: 100%;
            padding: 12px 14px;
            background-color: #0f0f0f;
            border: 1px solid #444;
            border-radius: 8px;
            color: white;
            font-size: 16px; /* Prevents auto-zoom on mobile */
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        input:focus {
            outline: none;
            border-color: #00d4ff;
        }
        button {
            width: 100%;
            padding: 13px;
            background-color: #00d4ff;
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background-color: #00b8e0;
        }
        .error {
            color: #ff4d4d;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(255, 77, 77, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(255, 77, 77, 0.25);
            font-size: 0.9rem;
        }
        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #00d4ff;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .back:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            body {
                padding: 14px 10px;
                display: block;
            }
            .login-box {
                padding: 22px 16px;
                margin: 20px auto;
                border-radius: 12px;
            }
            .login-box h2 {
                font-size: 1.35rem;
                margin-bottom: 18px;
            }
            button {
                padding: 12px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>

        <?php if ($error != "") { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
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