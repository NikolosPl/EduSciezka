<?php

function edusciezka_load_env_file($path)
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $variables = parse_ini_file($path, false, INI_SCANNER_RAW);
    if ($variables === false) {
        return;
    }

    foreach ($variables as $key => $value) {
        if (getenv($key) === false || getenv($key) === '') {
            putenv($key . '=' . $value);
        }
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function edusciezka_env($key, $default = null)
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }

    return $default;
}

edusciezka_load_env_file(dirname(__DIR__, 2) . '/.env');

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; style-src-elem 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline'; font-src 'self' data: https://fonts.gstatic.com; form-action 'self'; base-uri 'self'");
}

function edusciezka_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function edusciezka_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function edusciezka_csrf_input()
{
    return '<input type="hidden" name="csrf_token" value="' . edusciezka_e(edusciezka_csrf_token()) . '">';
}

function edusciezka_csrf_url($url)
{
    $separator = strpos($url, '?') === false ? '?' : '&';
    return $url . $separator . 'csrf_token=' . rawurlencode(edusciezka_csrf_token());
}

function edusciezka_validate_csrf($token)
{
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $token);
}

function edusciezka_require_csrf()
{
    $token = null;
    if (isset($_POST['csrf_token'])) {
        $token = (string) $_POST['csrf_token'];
    } elseif (isset($_GET['csrf_token'])) {
        $token = (string) $_GET['csrf_token'];
    }

    if (!edusciezka_validate_csrf($token)) {
        http_response_code(403);
        die('Nieprawidlowy token CSRF.');
    }
}

function edusciezka_request_ip()
{
    return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
}

function edusciezka_rate_limit($bucket, $maxAttempts, $windowSeconds)
{
    $safeBucket = preg_replace('/[^a-zA-Z0-9_\-:.]/', '_', (string) $bucket);
    $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'edusciezka-rate-limit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $path = $dir . DIRECTORY_SEPARATOR . sha1($safeBucket) . '.json';
    $now = time();
    $attempts = array();

    $handle = @fopen($path, 'c+');
    if (!$handle) {
        return array('allowed' => true, 'retry_after' => 0, 'remaining' => $maxAttempts);
    }

    try {
        if (flock($handle, LOCK_EX)) {
            $raw = stream_get_contents($handle);
            if ($raw !== false && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $attempts = $decoded;
                }
            }

            $attempts = array_values(array_filter($attempts, function ($timestamp) use ($now, $windowSeconds) {
                return is_numeric($timestamp) && ((int) $timestamp) > ($now - $windowSeconds);
            }));

            if (count($attempts) >= $maxAttempts) {
                $oldest = min($attempts);
                $retryAfter = max(1, ($oldest + $windowSeconds) - $now);
                flock($handle, LOCK_UN);
                fclose($handle);
                return array('allowed' => false, 'retry_after' => $retryAfter, 'remaining' => 0);
            }

            $attempts[] = $now;
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($attempts));
            fflush($handle);
            flock($handle, LOCK_UN);
            fclose($handle);

            return array('allowed' => true, 'retry_after' => 0, 'remaining' => max(0, $maxAttempts - count($attempts)));
        }
    } catch (Throwable $e) {
    }

    fclose($handle);
    return array('allowed' => true, 'retry_after' => 0, 'remaining' => $maxAttempts);
}
