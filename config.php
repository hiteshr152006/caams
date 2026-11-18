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

function execute_prepared_query($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $bindings = [$types];
        foreach ($params as &$param) {
            $bindings[] = &$param;
        }
        call_user_func_array([$stmt, 'bind_param'], $bindings);
    }

    $stmt->execute();
    return $stmt;
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

function get_app_base_url() {
    $configured = rtrim(get_env_value('APP_BASE_URL'), '/');
    if ($configured !== '') {
        if (filter_var($configured, FILTER_VALIDATE_URL) === false || !in_array(parse_url($configured, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new RuntimeException('APP_BASE_URL must be a valid HTTP or HTTPS URL.');
        }

        return $configured;
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!is_string($host) || !preg_match('/\A(?:[A-Za-z0-9.-]+|\[[0-9A-Fa-f:]+\])(?::\d{1,5})?\z/', $host)) {
        throw new RuntimeException('Unable to determine a safe application URL. Set APP_BASE_URL in .env.');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $directory = $directory === '/' || $directory === '.' ? '' : '/' . trim($directory, '/');

    return $scheme . '://' . $host . $directory;
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

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Database unavailable. Check the MySQL service and database configuration, then reload the page.');
}
?>