<?php
/**
 * Reusable Helper Functions
 * Watch Collection - College Project
 */

/**
 * Sanitize a single input value (trim, strip tags, convert special chars).
 */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize an array of inputs (e.g. $_POST).
 */
function sanitize_array(array $data): array
{
    $clean = [];
    foreach ($data as $key => $value) {
        $clean[$key] = is_array($value) ? sanitize_array($value) : sanitize((string) $value);
    }
    return $clean;
}

/**
 * Redirect to a path relative to BASE_URL and stop execution.
 */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

/**
 * Queue a one-time flash message shown on the next page load.
 * $type: 'success' | 'error' | 'info' | 'warning'
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message, if any.
 */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate (or reuse) a CSRF token for the session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token.
 */
function csrf_verify(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Build a full asset/base URL from a relative path.
 */
function base_url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}