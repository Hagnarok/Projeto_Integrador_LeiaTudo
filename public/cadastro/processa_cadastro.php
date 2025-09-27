<?php
// public/cadastro/processa_cadastro.php
// session_start(); // habilite se precisar de sessão

require_once __DIR__ . '/../lib/db.php';

try {
  // Campos
  $titulo = trim($_POST['titulo'] ?? '');
  $autor  = trim($_POST['autor'] ?? '');
  $genero = trim($_POST['genero'] ?? '');
  $preco  = $_POST['preco'] ?? '';
  $descricao = trim($_POST['descricao'] ?? '');

  if ($titulo === '' || $autor === '' || $genero === '' || $preco === '' ||
      !isset($_FILES['pdf']) || !isset($_FILES['capa'])) {
    throw new Exception('Campos obrigatórios ausentes.');
  }
  if (!is_numeric($preco) || $preco < 0) {
    throw new Exception('Preço inválido.');
  }

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

  // PDF
  if ($_FILES['pdf']['error'] !== UPLOAD_ERR_OK) throw new Exception('Falha no upload do PDF.');
  $pdfTmp  = $_FILES['pdf']['tmp_name'];
  $pdfType = mime_content_type($pdfTmp);
  if ($pdfType !== 'application/pdf') throw new Exception('Arquivo do livro deve ser PDF.');
  $pdfDestRel = "/public/uploads/pdfs/{$slugBase}_{$uniq}.pdf";
  $pdfDestAbs = __DIR__ . "/.." . substr($pdfDestRel, strlen('/public'));
  if (!move_uploaded_file($pdfTmp, $pdfDestAbs)) throw new Exception('Não foi possível salvar o PDF.');

  // Capa
  if ($_FILES['capa']['error'] !== UPLOAD_ERR_OK) throw new Exception('Falha no upload da capa.');
  $capaTmp  = $_FILES['capa']['tmp_name'];
  $capaType = mime_content_type($capaTmp);
  $ext = match ($capaType) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    default      => null
  };
  if ($ext === null) throw new Exception('Capa deve ser JPG, PNG ou WebP.');
  $capaDestRel = "/public/uploads/capas/{$slugBase}_{$uniq}.{$ext}";
  $capaDestAbs = __DIR__ . "/.." . substr($capaDestRel, strlen('/public'));
  if (!move_uploaded_file($capaTmp, $capaDestAbs)) throw new Exception('Não foi possível salvar a capa.');

  // Inserir no DB
  $pdo = db();
  $stmt = $pdo->prepare("
    INSERT INTO livros (titulo, autor, genero, preco, descricao, pdf_path, capa_path)
    VALUES (:titulo, :autor, :genero, :preco, :descricao, :pdf_path, :capa_path)
  ");
  $stmt->execute([
    ':titulo' => $titulo,
    ':autor'  => $autor,
    ':genero' => $genero,
    ':preco'  => (float)$preco,
    ':descricao' => $descricao,
    ':pdf_path'  => $pdfDestRel,
    ':capa_path' => $capaDestRel,
  ]);

  header('Location: /home/main.php?cadastro=ok');
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  echo "<h2>Erro no cadastro</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
