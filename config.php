<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        session_set_cookie_params([
            'lifetime' => 1800,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > 1800) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return is_string($token) && is_string($_SESSION['csrf_token'] ?? '') && hash_equals($_SESSION['csrf_token'], $token);
}

function get_env_value($key, $default = '') {
    $value = getenv($key);
    if ($value !== false && $value !== null && trim((string)$value) !== '') {
        return trim((string)$value);
    }

    $value = $_ENV[$key] ?? null;
    if (is_string($value) && trim($value) !== '') {
        return trim($value);
    }

    $envFile = __DIR__ . '/.env';
    if (is_file($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                [$name, $val] = array_pad(explode('=', $line, 2), 2, '');
                if (trim($name) === $key) {
                    $val = trim($val);
                    if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                        $val = substr($val, 1, -1);
                    }
                    return $val;
                }
            }
        }
    }

    return $default;
}

function is_password_hash($value) {
    if (!is_string($value) || $value === '') {
        return false;
    }

    return preg_match('/^\$(2[aby]|argon2)/', $value) === 1;
}

function verify_password($password, $storedHash) {
    if ($storedHash === null || $storedHash === '') {
        return false;
    }

    if (function_exists('password_verify') && is_password_hash($storedHash)) {
        return password_verify($password, $storedHash);
    }

    return hash_equals((string)$storedHash, (string)$password);
}

start_secure_session();

$db_host = get_env_value('DB_HOST', 'localhost');
$db_user = get_env_value('DB_USERNAME', 'root');
$db_pass = get_env_value('DB_PASSWORD', '');
$db_name = get_env_value('DB_NAME', 'college_system');

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>