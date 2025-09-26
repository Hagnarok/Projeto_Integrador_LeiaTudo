<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/../../home/config.php';

function backWith(array $errors, array $old = []): void {
  $_SESSION['errors'] = $errors;
  $_SESSION['old']    = $old;
  header('Location: main.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  backWith(['Método inválido.']);
}

$usuario = trim($_POST['usuario'] ?? '');
$senha   = $_POST['senha'] ?? '';

if ($usuario === '' || $senha === '') {
  backWith(['Preencha todos os campos.'], ['usuario' => $usuario]);
}

try {
  $pdo = get_pdo();
} catch (Throwable $e) {
  backWith(['Erro ao conectar ao banco.']);
}

// Pode logar com username ou e-mail
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
$stmt->execute([$usuario, $usuario]);
$user = $stmt->fetch();

if (!$user || !password_verify($senha, $user['password_hash'])) {
  backWith(['Usuário/e-mail ou senha incorretos.'], ['usuario' => $usuario]);
}

// Login OK → cria sessão
$_SESSION['user'] = [
  'id'       => $user['id'],
  'username' => $user['username'],
  'email'    => $user['email'],
];

// Redireciona para a home (ou dashboard protegido)
header('Location: /home/main.php');
exit;
