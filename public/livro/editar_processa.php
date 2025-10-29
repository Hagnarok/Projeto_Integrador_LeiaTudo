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

  if ($id <= 0) throw new Exception('ID inválido.');

  $user = $_SESSION['user'] ?? null;
  if (!$user || !isset($user['id'])) throw new Exception('Não autenticado.');

  $pdo = db();

  // 1) Buscar registro e conferir dono
  $stmt = $pdo->prepare("SELECT * FROM livros WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => $id]);
  $livro = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$livro) throw new Exception('Livro não encontrado.');
  if ((string)($livro['criado_por_id'] ?? '') !== (string)$user['id']) {
    throw new Exception('Sem permissão para editar este livro.');
  }

  // 2) Campos
  $titulo    = trim($_POST['titulo'] ?? '');
  $autor     = trim($_POST['autor'] ?? '');
  $genero    = trim($_POST['genero'] ?? '');
  $publicadoPor = trim($_POST['publicado_por'] ?? '');
  $descricao = trim($_POST['descricao'] ?? '');

  if ($titulo === '' || $autor === '' || $genero === '' || $publicadoPor === '') {
    throw new Exception('Campos obrigatórios ausentes.');
  }

  $publicadoPor = substr($publicadoPor, 0, 255);

  // 3) Uploads (opcionais)
  $baseUploads = __DIR__ . '/../uploads';
  $dirPdfs  = $baseUploads . '/pdfs';
  $dirCapas = $baseUploads . '/capas';
  foreach ([$baseUploads, $dirPdfs, $dirCapas] as $d) {
    if (!is_dir($d) && !mkdir($d, 0775, true)) {
      throw new Exception("Não foi possível criar a pasta: $d");
    }
  }

  $slugBase = preg_replace('/[^a-z0-9]+/i', '_', strtolower($titulo));
  $uniq     = time() . '_' . mt_rand(1000,9999);

  $pdf_path  = $livro['pdf_path'];   // default: mantém
  $capa_path = $livro['capa_path'];  // default: mantém

  // --- PDF novo?
  if (!empty($_FILES['pdf']) && ($_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $pdfTmp  = $_FILES['pdf']['tmp_name'];
    $pdfType = @mime_content_type($pdfTmp) ?: '';
    $isPdf   = ($pdfType === 'application/pdf');
    if (!$isPdf) {
      $orig = $_FILES['pdf']['name'] ?? '';
      $isPdf = (is_string($orig) && preg_match('/\.pdf$/i', $orig));
    }
    if (!$isPdf) throw new Exception('O novo arquivo deve ser PDF.');

    $pdfDestRel = "/public/uploads/pdfs/{$slugBase}_{$uniq}.pdf";
    $pdfDestAbs = __DIR__ . "/.." . substr($pdfDestRel, strlen('/public'));
    if (!move_uploaded_file($pdfTmp, $pdfDestAbs)) throw new Exception('Não foi possível salvar o novo PDF.');

    // apaga antigo, se for dentro de /public/uploads/
    if ($pdf_path && strpos($pdf_path, '/public/uploads/pdfs/') === 0) {
      $oldAbs = __DIR__ . "/.." . substr($pdf_path, strlen('/public'));
      if (is_file($oldAbs)) @unlink($oldAbs);
    }
    $pdf_path = $pdfDestRel;
  }

  // --- Capa nova?
  if (!empty($_FILES['capa']) && ($_FILES['capa']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
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
    if ($ext === null) throw new Exception('A nova capa deve ser JPG, PNG ou WebP.');

    $capaDestRel = "/public/uploads/capas/{$slugBase}_{$uniq}.{$ext}";
    $capaDestAbs = __DIR__ . "/.." . substr($capaDestRel, strlen('/public'));
    if (!move_uploaded_file($capaTmp, $capaDestAbs)) throw new Exception('Não foi possível salvar a nova capa.');

    // apaga capa antiga
    if ($capa_path && strpos($capa_path, '/public/uploads/capas/') === 0) {
      $oldAbs = __DIR__ . "/.." . substr($capa_path, strlen('/public'));
      if (is_file($oldAbs)) @unlink($oldAbs);
    }
    $capa_path = $capaDestRel;
  }

  // 4) UPDATE
  $up = $pdo->prepare("
    UPDATE livros
      SET titulo = :titulo,
        autor = :autor,
        genero = :genero,
        publicado_por = :publicado_por,
        descricao = :descricao,
        pdf_path = :pdf,
        capa_path = :capa
     WHERE id = :id
     LIMIT 1
  ");
  $up->execute([
    ':titulo' => $titulo,
    ':autor'  => $autor,
    ':genero' => $genero,
  ':publicado_por' => $publicadoPor,
    ':descricao' => $descricao,
    ':pdf'    => $pdf_path,
    ':capa'   => $capa_path,
    ':id'     => $id,
  ]);

  header('Location: /public/livro/ver.php?id=' . $id . '&edit=ok');
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  echo "<h2>Erro ao editar</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
