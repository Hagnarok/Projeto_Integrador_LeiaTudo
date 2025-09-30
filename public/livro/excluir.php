<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo "Método não permitido."; exit;
  }

  $id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $csrf = $_POST['csrf'] ?? '';
  csrf_check($csrf);

  if ($id <= 0) throw new Exception("ID inválido.");

  $user = $_SESSION['user'] ?? null;
  if (!$user || !isset($user['id'])) throw new Exception("Não autenticado.");

  $pdo = db();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // 1) Buscar registro e conferir dono
  $stmt = $pdo->prepare("SELECT id, pdf_path, capa_path, criado_por_id FROM livros WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => $id]);
  $livro = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$livro) throw new Exception("Livro não encontrado.");

  if ((string)$livro['criado_por_id'] !== (string)$user['id']) {
    throw new Exception("Sem permissão para excluir este livro.");
  }

  // 2) Apagar arquivos (com segurança de caminho)
  $paths = [];
  foreach (['pdf_path','capa_path'] as $k) {
    $web = (string)($livro[$k] ?? '');
    if ($web && strpos($web, '/public/uploads/') === 0) {
      $abs = __DIR__ . "/.." . substr($web, strlen('/public')); // resolve caminho físico
      $paths[] = $abs;
    }
  }
  foreach ($paths as $abs) {
    if (is_file($abs)) @unlink($abs);
  }

  // 3) Remover do banco
  $del = $pdo->prepare("DELETE FROM livros WHERE id = :id");
  $del->execute([':id' => $id]);

  // 4) Redirecionar
  header('Location: /home/main.php?del=ok');
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  echo "<h2>Erro ao excluir</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
