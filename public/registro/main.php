<?php
// public/registro/main.php
declare(strict_types=1);
session_start();

// Pega e limpa os flashes
$errors  = $_SESSION['errors']  ?? [];
$old     = $_SESSION['old']     ?? [];
$success = $_SESSION['success'] ?? null;

unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Registro - LeiaTudo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/public/css/default.css?v=2">
</head>
<body class="home-registro bg-light">
  <div class="container text-center mt-4">
    <a class="navbar-brand" href="/home/main.php">
      <img src="/public/css/imgs/logo.png" alt="LeiaTudo" width="120" height="120">
    </a>
  </div>

  <div class="container mt-5" style="max-width: 640px;">
    <h2 class="text-center mb-4">Cadastre-se</h2>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <strong>Corrija os itens abaixo:</strong>
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="processa_registro.php" method="POST" novalidate>
      <div class="mb-3">
        <label for="usuario" class="form-label">Nome de usuário</label>
        <input
          type="text"
          name="usuario"
          id="usuario"
          class="form-control"
          required
          minlength="3"
          maxlength="15"
          autocomplete="username"
          placeholder="Ex.: Gustavo"
          value="<?= htmlspecialchars($old['usuario'] ?? '') ?>"
        >
        <div class="form-text">3–15 caracteres (letras, números, ponto, hífen ou _).</div>
      </div>

      <div class="mb-3">
        <label for="cpf" class="form-label">CPF</label>
        <input
          type="text"
          name="cpf"
          id="cpf"
          class="form-control"
          required
          inputmode="numeric"
          pattern="^(\d{11}|\d{3}\.\d{3}\.\d{3}-\d{2})$"
          placeholder="Somente números (11) ou 000.000.000-00"
          value="<?= htmlspecialchars($old['cpf'] ?? '') ?>"
        >
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input
          type="email"
          name="email"
          id="email"
          class="form-control"
          required
          maxlength="254"
          autocomplete="email"
          placeholder="voce@exemplo.com"
          value="<?= htmlspecialchars($old['email'] ?? '') ?>"
        >
      </div>

      <div class="mb-3">
        <label for="senha" class="form-label">Senha</label>
        <input
          type="password"
          name="senha"
          id="senha"
          class="form-control"
          required
          minlength="8"
          autocomplete="new-password"
          placeholder="Mínimo 8 caracteres"
        >
      </div>

      <div class="mb-4">
        <label for="confirmar_senha" class="form-label">Confirme a senha</label>
        <input
          type="password"
          name="confirmar_senha"
          id="confirmar_senha"
          class="form-control"
          required
          autocomplete="new-password"
          placeholder="Repita a mesma senha"
        >
      </div>

      <div class="d-flex justify-content-center gap-3">
        <button type="submit" class="btn btn-primary btn-lg">Cadastrar</button>
        <a href="/login/main.php" class="btn btn-outline-secondary btn-lg">Já tenho conta</a>
      </div>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
