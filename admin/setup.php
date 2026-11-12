<?php
declare(strict_types=1);
$dataDir = dirname(__DIR__) . '/data';
$file = $dataDir . '/admins.json';
if (!is_dir($dataDir)) mkdir($dataDir, 0750, true);
$admins = [];
if (is_file($file)) {
    $decoded = json_decode((string)file_get_contents($file), true);
    if (is_array($decoded)) $admins = $decoded;
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && count($admins) === 0) {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
        $message = 'Enter a valid name/email and a password of at least 10 characters.';
    } else {
        $admins[] = ['id'=>bin2hex(random_bytes(8)), 'name'=>$name, 'email'=>$email,
            'password_hash'=>password_hash($password, PASSWORD_DEFAULT), 'created_at'=>date(DATE_ATOM)];
        $json = json_encode($admins, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($file, $json . PHP_EOL, LOCK_EX) !== false) {
            header('Location: index.php?setup=success'); exit;
        }
        $admins = []; $message = 'Could not save admin. Check data folder permissions.';
    }
}
$locked = count($admins) > 0;
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Alberto Admin Setup</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>
body{min-height:100vh;background:#101a15;color:#fff;display:grid;place-items:center;font-family:Arial,sans-serif}.setup{width:min(92%,480px);background:#1b2b21;border:1px solid #b99a66;padding:32px}.setup h1{font-family:Georgia,serif}.gold{color:#d8bb87}.form-control{border-radius:0;background:#101a15;border-color:#526052;color:white}.btn-gold{background:#c9a66b;border:0;border-radius:0;padding:12px;font-weight:bold}
</style></head><body><main class="setup"><p class="gold small">ALBERTO WATCH COMPANY</p><h1>Admin <span class="gold">setup.</span></h1>
<?php if ($locked): ?><div class="alert alert-success">Admin account already exists. Setup is locked for security.</div><a class="btn btn-gold w-100" href="index.php">Go to Admin Login</a>
<?php else: ?><p class="text-secondary">Create the first administrator account. This page locks automatically after setup.</p>
<?php if ($message): ?><div class="alert alert-danger"><?=htmlspecialchars($message, ENT_QUOTES, 'UTF-8')?></div><?php endif; ?>
<form method="post"><label class="form-label mt-2">Admin name</label><input class="form-control" name="name" required minlength="2"><label class="form-label mt-3">Admin email</label><input class="form-control" type="email" name="email" required><label class="form-label mt-3">Password (minimum 10 characters)</label><input class="form-control" type="password" name="password" minlength="10" required><button class="btn btn-gold w-100 mt-4">Create admin account</button></form><?php endif; ?></main></body></html>
