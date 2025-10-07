<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo "ID inválido."; exit; }

$pdo = db();
$stmt = $pdo->prepare("
  SELECT id, titulo, autor, genero, preco, descricao, pdf_path, capa_path, criado_por_id, criado_por_username
  FROM livros WHERE id = :id LIMIT 1
");
$stmt->execute([':id' => $id]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$livro) { http_response_code(404); echo "Livro não encontrado."; exit; }

// Dono?
$user = $_SESSION['user'] ?? null;
$canEdit = $user && isset($user['id']) && (string)$user['id'] === (string)($livro['criado_por_id'] ?? '');
if (!$canEdit) { http_response_code(403); echo "Sem permissão para editar este livro."; exit; }

$csrf = csrf_token();

// helpers
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$generos = [
  'Romance','Ficção Científica','Fantasia','Suspense/Thriller','Não Ficção',
  'Didático','Biografia','Poesia','Infantil','Outro'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar • <?= h($livro['titulo'] ?? 'E-book') ?> • LeiaTudo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{ background:#f7f9fc; }
    .card{ border:0; box-shadow:0 10px 30px rgba(0,0,0,.06); }
    .thumb{ width:120px; height:160px; object-fit:cover; border-radius:.5rem; background:#eef3fb; }
  </style>
</head>
<body>
<header class="py-3 bg-white border-bottom">
  <div class="container d-flex justify-content-between align-items-center">
    <a class="navbar-brand d-inline-flex align-items-center gap-2" href="/home/main.php">
      <img src="/css/imgs/logo.png" alt="LeiaTudo" width="40" height="40">
      <strong class="text-dark mb-0">LeiaTudo</strong>
    </a>
    <div class="d-flex gap-2">
      <a href="/public/livro/ver.php?id=<?= (int)$id ?>" class="btn btn-outline-secondary btn-sm">Voltar ao livro</a>
    </div>
  </div>
</header>

<main class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
      <div class="card">
        <div class="card-body p-4 p-lg-5">
          <h1 class="h4 mb-4">Editar E-book</h1>

          <form action="/public/livro/editar_processa.php" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="id" value="<?= (int)$livro['id'] ?>">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

            <!-- Título -->
            <div class="mb-3">
              <label for="titulo" class="form-label">Título</label>
              <input type="text" id="titulo" name="titulo" class="form-control" required maxlength="200"
                     value="<?= h($livro['titulo']) ?>">
            </div>

            <!-- Autor -->
            <div class="mb-3">
              <label for="autor" class="form-label">Autor</label>
              <input type="text" id="autor" name="autor" class="form-control" required maxlength="150"
                     value="<?= h($livro['autor']) ?>">
            </div>

            <!-- Gênero -->
            <div class="mb-3">
              <label for="genero" class="form-label">Gênero</label>
              <select id="genero" name="genero" class="form-select" required>
                <option value="" disabled>Selecione…</option>
                <?php foreach($generos as $g): ?>
                  <option <?= ($livro['genero']===$g?'selected':'') ?>><?= h($g) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Preço -->
            <div class="mb-3">
              <label for="preco" class="form-label">Preço (R$)</label>
              <input type="text" id="preco" name="preco" class="form-control" required
                     value="<?= number_format((float)$livro['preco'], 2, ',', '.') ?>">
              <div class="form-text">Aceita 39,90 ou 39.90</div>
            </div>

            <!-- Descrição -->
            <div class="mb-3">
              <label for="descricao" class="form-label">Descrição</label>
              <textarea id="descricao" name="descricao" rows="4" class="form-control"
                        maxlength="2000"><?= h($livro['descricao'] ?? '') ?></textarea>
            </div>

            <!-- Arquivos atuais -->
            <div class="row g-3 mb-2">
              <div class="col-auto">
                <?php if (!empty($livro['capa_path'])): ?>
                  <img src="<?= h($livro['capa_path']) ?>" alt="Capa atual" class="thumb">
                <?php else: ?>
                  <div class="thumb d-flex align-items-center justify-content-center text-muted">Sem capa</div>
                <?php endif; ?>
              </div>
              <div class="col d-flex align-items-center">
                <div>
                  <div class="mb-1"><strong>PDF atual:</strong>
                    <?php if (!empty($livro['pdf_path'])): ?>
                      <a href="<?= h($livro['pdf_path']) ?>" target="_blank" rel="noopener">Abrir</a>
                    <?php else: ?>
                      <span class="text-muted">Sem PDF</span>
                    <?php endif; ?>
                  </div>
                  <div class="text-muted small">Você pode enviar novos arquivos abaixo para substituir.</div>
                </div>
              </div>
            </div>

            <!-- Substituir PDF (opcional) -->
            <div class="mb-3">
              <label for="pdf" class="form-label">Substituir PDF (opcional)</label>
              <input type="file" id="pdf" name="pdf" class="form-control" accept="application/pdf">
            </div>

            <!-- Substituir capa (opcional) -->
            <div class="mb-4">
              <label for="capa" class="form-label">Substituir capa (opcional)</label>
              <input type="file" id="capa" name="capa" class="form-control" accept="image/*">
              <div class="form-text">Aceita JPG, PNG ou WebP.</div>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary btn-lg">Salvar alterações</button>
              <a href="/public/livro/ver.php?id=<?= (int)$livro['id'] ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</main>
</body>
</html>
