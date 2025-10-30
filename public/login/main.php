<?php
declare(strict_types=1);
session_start();

$errors  = $_SESSION['errors']  ?? [];
$old     = $_SESSION['old']     ?? [];
$success = $_SESSION['success'] ?? null;

unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Login - LeiaTudo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/public/css/default.css?v=2">
</head>
<body class="home-login bg-light">
  <div class="container text-center mt-4">
    <a class="navbar-brand" href="/home/main.php">
      <img src="/public/css/imgs/logo.png" alt="LeiaTudo" width="120" height="120">
    </a>
  </div>

  <div class="container mt-5" style="max-width: 480px;">
    <h2 class="text-center mb-4">Login</h2>

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

    <!-- Evita autofill agressivo: form autocomplete off e campos com atributos
         explícitos. Também adicionamos um campo escondido para capturar
         autofill de navegadores que ignoram autocomplete="off". -->
    <form action="/login/processa_login.php" method="POST" novalidate autocomplete="off">

      <!-- campo "falso" para reduzir autofill indesejado -->
      <input type="text" name="fake_username" id="fake_username" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;opacity:0;" autocomplete="username">

      <div class="mb-3">
        <label for="usuario" class="form-label">Usuário ou E-mail</label>
        <input type="text" name="usuario" id="usuario" class="form-control" required
               value="<?= htmlspecialchars($old['usuario'] ?? '') ?>"
               autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false"
               readonly onfocus="this.removeAttribute('readonly');">
      </div>

      <div class="mb-3">
        <label for="senha" class="form-label">Senha</label>
        <input type="password" name="senha" id="senha" class="form-control" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
      </div>

      <script>
        // tenta limpar valores que alguns navegadores já preencheram
        document.addEventListener('DOMContentLoaded', function(){
          try {
            var u = document.getElementById('usuario');
            var p = document.getElementById('senha');
            if (u && u.getAttribute('autocomplete') === 'off') u.value = '';
            if (p && p.getAttribute('autocomplete') === 'new-password') p.value = '';
          } catch(e){}
        });
      </script>

      <!--
      <div class="mb-3 d-flex justify-content-end">
        <a href="/login/esqueceu.php" class="small text-decoration-none">Esqueceu a senha?</a>
      </div>
      -->
      
      <div class="d-flex justify-content-center gap-3">
        <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
        <a href="/registro/main.php" class="btn btn-outline-secondary btn-lg">Cadastrar-se</a>
      </div>
    </form>
  </div>
</body>
</html>
