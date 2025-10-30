<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../lib/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo "ID inválido."; exit; }

$pdo = db();
$stmt = $pdo->prepare("
  SELECT id, titulo, autor, pdf_path, capa_path, criado_em, criado_por_id, criado_por_username
  FROM livros WHERE id=:id LIMIT 1
");
$stmt->execute([':id'=>$id]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$livro) { http_response_code(404); echo "Livro não encontrado."; exit; }

$pdfWeb = (string)$livro['pdf_path'];
if (strpos($pdfWeb,'/public/uploads/pdfs/')!==0) { http_response_code(400); echo "Caminho do PDF inválido."; exit; }
$capaWeb = (string)($livro['capa_path'] ?? '');
$ownerId = $livro['criado_por_id'] ?? null;
$ownerName = $livro['criado_por_username'] ?? '';
$currUser = $_SESSION['user'] ?? null;
$canDelete = $currUser && isset($currUser['id']) && (string)$currUser['id']===(string)$ownerId;

require_once __DIR__ . '/../lib/csrf.php';
$csrf = csrf_token();

$pdfAbs = __DIR__."/..".substr($pdfWeb, strlen('/public'));
$pdfSize = is_file($pdfAbs)?filesize($pdfAbs):null;
function humanSize(?int $bytes): string {
  if ($bytes===null||$bytes<0) return '';
  $u=['B','KB','MB','GB','TB']; $i=0; $v=(float)$bytes;
  while($v>=1024&&$i<count($u)-1){$v/=1024;$i++;}
  return number_format($v,($i===0?0:2),',','.').' '.$u[$i];
}

$titulo = $livro['titulo'] ?? 'E-book';
$autor = $livro['autor'] ?? '';
$criado = $livro['criado_em'] ?? null;
$capaUrl = $capaWeb ? htmlspecialchars($capaWeb, ENT_QUOTES,'UTF-8'):'';
$tituloEsc = htmlspecialchars($titulo,ENT_QUOTES,'UTF-8');
$autorEsc = htmlspecialchars($autor,ENT_QUOTES,'UTF-8');
$tamEsc = humanSize($pdfSize);
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
  --brand:#0573b3;--brand-light:#0a8fd9;--text-muted:#6b7b8c;
  --surface:#fff;--header-h:70px;--shadow:0 6px 24px rgba(0,0,0,.08);
  --transition-speed:0.25s;
}
body{
  margin:0;font-family:"Inter",system-ui,sans-serif;background:#f7f9fc;color:#0f172a;
}
header{
  height:var(--header-h);background:var(--surface);box-shadow:var(--shadow);
  display:flex;align-items:center;padding:0 1rem;position:sticky;top:0;z-index:50;
  flex-wrap:wrap;justify-content:space-between;
}
.brand{display:flex;align-items:center;gap:.5rem;text-decoration:none;}
.brand img{width:48px;height:48px;border-radius:12px;transition:transform var(--transition-speed);}
.brand:hover img{transform:scale(1.05);}
.brand-title{font-weight:700;font-size:1.3rem;color:var(--brand);}
.action-bar{display:flex;align-items:center;flex-wrap:wrap;gap:.5rem;}
.btn{border-radius:12px!important;font-weight:500;transition:all var(--transition-speed);}
.btn-primary{background:var(--brand)!important;border-color:var(--brand)!important;}
.btn-primary:hover{background:var(--brand-light)!important;transform:translateY(-2px);}
.btn-danger:hover{transform:translateY(-2px);}
.meta-strip{
  background:#fff;box-shadow:0 6px 20px rgba(0,0,0,.08);
  margin:1rem auto;border-radius:16px;padding:1rem 1.5rem;
  display:flex;gap:1rem;align-items:center;width:90%;max-width:1000px;transition:all .3s;
}
.meta-strip:hover{box-shadow:0 8px 28px rgba(0,0,0,.12);}
.cover{width:100px;height:140px;border-radius:14px;object-fit:cover;box-shadow:0 4px 16px rgba(0,0,0,.08);transition:transform .3s;}
.cover:hover{transform:scale(1.05);}
.book-title{font-weight:700;margin-bottom:.3rem;font-size:1.25rem;}
.book-sub{color:var(--text-muted);font-size:.95rem;}
#pdf-canvas{
  border-radius:10px;border:1px solid #ccc;box-shadow:var(--shadow);
  display:block;margin:2rem auto;transition:all var(--transition-speed);background:#fff;
}
.viewer-controls{
  display:flex;align-items:center;gap:.3rem;justify-content:center;margin:0 auto 1rem auto;
}
#zoomRange{width:80px;}
.reader-toggle{
  position:fixed;bottom:1.2rem;right:1.2rem;z-index:120;border-radius:50%;
  width:50px;height:50px;display:flex;align-items:center;justify-content:center;
  box-shadow:0 6px 24px rgba(0,0,0,.15);background:#fff;cursor:pointer;transition:all .25s;
}
.reader-toggle:hover{transform:scale(1.15);background:#f0f0f0;}
.reader-btn{
  position:fixed;bottom:1.2rem;right:1.2rem;z-index:120;border-radius:50%;
  width:50px;height:50px;display:flex;align-items:center;justify-content:center;
  box-shadow:0 6px 24px rgba(0,0,0,.15);background:#fff;cursor:pointer;transition:all .25s;
}
.reader-btn:hover{transform:scale(1.15);background:#f0f0f0;}

/* Modo leitura */
.reader-compact header,
.reader-compact .meta-strip,
.reader-compact .viewer-controls,
.reader-compact .reader-btn{
    display:none !important;
}

/* Barra mínima para navegar páginas no modo leitura */
.reader-minibar {
  position: fixed;
  bottom: 1.2rem;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(255,255,255,0.7);
  padding: 0.3rem 0.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0,0,0,.2);
  display: none;
  align-items: center;
  gap: 0.5rem;
  z-index: 110;
  font-size: 0.9rem;
  transition: background 0.25s;
}
.reader-minibar:hover { background: rgba(255,255,255,1); }
.reader-minibar button { padding:0.25rem 0.5rem; border-radius:8px; }
.reader-compact .reader-minibar { display:flex !important; }

/* Aviso temporário */
#readerTip{
  position:fixed;bottom:5.5rem;left:50%;transform:translateX(-50%);
  background:rgba(0,0,0,0.75);color:#fff;padding:.5rem 1rem;
  border-radius:12px;font-size:.9rem;z-index:115;display:none;
}
</style>
</head>
<body>

<header>
<a class="brand" href="/home/main.php">
<img src="/css/imgs/logo.png" alt="LeiaTudo"><span class="brand-title">LeiaTudo</span>
</a>

<div class="viewer-controls">
  <button id="prevPage" class="btn btn-sm btn-primary"><i class="bi bi-arrow-left"></i></button>
  <span>Pág: <span id="page_num">1</span> / <span id="page_count">1</span></span>
  <button id="nextPage" class="btn btn-sm btn-primary"><i class="bi bi-arrow-right"></i></button>
  <input type="range" id="zoomRange" min="100" max="250" value="150">
  <span id="zoomVal">150%</span>
  <button id="fitWidth" class="btn btn-sm btn-primary">Largura</button>
  <button id="fitPage" class="btn btn-sm btn-primary">Página</button>
</div>

<div class="action-bar">
    <a href="/home/main.php" class="btn btn-outline-primary btn-sm">Home</a>
    <button id="eyeComfort" class="btn btn-sm btn-warning" title="Conforto Ocular">
      <i class="bi bi-eye"></i>
    </button>

    <?php if($canDelete): ?>
    <a href="/public/livro/editar.php?id=<?= (int)$id ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> Editar</a>
    <form action="/public/livro/excluir.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este livro?');">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf,ENT_QUOTES,'UTF-8') ?>">
        <button class="btn btn-danger btn-sm" type="submit"><i class="bi bi-trash3"></i> Excluir</button>
    </form>
    <?php endif; ?>
</div>

</header>

<section class="meta-strip">
<?php if($capaUrl): ?>
<img src="<?= $capaUrl ?>" class="cover" alt="Capa do livro">
<?php else: ?>
<div class="cover d-flex align-items-center justify-content-center bg-light text-muted">
<i class="bi bi-book" style="font-size:1.5rem;"></i>
</div>
<?php endif; ?>
<div>
<h1 class="book-title h5 mb-1"><?= $tituloEsc ?></h1>
<p class="book-sub mb-0">
<?php if($autorEsc): ?>de <strong><?= $autorEsc ?></strong><?php endif; ?>
<?php if($tamEsc): ?><span class="ms-2"><?= $tamEsc ?></span><?php endif; ?>
<?php if($criado): ?><span class="ms-2 text-secondary"><?= htmlspecialchars($criado,ENT_QUOTES,'UTF-8') ?></span><?php endif; ?>
<?php if($ownerName): ?><span class="ms-2 text-secondary">Por: <?= htmlspecialchars($ownerName,ENT_QUOTES,'UTF-8') ?></span><?php endif; ?>
</p>
</div>
</section>

<canvas id="pdf-canvas"></canvas>

<!-- Botões -->
<button id="exitRead" class="reader-toggle d-none" title="Sair do modo leitura"><i class="bi bi-x-lg"></i></button>
<button id="enterRead" class="reader-btn" title="Entrar no modo leitura"><i class="bi bi-eye"></i></button>

<!-- Barra mínima para navegação páginas no modo leitura -->
<div id="readerMiniBar" class="reader-minibar">
  <button id="prevPageMini" class="btn btn-sm btn-primary"><i class="bi bi-arrow-left"></i></button>
  <span>Pág: <span id="page_num_mini">1</span> / <span id="page_count_mini">1</span></span>
  <button id="nextPageMini" class="btn btn-sm btn-primary"><i class="bi bi-arrow-right"></i></button>
</div>

<!-- Aviso temporário -->
<div id="readerTip">Pressione a tecla 'L' para sair do modo leitura</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.10.111/pdf.min.js"></script>
<script>
// PDF setup
const url = "<?= $pdfWeb ?>";
const canvas = document.getElementById("pdf-canvas");
const ctx = canvas.getContext("2d");
const pageNumElem = document.getElementById("page_num");
const pageCountElem = document.getElementById("page_count");
const zoomRange = document.getElementById("zoomRange");
const zoomVal = document.getElementById("zoomVal");
const saveKey = "pdf_page_<?= (int)$id ?>_<?= (int)($currUser['id'] ?? 0) ?>"; // inclui user id para isolar progresso por usuário

// Modo leitura elementos
const exitBtn = document.getElementById("exitRead");
const enterBtn = document.getElementById("enterRead");
const miniBar = document.getElementById("readerMiniBar");
const pageNumMini = document.getElementById("page_num_mini");
const pageCountMini = document.getElementById("page_count_mini");
const tip = document.getElementById("readerTip");

// Conforto ocular
const eyeBtn = document.getElementById("eyeComfort");
const eyeIcon = eyeBtn.querySelector("i");
let eyeOn = false;
eyeBtn.addEventListener("click", () => {
    eyeOn = !eyeOn;
    if(eyeOn){
        document.body.style.filter = "sepia(0.5) brightness(1.05) contrast(0.9)";
        eyeIcon.classList.replace("bi-eye","bi-eye-slash");
    } else {
        document.body.style.filter = "none";
        eyeIcon.classList.replace("bi-eye-slash","bi-eye");
    }
});

let pdfDoc = null, pageNum=1, pageRendering=false, pageNumPending=null;
let scale = 1.5;

function renderPage(num){
  pageRendering=true;
  pdfDoc.getPage(num).then(page=>{
    const viewport = page.getViewport({scale});
    canvas.height=viewport.height;
    canvas.width=viewport.width;
    const renderCtx={canvasContext:ctx,viewport};
    page.render(renderCtx).promise.then(()=>{
      pageRendering=false;
      if(pageNumPending!==null){renderPage(pageNumPending); pageNumPending=null;}
    });
    pageNumElem.textContent=num;
    pageNumMini.textContent=num;
    pageCountMini.textContent=pdfDoc.numPages;
    localStorage.setItem(saveKey,num);
  });
}

function queueRenderPage(num){pageRendering?pageNumPending=num:renderPage(num);}
function onPrevPage(){if(pageNum<=1)return; pageNum--; queueRenderPage(pageNum);}
function onNextPage(){if(pageNum>=pdfDoc.numPages)return; pageNum++; queueRenderPage(pageNum);}
function setZoom(v){scale=v; queueRenderPage(pageNum);}

pdfjsLib.getDocument(url).promise.then(doc=>{
  pdfDoc=doc;
  pageCountElem.textContent=doc.numPages;
  pageCountMini.textContent=doc.numPages;
  const savedPage=parseInt(localStorage.getItem(saveKey));
  if(savedPage>=1 && savedPage<=doc.numPages) pageNum=savedPage;
  renderPage(pageNum);
});

// Eventos principais
document.getElementById("prevPage").addEventListener("click",onPrevPage);
document.getElementById("nextPage").addEventListener("click",onNextPage);
document.getElementById("fitWidth").addEventListener("click",()=>{scale=2.5; zoomRange.value=250; zoomVal.textContent="250%"; queueRenderPage(pageNum);});
document.getElementById("fitPage").addEventListener("click",()=>{scale=1; zoomRange.value=100; zoomVal.textContent="100%"; queueRenderPage(pageNum);});
zoomRange.addEventListener("input",()=>{scale=parseInt(zoomRange.value)/100; zoomVal.textContent=zoomRange.value+"%"; queueRenderPage(pageNum);});

// Modo leitura
function toggleReader(force){
  const on = document.body.classList.toggle("reader-compact", force);
  exitBtn.classList.toggle("d-none", !on);
  miniBar.classList.toggle("d-none", !on);
  enterBtn.classList.toggle("d-none", on);

  if(on){
    tip.style.display="block";
    setTimeout(()=>tip.style.display="none",4000);
  }
}

exitBtn.addEventListener("click",()=>toggleReader(false));
enterBtn.addEventListener("click",()=>toggleReader(true));
document.addEventListener("keydown", e=>{if(e.key.toLowerCase()==='l') toggleReader();});

// MiniBar navegação
document.getElementById("prevPageMini").addEventListener("click",()=>onPrevPage());
document.getElementById("nextPageMini").addEventListener("click",()=>onNextPage());
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
