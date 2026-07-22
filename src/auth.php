<?php

function signup(PDO $pdo, string $email, string $password): array
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'error' => 'invalid_email'];
    if (strlen($password) < 8) return ['ok' => false, 'error' => 'password_too_short'];
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([$email]);
    if ($st->fetch()) return ['ok' => false, 'error' => 'email_taken'];

    $token = bin2hex(random_bytes(32));
    for ($try = 0; $try < 5; $try++) {                    // handle collisions are rare; retry
        $handle = generate_handle();
        try {
            $pdo->prepare('INSERT INTO users (email, password_hash, handle, confirm_token)
                VALUES (?,?,?,?)')
                ->execute([$email, password_hash($password, PASSWORD_BCRYPT), $handle, $token]);
            return ['ok' => true, 'user_id' => (int)$pdo->lastInsertId(), 'confirm_token' => $token];
        } catch (PDOException $e) {
            if (!str_contains($e->getMessage(), 'handle')) throw $e;
        }
    }
    return ['ok' => false, 'error' => 'try_again'];
}

function confirm_email(PDO $pdo, string $token): bool
{
    $st = $pdo->prepare('UPDATE users SET email_verified_at = NOW(), confirm_token = NULL
        WHERE confirm_token = ? AND email_verified_at IS NULL');
    $st->execute([$token]);
    return $st->rowCount() === 1;
}

function attempt_login(PDO $pdo, string $email, string $password): ?array
{
    $st = $pdo->prepare('SELECT * FROM users WHERE email = ? AND banned_at IS NULL');
    $st->execute([strtolower(trim($email))]);
    $u = $st->fetch();
    return ($u && password_verify($password, $u['password_hash'])) ? $u : null;
}

function current_user(): ?array
{
    if (empty($_SESSION['uid'])) return null;
    static $cacheUid = null, $cacheUser = null;
    if ($cacheUid !== (int)$_SESSION['uid']) {
        $st = db()->prepare('SELECT * FROM users WHERE id = ? AND banned_at IS NULL');
        $st->execute([$_SESSION['uid']]);
        $cacheUser = $st->fetch() ?: null;
        $cacheUid = (int)$_SESSION['uid'];
    }
    return $cacheUser;
}

function require_verified_user(): array
{
    $u = current_user();
    if (!$u || !$u['email_verified_at']) redirect('/login');
    return $u;
}

function require_verified_user_json(): array
{
    $u = current_user();
    if (!$u || !$u['email_verified_at']) json_out(['ok' => false, 'error' => t('err_login_required')], 401);
    return $u;
}

function require_admin(): array
{
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') { http_response_code(404); echo '404'; exit; }
    return $u;
}

function reset_start(PDO $pdo, string $email): ?string
{
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ? AND banned_at IS NULL');
    $st->execute([strtolower(trim($email))]);
    if (!$st->fetch()) return null;
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('UPDATE users SET reset_token = ?,
        reset_expires_at = NOW() + INTERVAL 1 HOUR WHERE email = ?')
        ->execute([$token, strtolower(trim($email))]);
    return $token;
}

function reset_finish(PDO $pdo, string $token, string $newPassword): bool
{
    if (strlen($newPassword) < 8 || $token === '') return false;
    $st = $pdo->prepare('UPDATE users SET password_hash = ?, reset_token = NULL,
        reset_expires_at = NULL
        WHERE reset_token = ? AND reset_expires_at > NOW()');
    $st->execute([password_hash($newPassword, PASSWORD_BCRYPT), $token]);
    return $st->rowCount() === 1;
}
