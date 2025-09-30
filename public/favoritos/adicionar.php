<?php
// public/favoritos/adicionar.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../lib/db.php';

// Para onde voltar após a ação
$redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? '/home/main.php');

// Exige login
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
  // guarda flash e manda para login com next
  $_SESSION['flash'] = ['type' => 'danger', 'html' => 'Você precisa estar logado para favoritar.'];
  header('Location: /login/main.php?next=' . urlencode($redirect));
  exit;
}

$user   = $_SESSION['user'];
$uid    = (int)($user['id'] ?? 0);
$livroId = (int)($_POST['livro_id'] ?? 0);

// (Opcional) CSRF: valide token aqui, se estiver usando.

if ($uid <= 0 || $livroId <= 0) {
  $_SESSION['flash'] = ['type' => 'danger', 'html' => 'Não foi possível processar seu favoritamento.'];
  header('Location: ' . $redirect);
  exit;
}

try {
  $pdo = db();

  // Confere se o livro existe (recomendável)
  $check = $pdo->prepare("SELECT 1 FROM livros WHERE id = :id");
  $check->execute([':id' => $livroId]);
  if (!$check->fetchColumn()) {
    $_SESSION['flash'] = ['type' => 'danger', 'html' => 'E-book não encontrado para favoritar.'];
    header('Location: ' . $redirect);
    exit;
  }

  // Inserção idempotente
  $stmt = $pdo->prepare("
    INSERT OR IGNORE INTO user_favoritos (user_id, livro_id)
    VALUES (:uid, :lid)
  ");
  $stmt->execute([':uid' => $uid, ':lid' => $livroId]);

  $_SESSION['flash'] = [
    'type' => 'success',
    'html' => '<strong>E-book favoritado.</strong> Acesse-o pela sua <a href="/biblioteca_user/main.php" class="alert-link">biblioteca</a>.'
  ];
} catch (Throwable $e) {
  $_SESSION['flash'] = ['type' => 'danger', 'html' => 'Não foi possível processar seu favoritamento.'];
}

header('Location: ' . $redirect);
exit;
