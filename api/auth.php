<?php
declare(strict_types=1);
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
]);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function respond(bool $success, string $message = '', ?array $user = null, int $status = 200): never {
    http_response_code($status);
    echo json_encode(['success'=>$success, 'message'=>$message, 'user'=>$user], JSON_UNESCAPED_SLASHES);
    exit;
}
function read_json_file(string $file): array {
    if (!is_file($file)) return [];
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') return [];
    $value = json_decode($raw, true);
    return is_array($value) ? $value : [];
}
function save_json_file(string $file, array $value): bool {
    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;
    $fp = fopen($file, 'c+');
    if (!$fp) return false;
    try {
        if (!flock($fp, LOCK_EX)) return false;
        ftruncate($fp, 0); rewind($fp);
        $ok = fwrite($fp, $json . PHP_EOL) !== false;
        fflush($fp); flock($fp, LOCK_UN);
        return $ok;
    } finally { fclose($fp); }
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'POST requests only.', null, 405);
$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) respond(false, 'Invalid request data.', null, 400);
$action = (string)($input['action'] ?? '');
$dataDir = dirname(__DIR__) . '/data';
if (!is_dir($dataDir)) mkdir($dataDir, 0750, true);
$userFile = $dataDir . '/users.json';
$adminFile = $dataDir . '/admins.json';

if ($action === 'status') {
    respond(true, '', isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null);
}
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], '', $p['secure'], $p['httponly']);
    }
    session_destroy();
    respond(true, 'Logged out successfully.');
}
if ($action === 'register') {
    $name = trim((string)($input['name'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');
    if (strlen($name) < 2 || strlen($name) > 240) respond(false, 'Name must be between 2 and 80 characters.', null, 422);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, 'Please enter a valid email address.', null, 422);
    if (strlen($password) < 8 || strlen($password) > 200) respond(false, 'Password must be at least 8 characters.', null, 422);
    foreach (read_json_file($userFile) as $existing) {
        if (strtolower((string)($existing['email'] ?? '')) === $email) respond(false, 'This email is already registered. Please login.', null, 409);
    }
    foreach (read_json_file($adminFile) as $existing) {
        if (strtolower((string)($existing['email'] ?? '')) === $email) respond(false, 'This email is reserved for an administrator.', null, 409);
    }
    $users = read_json_file($userFile);
    $users[] = ['id'=>bin2hex(random_bytes(8)), 'name'=>$name, 'email'=>$email,
        'password_hash'=>password_hash($password, PASSWORD_DEFAULT), 'created_at'=>date(DATE_ATOM)];
    if (!save_json_file($userFile, $users)) respond(false, 'Could not save account. Check data folder permissions.', null, 500);
    respond(true, 'Account created successfully. Please login.', null, 201);
}
if ($action === 'login') {
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');
    foreach (read_json_file($adminFile) as $account) {
        if (strtolower((string)($account['email'] ?? '')) === $email &&
            isset($account['password_hash']) && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['name'=>$account['name'], 'email'=>$account['email'], 'role'=>'admin'];
            respond(true, 'Admin login successful.', $_SESSION['user']);
        }
    }
    foreach (read_json_file($userFile) as $account) {
        if (strtolower((string)($account['email'] ?? '')) === $email &&
            isset($account['password_hash']) && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['name'=>$account['name'], 'email'=>$account['email'], 'role'=>'customer'];
            respond(true, 'Login successful.', $_SESSION['user']);
        }
    }
    respond(false, 'Incorrect email or password.', null, 401);
}
respond(false, 'Unknown action.', null, 400);
