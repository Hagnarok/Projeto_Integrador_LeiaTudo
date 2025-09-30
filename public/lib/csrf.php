<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}
function csrf_check(string $token): void {
  if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo "CSRF inválido.";
    exit;
  }
}
