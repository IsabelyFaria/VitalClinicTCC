<?php

function current_user(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    if (empty($_SESSION['user_id'])) {
        $user = null;
        return null;
    }

    $candidate = repository_find_user((int) $_SESSION['user_id']);
    if (!$candidate || $candidate['status'] !== 'active' || !in_array($candidate['role'], ['admin', 'doctor'], true)) {
        unset($_SESSION['user_id']);
        $user = null;
        return null;
    }

    $user = repository_user_with_clinic($candidate);
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Entre para continuar.');
        redirect(['page' => 'login']);
    }
    return $user;
}

function require_role($roles): array
{
    $user = require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $roles, true)) {
        abort_forbidden();
    }
    return $user;
}

function login_user(string $email, string $password): bool
{
    $candidate = null;
    foreach (repository_state()['users'] as $user) {
        if (strtolower($user['email']) === strtolower($email) && $user['status'] === 'active' && in_array($user['role'], ['admin', 'doctor'], true)) {
            $candidate = $user;
            break;
        }
    }

    if (!$candidate || !password_verify($password, $candidate['password_hash'])) {
        return false;
    }

    $_SESSION['user_id'] = (int) $candidate['id'];
    repository_update_user((int) $candidate['id'], ['last_login_at' => now_sql()]);
    return true;
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
}

function register_patient(array $data): int
{
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Informe um e-mail válido.');
    }
    if (strlen($data['password']) < 6) {
        throw new RuntimeException('A senha deve ter pelo menos 6 caracteres.');
    }
    foreach (repository_state()['users'] as $user) {
        if (strtolower($user['email']) === strtolower($data['email'])) {
            throw new RuntimeException('Este e-mail já está cadastrado.');
        }
    }

    return repository_append('users', [
        'name' => $data['name'],
        'email' => strtolower($data['email']),
        'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        'role' => 'patient',
        'phone' => $data['phone'] ?: null,
        'document' => $data['document'] ?: null,
        'birth_date' => $data['birth_date'] ?: null,
        'address' => $data['address'] ?: null,
        'clinic_id' => $data['clinic_id'] ?: null,
        'status' => 'active',
        'created_at' => now_sql(),
        'updated_at' => null,
        'last_login_at' => null,
    ]);
}
