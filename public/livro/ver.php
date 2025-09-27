<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../lib/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo "ID inválido."; exit; }

$pdo = db();
$stmt = $pdo->prepare("SELECT id, titulo, autor, pdf_path, capa_path, criado_em FROM livros WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$livro) { http_response_code(404); echo "Livro não encontrado."; exit; }

$pdfWeb = (string)$livro['pdf_path'];
if (strpos($pdfWeb, '/public/uploads/pdfs/') !== 0) {
  http_response_code(400); echo "Caminho do PDF inválido."; exit;
}
$capaWeb = (string)($livro['capa_path'] ?? '');

$pdfAbs = __DIR__ . "/.." . substr($pdfWeb, strlen('/public')); // resolve caminho físico
$pdfSize = (is_file($pdfAbs) ? filesize($pdfAbs) : null);
function humanSize(?int $bytes): string {
  if ($bytes === null || $bytes < 0) return '';
  $u = ['B','KB','MB','GB','TB']; $i = 0; $v = (float)$bytes;
  while ($v >= 1024 && $i < count($u)-1) { $v /= 1024; $i++; }
  return number_format($v, ($i===0?0:2), ',', '.') . ' ' . $u[$i];
}

$titulo = $livro['titulo'] ?? 'E-book';
$autor  = $livro['autor']  ?? '';
$criado = $livro['criado_em'] ?? null;

$pdfUrl = htmlspecialchars($pdfWeb, ENT_QUOTES, 'UTF-8');
$capaUrl = $capaWeb ? htmlspecialchars($capaWeb, ENT_QUOTES, 'UTF-8') : '';
$tituloEsc = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
$autorEsc  = htmlspecialchars($autor, ENT_QUOTES, 'UTF-8');
$tamEsc    = humanSize($pdfSize);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= $tituloEsc ?> • LeiaTudo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root{
      --header-h: 68px;
      --grad: linear-gradient(90deg, #4da3ff, #7cc1ff 60%, #cfeaff);
      --border: #e9eef5;
      --bg-soft: #f7f9fc;
      --text-weak: #51607a;
    }
    @media (prefers-color-scheme: dark){
      :root{
        --grad: linear-gradient(90deg, #2a5ea0, #3d7bc7 60%, #477ab6);
        --border: #253041;
        --bg-soft: #0f1420;
        --text-weak: #9fb2d4;
      }
      body, .viewer { background:#0b101a; }
      header, .meta-strip { background:#0f1624; }
      .btn-outline-secondary, .btn-outline-primary { border-color:#2b3a52; }
    }

    body{ background:var(--bg-soft); }
    header{
      position: sticky; top:0; z-index: 30;
      height: var(--header-h);
      background: #fff; border-bottom:1px solid var(--border);
      display:flex; align-items:center;
    }
    .brand{
      display:flex; align-items:center; gap:.6rem; text-decoration:none;
      color:#0f172a;
    }
    .brand-title{ font-weight:700; letter-spacing:.2px; }
    .app-bar{
      display:flex; align-items:center; gap:1rem; justify-content:space-between;
    }

    .meta-strip{
      background: #fff; border-bottom:1px solid var(--border);
    }
    .meta-card{
      display:flex; align-items:center; gap:1rem; padding: .85rem 0;
    }
    .cover{
      width:64px; height:86px; border-radius:.5rem; object-fit:cover; background:#eef3fb;
      box-shadow: 0 6px 18px rgba(0,0,0,.12);
      cursor: zoom-in;
    }
    .book-title{ font-weight:800; margin:0; }
    .book-sub{ color:var(--text-weak); margin:0; }
    .badge-soft{
      font-size:.75rem; border:1px solid var(--border); padding:.25rem .5rem; border-radius:999px;
      color:#334; background:#fff;
    }

    .action-bar .btn{ white-space:nowrap; }
    .action-bar .btn i{ margin-right:.35rem; }

    .viewer-wrap{ height: calc(100vh - var(--header-h) - 96px); }
    @media (max-width: 576px){
      .viewer-wrap{ height: calc(100vh - var(--header-h) - 130px); }
    }
    .viewer { width:100%; height:100%; border:0; background:#fff; }

    /* Loader */
    .viewer-loader{
      position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
      background:linear-gradient(180deg, rgba(255,255,255,.9), rgba(255,255,255,.85));
      backdrop-filter: blur(2px);
    }
    @media (prefers-color-scheme: dark){
      .viewer-loader{
        background:linear-gradient(180deg, rgba(5,10,18,.9), rgba(5,10,18,.85));
      }
    }
  </style>
</head>
<body>

<header>
  <div class="container app-bar">
    <div class="d-flex align-items-center gap-3">
      <a class="brand" href="/home/main.php" title="LeiaTudo">
        <img src="/css/imgs/logo.png" alt="LeiaTudo" width="36" height="36" class="rounded-circle">
        <span class="brand-title">LeiaTudo</span>
      </a>
      <span class="d-none d-sm-inline text-secondary">/</span>
      <button id="btnBack" type="button" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
      </button>
    </div>

    <div class="action-bar d-flex align-items-center gap-2">
      <button id="btnCopy" class="btn btn-sm btn-outline-secondary" type="button" title="Copiar link (Ctrl+C)">
        <i class="bi bi-link-45deg"></i> Copiar link
      </button>
      <a class="btn btn-sm btn-outline-primary" href="<?= $pdfUrl ?>" target="_blank" rel="noopener" title="Abrir em nova aba (O)">
        <i class="bi bi-box-arrow-up-right"></i> Abrir em nova aba
      </a>
      <a class="btn btn-sm btn-primary" href="<?= $pdfUrl ?>" download title="Baixar PDF (D)">
        <i class="bi bi-download"></i> Baixar PDF
      </a>
      <button id="btnPrint" class="btn btn-sm btn-outline-secondary" type="button" title="Imprimir (P)">
        <i class="bi bi-printer"></i> Imprimir
      </button>
    </div>
  </div>
</header>

<section class="meta-strip">
  <div class="container">
    <div class="meta-card">
      <?php if ($capaUrl): ?>
        <img src="<?= $capaUrl ?>" class="cover" alt="Capa do livro" data-bs-toggle="modal" data-bs-target="#modalCapa">
      <?php else: ?>
        <div class="cover d-flex align-items-center justify-content-center text-muted" title="Sem capa">
          <i class="bi bi-book" style="font-size:1.25rem;"></i>
        </div>
      <?php endif; ?>
      <div class="flex-grow-1">
        <h1 class="book-title h5 mb-1"><?= $tituloEsc ?></h1>
        <p class="book-sub mb-0">
          <?php if ($autorEsc): ?>de <strong><?= $autorEsc ?></strong><?php endif; ?>
          <?php if ($tamEsc): ?><span class="ms-2 badge-soft"><?= $tamEsc ?></span><?php endif; ?>
          <?php if ($criado): ?><span class="ms-2 text-secondary">adic.: <?= htmlspecialchars($criado, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </p>
      </div>
    </div>
  </div>
</section>

<main class="position-relative">
  <div class="viewer-wrap container-fluid px-0">
    <!-- Loader -->
    <div id="viewerLoader" class="viewer-loader">
      <div class="text-center">
        <div class="spinner-border" role="status" aria-label="Carregando PDF..."></div>
        <div class="mt-2 text-secondary">Carregando o leitor…</div>
      </div>
    </div>

    <!-- Usando iframe para melhor controle de onload -->
    <iframe id="pdfFrame" src="<?= $pdfUrl ?>#toolbar=1&navpanes=0&view=FitH" class="viewer"></iframe>
  </div>
</main>

<!-- Modal da Capa -->
<div class="modal fade" id="modalCapa" tabindex="-1" aria-labelledby="modalCapaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCapaLabel">Capa • <?= $tituloEsc ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body p-0">
        <?php if ($capaUrl): ?>
          <img src="<?= $capaUrl ?>" alt="Capa do livro" style="width:100%; height:auto; display:block;">
        <?php else: ?>
          <div class="p-4 text-center text-secondary">Sem capa disponível.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const backBtn = document.getElementById('btnBack');
  backBtn?.addEventListener('click', ()=>{
    if (window.history.length > 1) history.back();
    else window.location.href = '/home/main.php';
  });

  // Loader some: esconder quando o iframe carregar
  const pdfFrame = document.getElementById('pdfFrame');
  const loader = document.getElementById('viewerLoader');
  if (pdfFrame) {
    pdfFrame.addEventListener('load', ()=>{
      loader?.classList.add('d-none');
    });
    // fallback: se não disparar load por algum motivo, some após 3s
    setTimeout(()=>loader?.classList.add('d-none'), 3000);
  }

  // Copiar link
  const btnCopy = document.getElementById('btnCopy');
  btnCopy?.addEventListener('click', async ()=>{
    try {
      await navigator.clipboard.writeText(window.location.href);
      btnCopy.classList.remove('btn-outline-secondary');
      btnCopy.classList.add('btn-success');
      btnCopy.innerHTML = '<i class="bi bi-check2"></i> Copiado';
      setTimeout(()=>{
        btnCopy.classList.add('btn-outline-secondary');
        btnCopy.classList.remove('btn-success');
        btnCopy.innerHTML = '<i class="bi bi-link-45deg"></i> Copiar link';
      }, 1800);
    } catch(e){
      alert('Não foi possível copiar o link.');
    }
  });

  // Atalhos de teclado
  document.addEventListener('keydown', (ev)=>{
    const k = ev.key.toLowerCase();
    if (k === 'b') backBtn?.click();
    if (k === 'd') document.querySelector('a[download]')?.click();
    if (k === 'o') document.querySelector('a[target="_blank"]')?.click();
    if (k === 'p') window.print();
  });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
