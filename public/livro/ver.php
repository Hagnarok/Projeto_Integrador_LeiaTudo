<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../lib/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo "ID inválido."; exit; }

$pdo = db();

// 1) Buscar o livro
$stmt = $pdo->prepare("
  SELECT id, titulo, autor, pdf_path, capa_path, criado_em, criado_por_id, criado_por_username
  FROM livros
  WHERE id = :id
  LIMIT 1
");
$stmt->execute([':id' => $id]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$livro) { http_response_code(404); echo "Livro não encontrado."; exit; }

// 2) Segurança do caminho
$pdfWeb = (string)$livro['pdf_path'];
if (strpos($pdfWeb, '/public/uploads/pdfs/') !== 0) {
  http_response_code(400); echo "Caminho do PDF inválido."; exit;
}
$capaWeb = (string)($livro['capa_path'] ?? '');

// 3) Permissões
$ownerId   = $livro['criado_por_id'] ?? null;
$ownerName = $livro['criado_por_username'] ?? '';
$currUser  = $_SESSION['user'] ?? null;
$canDelete = $currUser && isset($currUser['id']) && (string)$currUser['id'] === (string)$ownerId;

// 4) CSRF
require_once __DIR__ . '/../lib/csrf.php';
$csrf = csrf_token();

// 5) Metadados / helpers
$pdfAbs  = __DIR__ . "/.." . substr($pdfWeb, strlen('/public'));
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

// Links comuns
$pdfUrl    = htmlspecialchars($pdfWeb, ENT_QUOTES, 'UTF-8');
// Zoom inicial: largura da página
$pdfSrcRaw = $pdfWeb . '?_=' . time() . '#page=1&zoom=page-width';
$pdfSrc    = htmlspecialchars($pdfSrcRaw, ENT_QUOTES, 'UTF-8');

$capaUrl   = $capaWeb ? htmlspecialchars($capaWeb, ENT_QUOTES, 'UTF-8') : '';
$tituloEsc = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
$autorEsc  = htmlspecialchars($autor, ENT_QUOTES, 'UTF-8');
$tamEsc    = humanSize($pdfSize);

// flags
$editOk = ($_GET['edit'] ?? '') === 'ok';
$delOk  = ($_GET['del']  ?? '') === 'ok';
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
    /* =========================
       PALETA PADRÃO LEIATUDO
       Branco + Azul #0573b3
       ========================= */
    :root{
      --brand: #0573b3;
      --brand-2: #0a8fd9;     /* tom levemente mais claro p/ hover */
      --text-weak: #5a6b7f;
      --surface: rgba(255,255,255,0.9);
      --surface-bd: rgba(0,0,0,0.06);
      --shadow-lg: 0 16px 50px rgba(0,0,0,.08);
      --shadow-sm: 0 6px 24px rgba(0,0,0,.06);
      --border: #e9eef4;

      /* Alinha o tema do Bootstrap para o azul padrão */
      --bs-primary: #0573b3;
      --bs-primary-rgb: 5,115,179;

      --header-h: 70px;
      --rail-w: 220px;
    }

    /* Ajustes gerais do Bootstrap para adotar o azul padrão */
    .btn-primary {
      background-color: var(--bs-primary) !important;
      border-color: var(--bs-primary) !important;
    }
    .btn-primary:hover {
      background-color: var(--brand-2) !important;
      border-color: var(--brand-2) !important;
    }
    .btn-outline-primary{
      color: var(--bs-primary) !important;
      border-color: var(--bs-primary) !important;
      background-color: #fff !important;
    }
    .btn-outline-primary:hover{
      color: #fff !important;
      background-color: var(--bs-primary) !important;
      border-color: var(--bs-primary) !important;
    }
    .text-primary{ color: var(--bs-primary) !important; }
    .border-primary{ border-color: var(--bs-primary) !important; }
    .bg-primary{ background-color: var(--bs-primary) !important; }

    body{
      background: #fff; /* fundo branco, limpo e neutro */
      min-height: 100vh;
    }

    /* Header fixo e translúcido */
    header{
      position: sticky; top:0; z-index: 30; height: var(--header-h);
      backdrop-filter: saturate(130%) blur(8px);
      background: rgba(255,255,255,.9);
      border-bottom: 1px solid var(--surface-bd);
      display:flex; align-items:center;
    }
    .brand{ display:flex; align-items:center; gap:.75rem; text-decoration:none; color:#0f172a; }
    .brand img{ width:54px; height:54px; border-radius:12px; box-shadow: var(--shadow-sm); }
    .brand-title{ font-weight:800; letter-spacing:.2px; font-size:1.25rem; color: var(--bs-primary); }
    .app-bar{ display:flex; align-items:center; gap:.75rem; justify-content:space-between; width:100%; flex-wrap: wrap; }

    /* Meta strip */
    .meta-strip{ border-bottom: 1px solid var(--surface-bd); padding: 12px 0; }
    .meta-card{
      display:flex; align-items:center; gap:1rem;
      background: var(--surface); border:1px solid var(--surface-bd);
      border-radius: 14px; padding: 12px 14px; box-shadow: var(--shadow-sm);
    }
    .cover{ width:80px; height:110px; border-radius:10px; object-fit:cover; background:#f3f6fa; box-shadow: var(--shadow-sm); }
    .book-title{ font-weight:800; margin:0; letter-spacing:.2px; }
    .book-sub{ color:var(--text-weak); margin:0; }
    .badge-soft{ font-size:.76rem; border:1px solid var(--border); padding:.25rem .6rem; border-radius:999px; color:#264; background:#fff; }

    /* Viewer */
    .viewer-outer.container { }
    .viewer-wrap{
      height: calc(100vh - var(--header-h) - 110px);
      margin-top: 14px;
    }
    .viewer{ width:100%; height:100%; border:0; background:#fff; border-radius: 14px; box-shadow: var(--shadow-lg); }
    .viewer-loader{
      position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
      background:linear-gradient(180deg, rgba(255,255,255,.9), rgba(255,255,255,.85)); border-radius: 14px;
    }

    /* Responsividade do viewer: alturas mais generosas em telas menores */
    @media (max-width: 992px){
      .viewer-wrap{ height: calc(100vh - var(--header-h) - 90px); }
      .brand img{ width:50px; height:50px; }
    }
    @media (max-width: 576px){
      .viewer-wrap{ height: calc(100vh - var(--header-h) - 70px); }
      .meta-card{ flex-direction: row; gap:.75rem; }
      .cover{ width:64px; height:88px; }
      .action-bar > *:not(.btn){ margin-top: .25rem; }
    }

    /* Botões proprietários: agora alinhados à paleta */
    .btn-action{
      background: var(--bs-primary);
      color:#fff; border:1px solid transparent;
      box-shadow: 0 6px 18px rgba(5,115,179,.18);
    }
    .btn-action:hover{ background: var(--brand-2); color:#fff; }
    .btn-action:active{ filter: brightness(.95); color:#fff; }
    .btn-action i{ margin-right:.35rem; }
    .btn-danger-solid{ background:#dc3545; color:#fff; border:1px solid transparent; box-shadow: 0 6px 18px rgba(220,53,69,.18); }
    .btn-danger-solid:hover{ background:#c22736; color:#fff; }
    .btn-danger-solid:active{ background:#a51f2c; color:#fff; }
    .btn-chip{ border-radius:999px; padding:.35rem .75rem; font-weight:600; }

    /* ====== MODO LEITURA COM RAIL ESQUERDO ====== */
    .reader-compact header,
    .reader-compact .meta-strip { display:none !important; }
    .reader-compact .viewer-wrap { height: 100vh; margin-top: 0; }
    .reader-compact .viewer-outer { max-width: none; padding-left: calc(var(--rail-w) + 16px); }

    .reader-rail{
      display:none;
      position:fixed; z-index:40; left:0; top:0; bottom:0; width:var(--rail-w);
      backdrop-filter: blur(8px) saturate(130%);
      background: rgba(255,255,255,.95);
      border-right: 1px solid var(--surface-bd);
      box-shadow: 6px 0 18px rgba(0,0,0,.06);
      padding: 14px 12px;
    }
    .reader-compact .reader-rail{ display:flex; flex-direction:column; gap:10px; }

    .rail-brand{ display:flex; align-items:center; gap:.6rem; text-decoration:none; color:#0f172a; }
    .rail-brand img{ width:42px; height:42px; border-radius:10px; box-shadow: var(--shadow-sm); }
    .rail-title{ font-weight:800; letter-spacing:.2px; font-size:1.05rem; color: var(--bs-primary); }

    .rail-actions{ display:flex; flex-direction:column; gap:8px; margin-top:10px; }
    .rail-actions .btn{ text-align:left; display:flex; align-items:center; gap:.5rem; }
    .rail-sep{ height:1px; background:var(--surface-bd); margin:8px 0; }
    .rail-note{ color:var(--text-weak); font-size:.85rem; }

    @media (max-width: 576px){
      :root{ --rail-w: 200px; }
    }

    /* Travar o scroll da página inteira */
    html, body {
      height: 100%;
      overflow: hidden; /* <- bloqueia o scroll da página */
    }

    /* O main e o viewer passam a ocupar o espaço visível sem criar scroll na página */
    main {
      height: calc(100vh - var(--header-h) - var(--meta-h, 110px));
      overflow: hidden;
    }

    /* Garantir que as camadas do viewer não gerem scroll externo */
    .viewer-outer,
    .viewer-wrap {
      height: 100%;
      overflow: hidden;
    }

    /* O iframe (Chrome PDF Viewer) cuida do scroll interno */
    .viewer {
      height: 100%;
      width: 100%;
      display: block;
      border: 0;
    }

  </style>
</head>
<body>

<!-- RAIL ESQUERDO (aparece apenas no modo leitura) -->
<aside class="reader-rail">
  <a class="rail-brand" href="/home/main.php" title="LeiaTudo">
    <img src="/css/imgs/logo.png" alt="LeiaTudo">
    <span class="rail-title">LeiaTudo</span>
  </a>
  <div class="rail-sep"></div>
  <div class="rail-actions">
    <!-- Botão Home com Bootstrap -->
    <a href="/home/main.php" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-house-door"></i><span>Home</span>
    </a>
  </div>
  <div class="rail-sep"></div>
  <div class="rail-note">Pressione <strong>L</strong> para sair do modo leitura.</div>
</aside>

<header>
  <div class="container-fluid app-bar">
    <div class="d-flex justify-content-start">
      <a class="brand d-flex align-items-center" href="/home/main.php" title="LeiaTudo">
        <img src="/css/imgs/logo.png" alt="LeiaTudo">
        <span class="brand-title ms-2">LeiaTudo</span>
      </a>
    </div>

    <div class="action-bar d-flex align-items-center gap-2 flex-wrap">
      <!-- Botão Home com Bootstrap no topo -->
      <a href="/home/main.php" class="btn btn-outline-primary btn-sm" title="Ir para a Home">
        <i class="bi bi-house-door"></i> Home
      </a>

      <?php if ($canDelete): ?>
        <a href="/public/livro/editar.php?id=<?= (int)$id ?>"
           class="btn btn-primary btn-sm" title="Editar este livro">
          <i class="bi bi-pencil-square"></i> Editar
        </a>
        <form action="/public/livro/excluir.php" method="POST" class="d-inline"
              onsubmit="return confirm('Tem certeza que deseja excluir este livro? Esta ação não pode ser desfeita.');">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-danger-solid btn-sm" type="submit" title="Excluir este livro">
            <i class="bi bi-trash3"></i> Excluir
          </button>
        </form>
      <?php endif; ?>

      <button id="btnCopy" class="btn btn-primary btn-sm" type="button" title="Copiar link (Ctrl+C)">
        <i class="bi bi-link-45deg"></i> Copiar link
      </button>
      <a class="btn btn-primary btn-sm" href="<?= $pdfUrl ?>" target="_blank" rel="noopener" title="Abrir em nova aba (O)">
        <i class="bi bi-box-arrow-up-right"></i> Abrir
      </a>
      <a class="btn btn-primary btn-sm" href="<?= $pdfUrl ?>" download title="Baixar PDF (D)">
        <i class="bi bi-download"></i> Baixar
      </a>

      <!-- Controles de leitura/zoom -->
      <button id="btnRead" class="btn btn-primary btn-sm" type="button" title="Modo leitura (L)">
        <i class="bi bi-layout-text-window-reverse"></i> Leitura
      </button>
    </div>
  </div>
</header>

<section class="meta-strip">
  <div class="container">
    <?php if ($editOk): ?>
      <div class="alert alert-success my-3 py-2 px-3">
        <i class="bi bi-check-circle me-1"></i> Livro atualizado com sucesso.
      </div>
    <?php endif; ?>
    <?php if ($delOk): ?>
      <div class="alert alert-success my-3 py-2 px-3">
        <i class="bi bi-check-circle me-1"></i> Livro excluído com sucesso.
      </div>
    <?php endif; ?>

    <div class="meta-card">
      <?php if ($capaUrl): ?>
        <img src="<?= $capaUrl ?>" class="cover" alt="Capa do livro">
      <?php else: ?>
        <div class="cover d-flex align-items-center justify-content-center text-muted" title="Sem capa">
          <i class="bi bi-book" style="font-size:1.1rem;"></i>
        </div>
      <?php endif; ?>
      <div class="flex-grow-1">
        <h1 class="book-title h5 mb-1"><?= $tituloEsc ?></h1>
        <p class="book-sub mb-0">
          <?php if ($autorEsc): ?>de <strong><?= $autorEsc ?></strong><?php endif; ?>
          <?php if ($tamEsc): ?><span class="ms-2 badge-soft"><?= $tamEsc ?></span><?php endif; ?>
          <?php if ($criado): ?><span class="ms-2 text-secondary">adic.: <?= htmlspecialchars($criado, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          <?php if ($ownerName): ?><span class="ms-2 text-secondary">por: <?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </p>
      </div>
    </div>
  </div>
</section>

<main class="position-relative">
  <div class="viewer-outer container">
    <div class="viewer-wrap">
      <div id="viewerLoader" class="viewer-loader">
        <div class="text-center">
          <div class="spinner-border text-primary" role="status" aria-label="Carregando PDF..."></div>
          <div class="mt-2 text-secondary">Carregando o leitor…</div>
        </div>
      </div>
      <iframe id="pdfFrame" src="<?= $pdfSrc ?>" class="viewer" title="Leitor de PDF"></iframe>
    </div>
  </div>
</main>

<script>
(function(){
  const pdfFrame  = document.getElementById('pdfFrame');
  const loader    = document.getElementById('viewerLoader');
  const btnRead   = document.getElementById('btnRead');

  // Loader
  pdfFrame?.addEventListener('load', ()=> loader?.classList.add('d-none'));
  setTimeout(()=> loader?.classList.add('d-none'), 3000);

  // Copiar link
  const btnCopy = document.getElementById('btnCopy');
  btnCopy?.addEventListener('click', async ()=>{
    try {
      await navigator.clipboard.writeText(location.href);
      btnCopy.classList.add('disabled');
      btnCopy.innerHTML = '<i class="bi bi-check2"></i> Copiado';
      setTimeout(()=>{
        btnCopy.classList.remove('disabled');
        btnCopy.innerHTML = '<i class="bi bi-link-45deg"></i> Copiar link';
      }, 1600);
    } catch(e){ alert('Não foi possível copiar o link.'); }
  });

  // ====== Zoom / Ajuste ======
  const zoomSteps = [50, 67, 80, 90, 100, 110, 125, 150, 175, 200, 250];
  let currentZoom = 'page-width';

  function setZoom(value){
    currentZoom = value;
    try{
      const url = new URL(pdfFrame.src, location.origin);
      const hash = url.hash || '';
      const matchPage = hash.match(/page=(\\d+)/);
      const page = matchPage ? matchPage[1] : '1';
      url.hash = `#page=${page}&zoom=${value}`;
      if (pdfFrame.src !== url.toString()) {
        pdfFrame.src = url.toString();
      } else {
        pdfFrame.contentWindow?.location.replace(url.toString());
      }
    }catch(e){
      pdfFrame.src = pdfFrame.src.split('#')[0] + `#page=1&zoom=${value}`;
    }
  }
  function stepZoom(delta){
    let z = (typeof currentZoom === 'number') ? currentZoom : 100;
    let idx = zoomSteps.findIndex(v => v >= z);
    if (idx === -1) idx = zoomSteps.indexOf(100);
    let newIdx = Math.min(Math.max(idx + delta, 0), zoomSteps.length - 1);
    setZoom(zoomSteps[newIdx]);
  }

  document.getElementById('btnZoomIn')?.addEventListener('click', ()=> stepZoom(+1));
  document.getElementById('btnZoomOut')?.addEventListener('click', ()=> stepZoom(-1));
  document.getElementById('btnFitW')?.addEventListener('click', ()=> setZoom('page-width'));
  document.getElementById('btnFitP')?.addEventListener('click', ()=> setZoom('page-fit'));

  // Modo leitura (toggle)
  function toggleReader(){
    document.body.classList.toggle('reader-compact');
  }
  btnRead?.addEventListener('click', toggleReader);

  // Atalhos
  document.addEventListener('keydown', (ev)=>{
    const k = ev.key.toLowerCase();
    if (k === 'd') document.querySelector('a[download]')?.click();
    if (k === 'o') document.querySelector('a[target="_blank"]')?.click();
    if (k === 'p') window.print();
    if (k === '+') stepZoom(+1);
    if (k === '-') stepZoom(-1);
    if (k === 'w') setZoom('page-width');
    if (k === 'f') setZoom('page-fit');
    if (k === 'l') toggleReader();
  });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
