<?php
// public/registro/processa_registro.php
declare(strict_types=1);

session_start();

// Carrega sua conexão SQLite (ajuste o caminho se necessário)
require __DIR__ . '/../../home/config.php';

/* ===== Helpers ===== */
function normalizarCpf(string $cpf): string {
  return preg_replace('/\D+/', '', $cpf) ?? '';
}
function validarUsuario(string $u): bool {
  return (bool)preg_match('/^[A-Za-z0-9._-]{3,32}$/', $u);
}
function validarCpf(string $cpf): bool {
  $cpf = normalizarCpf($cpf);
  if (strlen($cpf) !== 11) return false;
  if (preg_match('/^(\\d)\\1{10}$/', $cpf)) return false;
  $a = array_map('intval', str_split($cpf));
  // DV1
  $s = 0; for ($i=0,$p=10;$i<9;$i++,$p--) $s += $a[$i]*$p;
  $d1 = $s % 11; $d1 = ($d1 < 2) ? 0 : 11 - $d1;
  if ($d1 !== $a[9]) return false;
  // DV2
  $s = 0; for ($i=0,$p=11;$i<10;$i++,$p--) $s += $a[$i]*$p;
  $d2 = $s % 11; $d2 = ($d2 < 2) ? 0 : 11 - $d2;
  return $d2 === $a[10];
}
function validarSenha(string $s): bool {
  return strlen($s) >= 8;
}
function backWith(array $errors, array $old = []): void {
  $_SESSION['errors'] = $errors;
  $_SESSION['old']    = $old;
  header('Location: main.php'); // volta ao formulário
  exit;
}

/* ===== Fluxo ===== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  backWith(['Método inválido.']);
}

$usuario        = trim($_POST['usuario'] ?? '');
$cpfRaw         = trim($_POST['cpf'] ?? '');
$email          = trim($_POST['email'] ?? '');
$senha          = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

$old = [
  'usuario' => $usuario,
  'cpf'     => $cpfRaw,   // mantém como digitado (com ou sem máscara)
  'email'   => $email,
];

$errors = [];

// Validações acumuladas (para mostrar tudo de uma vez)
if (!validarUsuario($usuario)) {
  $errors[] = 'Nome de usuário inválido. Use 3–32 caracteres (letras, números, ponto, hífen ou _).';
}
$cpf = normalizarCpf($cpfRaw);
if (!validarCpf($cpf)) {
  $errors[] = 'CPF inválido. Verifique os dígitos e tente novamente.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'E-mail inválido.';
}
if (!validarSenha($senha)) {
  $errors[] = 'Senha inválida. Use pelo menos 8 caracteres.';
}
if ($senha !== $confirmarSenha) {
  $errors[] = 'As senhas não coincidem.';
}

if ($errors) {
  backWith($errors, $old);
}

// Conecta ao SQLite
try {
  $pdo = get_pdo();
} catch (Throwable $e) {
  backWith(['Falha ao abrir o banco de dados (PDO_SQLite).'], $old);
}

// Checa duplicidades
try {
  $sel = $pdo->prepare("SELECT username, cpf, email FROM users WHERE username = ? OR cpf = ? OR email = ? LIMIT 1");
  $sel->execute([$usuario, $cpf, $email]);
  if ($row = $sel->fetch()) {
    if (strcasecmp($row['username'], $usuario) === 0) $errors[] = 'Este nome de usuário já está em uso.';
    if ($row['cpf'] === $cpf)                           $errors[] = 'Este CPF já está cadastrado.';
    if (strcasecmp($row['email'], $email) === 0)        $errors[] = 'Este e-mail já está cadastrado.';
  }
} catch (Throwable $e) {
  $errors[] = 'Erro ao verificar duplicidades.';
}

if ($errors) {
  backWith($errors, $old);
}

// Insere
$hash = password_hash($senha, PASSWORD_DEFAULT);
try {
  $ins = $pdo->prepare("INSERT INTO users (username, cpf, email, password_hash) VALUES (?,?,?,?)");
  $ins->execute([$usuario, $cpf, $email, $hash]);
} catch (Throwable $e) {
  backWith(['Erro ao cadastrar. Tente novamente.'], $old);
}

// Sucesso → flash + redirect para o próprio form (ou para login)
$_SESSION['success'] = 'Conta criada com sucesso! Você já pode fazer login.';
header('Location: main.php');  // ou: header('Location: /login/main.php');
exit;
