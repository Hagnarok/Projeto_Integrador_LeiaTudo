<?php
declare(strict_types=1);
session_start();

// --- Recupera mensagens flash da sessão ---
$errors  = $_SESSION['errors']  ?? [];
$old     = $_SESSION['old']     ?? [];
$success = $_SESSION['success'] ?? null;

// --- Gera token CSRF (mantém compatibilidade com public/lib/csrf.php) ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- Limpa as mensagens antigas ---
unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Esqueceu a senha - LeiaTudo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/public/css/default.css?v=2">
</head>
<body class="home-login bg-light">

  <!-- Logo -->
  <header class="container text-center mt-4">
    <a class="navbar-brand" href="/home/main.php">
      <img src="/public/css/imgs/logo.png" alt="LeiaTudo" width="120" height="120">
    </a>
  </header>

  <!-- Conteúdo principal -->
  <main class="container mt-5" style="max-width: 480px;">
    <h2 class="text-center mb-4">Recuperar Senha</h2>

    <?php if ($success): ?>
      <div class="alert alert-success text-center">
        <?= htmlspecialchars($success) ?>
      </div>
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

    <!-- Formulário de recuperação -->
    <form action="processa_esqueceu.php" method="POST" novalidate>
      <!-- Token CSRF -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-control"
          required
          placeholder="Digite seu e-mail cadastrado"
          value="<?= htmlspecialchars($old['email'] ?? '') ?>"
        >
      </div>

      <div class="d-flex justify-content-center gap-3">
        <button type="submit" class="btn btn-primary btn-lg">Enviar E-mail</button>
        <a href="/login/main.php" class="btn btn-outline-secondary btn-lg">Voltar</a>
      </div>
    </form>
  </main>

</body>
</html>
