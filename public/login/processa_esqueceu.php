<?php
declare(strict_types=1);
session_start();

// --- Inclui Composer autoload (PHPMailer) ---
require __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Inclui banco de dados ---
require __DIR__ . '/../../home/config.php';

// --- Inclui funções CSRF ---
require __DIR__ . '/../lib/csrf.php';

// --- Verifica token CSRF ---
if (!isset($_POST['csrf_token'])) {
    $_SESSION['errors'] = ['Requisição inválida.'];
    header('Location: esqueceu.php');
    exit;
}
csrf_check($_POST['csrf_token']);

// --- Captura e validação do e-mail ---
$email = trim($_POST['email'] ?? '');
$errors = [];

// Valida e-mail
if ($email === '') {
    $errors[] = 'O campo e-mail é obrigatório.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Digite um e-mail válido.';
}

$_SESSION['old'] = ['email' => $email];

if ($errors) {
    $_SESSION['errors'] = $errors;
    header('Location: esqueceu.php');
    exit;
}

// --- Conexão com o banco ---
$pdo = get_pdo();

// --- Cria tabela password_resets se não existir ---
$pdo->exec("
    CREATE TABLE IF NOT EXISTS password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT NOT NULL,
        expiracao TEXT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

// --- Busca usuário pelo e-mail ---
$stmt = $pdo->prepare('SELECT id, username FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['errors'] = ['E-mail não encontrado em nossa base de dados.'];
    header('Location: esqueceu.php');
    exit;
}

// --- Gera token e salva no banco ---
$token = bin2hex(random_bytes(32));
$expiracao = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

// Remove tokens antigos
$pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);

// Insere novo token
$stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expiracao) VALUES (?, ?, ?)');
$stmt->execute([$user['id'], $token, $expiracao]);

// --- Gera link de redefinição ---
$resetLink = "http://localhost/public/login/resetar_senha.php?token=$token";

// --- Envia e-mail usando PHPMailer ---
$mail = new PHPMailer(true);

try {
    // Configura SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';   // ou 'smtp.office365.com' para Hotmail
    $mail->SMTPAuth   = true;
    $mail->Username   = 'leiatudointegrador@gmail.com';           // 🔧 seu e-mail
    $mail->Password   = 'ehke hggb yphb yvdn';   // 🔧 senha de app
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Remetente e destinatário
    $mail->setFrom('no-reply@leiatudo.com', 'LeiaTudo');
    $mail->addAddress($email, $user['username']);

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Redefinicao de senha - LeiaTudo';
    $mail->Body = "
        <p>Olá <strong>{$user['username']}</strong>,</p>
        <p>Você solicitou a redefinição da sua senha.</p>
        <p>Clique no link abaixo para redefinir sua senha:</p>
        <p><a href='$resetLink'>$resetLink</a></p>
        <p>O link é válido por 1 hora. Caso não tenha solicitado, ignore este e-mail.</p>
    ";

    $mail->send();
    $_SESSION['success'] = 'Um e-mail com as instruções foi enviado.';
} catch (Exception $e) {
    $_SESSION['errors'] = ['Falha ao enviar o e-mail: ' . htmlspecialchars($mail->ErrorInfo)];
}

// --- Redireciona de volta ---
header('Location: esqueceu.php');
exit;
