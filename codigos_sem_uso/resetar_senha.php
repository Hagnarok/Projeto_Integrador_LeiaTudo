<?php
declare(strict_types=1);
session_start();

// Inclui banco de dados
require __DIR__ . '/../../home/config.php';
// Inclui funções CSRF
require __DIR__ . '/../lib/csrf.php';

$pdo = get_pdo();
$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? null;
unset($_SESSION['errors'], $_SESSION['success']);

// Gera token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Pega token da URL
$token = $_GET['token'] ?? '';
if (!$token) {
    die('Token inválido.');
}

// Verifica token no banco
$stmt = $pdo->prepare("SELECT pr.user_id, u.username FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.expiracao >= datetime('now','localtime')");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die('Token inválido ou expirado.');
}

// Processa formulário de redefinição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica CSRF
    csrf_check($_POST['csrf_token'] ?? '');

    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    $errors = [];
    if (strlen($senha) < 8) {
        $errors[] = 'Senha deve ter no mínimo 8 caracteres.';
    }
    if ($senha !== $confirmar) {
        $errors[] = 'As senhas não coincidem.';
    }

    if (!$errors) {
        // Atualiza senha
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([$hash, $user['user_id']]);
        // Remove token
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")
            ->execute([$user['user_id']]);
        $_SESSION['success'] = 'Senha redefinida com sucesso. Você já pode fazer login.';
        header('Location: /login/main.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Redefinir Senha - LeiaTudo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/public/css/default.css?v=2">
</head>
<body class="home-login bg-light">
  <div class="container mt-5" style="max-width: 480px;">
    <h2 class="text-center mb-4">Redefinir Senha</h2>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="mb-3">
        <label for="senha" class="form-label">Nova senha</label>
        <input type="password" name="senha" id="senha" class="form-control" required minlength="8" placeholder="Nova senha">
      </div>

      <div class="mb-3">
        <label for="confirmar_senha" class="form-label">Confirme a senha</label>
        <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required minlength="8" placeholder="Repita a senha">
      </div>

      <div class="d-flex justify-content-center gap-3">
        <button type="submit" class="btn btn-primary btn-lg">Redefinir Senha</button>
        <a href="/login/main.php" class="btn btn-outline-secondary btn-lg">Voltar</a>
      </div>
    </form>
  </div>
</body>
</html>
