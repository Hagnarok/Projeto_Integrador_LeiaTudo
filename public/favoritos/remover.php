<?php
// public/favoritos/remover.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../lib/db.php';

// Para onde voltar após a ação
$redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? '/home/main.php');

// Exige login
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
  $_SESSION['flash'] = ['type' => 'danger', 'html' => 'Você precisa estar logado para desfavoritar.'];
  header('Location: /login/main.php?next=' . urlencode($redirect));
  exit;
}

$user    = $_SESSION['user'];
$uid     = (int)($user['id'] ?? 0);
$livroId = (int)($_POST['livro_id'] ?? 0);

if ($uid <= 0 || $livroId <= 0) {
  $_SESSION['flash'] = ['type' => 'danger', 'html' => 'Não foi possível remover dos favoritos.'];
  header('Location: ' . $redirect);
  exit;
}

try {
  $pdo = db();
  $del = $pdo->prepare("DELETE FROM user_favoritos WHERE user_id = :uid AND livro_id = :lid");
  $del->execute([':uid' => $uid, ':lid' => $livroId]);

  $_SESSION['flash'] = ['type' => 'warning', 'html' => 'E-book removido dos favoritos.'];
} catch (Throwable $e) {
  $_SESSION['flash'] = ['type' => 'danger', 'html' => 'Não foi possível remover dos favoritos.'];
}

header('Location: ' . $redirect);
exit;
