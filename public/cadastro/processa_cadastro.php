<?php
// public/cadastro/processa_cadastro.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../lib/db.php';

try {
  // Usuário logado (pode ser null)
  $sessUser = $_SESSION['user'] ?? null;
  $uid      = isset($sessUser['id']) ? (int)$sessUser['id'] : null;
  // tenta vários campos comuns: username, login, email
  $uname    = $sessUser['username'] ?? ($sessUser['login'] ?? ($sessUser['email'] ?? null));
  if (is_array($uname)) $uname = null;

  // Campos
  $titulo    = trim($_POST['titulo'] ?? '');
  $autor     = trim($_POST['autor'] ?? '');
  $genero    = trim($_POST['genero'] ?? '');
  $publicadoPor = trim($_POST['publicado_por'] ?? '');
  $descricao = trim($_POST['descricao'] ?? '');

  // Se o campo publicado_por não foi enviado, tenta usar o username da sessão
  if ($publicadoPor === '' && is_string($uname) && $uname !== '') {
    $publicadoPor = $uname;
  }

  if ($titulo === '' || $autor === '' || $genero === '' || $publicadoPor === '' ||
      !isset($_FILES['pdf']) || !isset($_FILES['capa'])) {
    throw new Exception('Campos obrigatórios ausentes.');
  }
  // Limita tamanho do campo publicado_por
  $publicadoPor = substr($publicadoPor, 0, 255);

  // Pastas
  $baseUploads = __DIR__ . '/../uploads';
  $dirPdfs  = $baseUploads . '/pdfs';
  $dirCapas = $baseUploads . '/capas';
  foreach ([$baseUploads, $dirPdfs, $dirCapas] as $d) {
    if (!is_dir($d) && !mkdir($d, 0775, true)) {
      throw new Exception("Não foi possível criar a pasta: $d");
    }
  }

  // Nomes
  $slugBase = preg_replace('/[^a-z0-9]+/i', '_', strtolower($titulo));
  $uniq     = time() . '_' . mt_rand(1000, 9999);

  // ===== PDF =====
  $pdfError = $_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE;
  $uploadErrorMap = [
    UPLOAD_ERR_OK => 'Sem erro.',
    UPLOAD_ERR_INI_SIZE => 'O arquivo excede upload_max_filesize no servidor.',
    UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite definido no formulário.',
    UPLOAD_ERR_PARTIAL => 'O upload foi feito parcialmente.',
    UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
    UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente no servidor.',
    UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo em disco.',
    UPLOAD_ERR_EXTENSION => 'Upload interrompido por extensão PHP.'
  ];
  if ($pdfError !== UPLOAD_ERR_OK) {
    $msg = $uploadErrorMap[$pdfError] ?? 'Erro desconhecido no upload.';
    throw new Exception("Falha no upload do PDF. Código: {$pdfError} - {$msg}");
  }
  $pdfTmp  = $_FILES['pdf']['tmp_name'];
  $pdfType = @mime_content_type($pdfTmp) ?: '';
  $isPdf = ($pdfType === 'application/pdf');
  if (!$isPdf) {
    $orig = $_FILES['pdf']['name'] ?? '';
    $isPdf = (is_string($orig) && preg_match('/\.pdf$/i', $orig));
  }
  if (!$isPdf) {
    throw new Exception('Arquivo do livro deve ser PDF.');
  }
  $pdfDestRel = "/public/uploads/pdfs/{$slugBase}_{$uniq}.pdf";
  $pdfDestAbs = __DIR__ . "/.." . substr($pdfDestRel, strlen('/public'));
  if (!move_uploaded_file($pdfTmp, $pdfDestAbs)) {
    throw new Exception('Não foi possível salvar o PDF.');
  }

  // ===== CAPA =====
  $capaError = $_FILES['capa']['error'] ?? UPLOAD_ERR_NO_FILE;
  if ($capaError !== UPLOAD_ERR_OK) {
    $msg = $uploadErrorMap[$capaError] ?? 'Erro desconhecido no upload.';
    throw new Exception("Falha no upload da capa. Código: {$capaError} - {$msg}");
  }
  $capaTmp  = $_FILES['capa']['tmp_name'];
  $capaType = @mime_content_type($capaTmp) ?: '';
  $ext = match ($capaType) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    default      => null
  };
  if ($ext === null) {
    $orig = $_FILES['capa']['name'] ?? '';
    if (is_string($orig) && preg_match('/\.(jpe?g|png|webp)$/i', $orig, $m)) {
      $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
      if (!in_array($ext, ['jpg','png','webp'], true)) $ext = null;
    }
  }
  if ($ext === null) {
    throw new Exception('Capa deve ser JPG, PNG ou WebP.');
  }
  $capaDestRel = "/public/uploads/capas/{$slugBase}_{$uniq}.{$ext}";
  $capaDestAbs = __DIR__ . "/.." . substr($capaDestRel, strlen('/public'));
  if (!move_uploaded_file($capaTmp, $capaDestAbs)) {
    throw new Exception('Não foi possível salvar a capa.');
  }

  // Inserir no DB (com dono do cadastro)
  $pdo = db();
  $stmt = $pdo->prepare("
    INSERT INTO livros
      (titulo, autor, genero, publicado_por, descricao, pdf_path, capa_path, criado_por_id, criado_por_username)
    VALUES
      (:titulo, :autor, :genero, :publicado_por, :descricao, :pdf_path, :capa_path, :uid, :uname)
  ");
  $stmt->execute([
    ':titulo'    => $titulo,
    ':autor'     => $autor,
    ':genero'    => $genero,
    ':publicado_por' => $publicadoPor,
    ':descricao' => $descricao,
    ':pdf_path'  => $pdfDestRel,
    ':capa_path' => $capaDestRel,
    ':uid'       => $uid,
    ':uname'     => $uname,
  ]);

  header('Location: /home/main.php?cadastro=ok');
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  // Save old POST values in session so the form can be repopulated when user returns
  $_SESSION['old_input'] = [
    'titulo' => $_POST['titulo'] ?? '',
    'autor' => $_POST['autor'] ?? '',
    'genero' => $_POST['genero'] ?? '',
    'publicado_por' => $_POST['publicado_por'] ?? '',
    'descricao' => $_POST['descricao'] ?? '',
  ];
  // render friendly error page (Voltar irá recarregar o formulário que agora usará $_SESSION['old_input'])
  require_once __DIR__ . '/../templates/error_page.php';
  render_error_page('Erro no cadastro', $e->getMessage(), '/public/cadastro/main.php');
}
