<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$user = $_SESSION['user'] ?? null;

require_once __DIR__ . '/../lib/db.php';
$pdo = db();

/* =========================
   Catálogo via SQLite
   ========================= */
function fetchByGenero(PDO $pdo, string $genero, int $limit = 8): array {
  $stmt = $pdo->prepare("
    SELECT id, titulo, preco, capa_path AS img, descricao
    FROM livros
    WHERE genero = :g
    ORDER BY criado_em DESC
    LIMIT :lim
  ");
  $stmt->bindValue(':g', $genero);
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$destaque = $pdo->query("
  SELECT id, titulo, preco, capa_path AS img, descricao
  FROM livros
  ORDER BY criado_em DESC
  LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$catalogo = [
  'destaque'     => $destaque,
  'infantil'     => fetchByGenero($pdo, 'Infantil'),
  'romance'      => fetchByGenero($pdo, 'Romance'),
  'documentario' => fetchByGenero($pdo, 'Não Ficção'),
  'terror'       => fetchByGenero($pdo, 'Terror'),
  'misterio'     => fetchByGenero($pdo, 'Mistério'),
  'ficcao'       => fetchByGenero($pdo, 'Ficção Científica'),
  'fantasia'     => fetchByGenero($pdo, 'Fantasia'),
  'suspense'     => fetchByGenero($pdo, 'Suspense/Thriller'),
  'poesia'       => fetchByGenero($pdo, 'Poesia'),
  'biografia'    => fetchByGenero($pdo, 'Biografia'),
  'didatico'     => fetchByGenero($pdo, 'Didático')
];

/* =========================
   Favoritos do usuário
   ========================= */
$userFavIds = [];
if (isset($user['id'])) {
  $uid = (int)$user['id'];
  try {
    $st = $pdo->prepare("SELECT livro_id FROM user_favoritos WHERE user_id = :uid");
    $st->execute([':uid' => $uid]);
    $userFavIds = array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC), 'livro_id'));
  } catch (Throwable $e) {
    $userFavIds = [];
  }
}
$userFavJson = json_encode($userFavIds, JSON_UNESCAPED_SLASHES);

/* =========================
   Helpers usados por secoes.php
   ========================= */
function getOffset(string $slug, int $total, int $pageSize): int{
  $k = "offset_$slug";
  $o = isset($_GET[$k]) ? intval($_GET[$k]) : 0;
  if($total>0){
    $o = $o % $total;
    if($o<0) $o += $total;
    $o = intdiv($o, $pageSize) * $pageSize % max($total,1);
  }
  return $o;
}

function renderCards(array $itens, int $inicio, int $qtd): string{
  // usa $userFavIds para pintar o toggle
  $userFavIds = $GLOBALS['userFavIds'] ?? [];
  $html=''; $n=count($itens); if(!$n) return $html;

  // URL atual para redirecionar após favoritar/desfavoritar
  $currentUrl = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/home/main.php', ENT_QUOTES, 'UTF-8');

  for($i=0;$i<$qtd;$i++){
    $idx = ($inicio + $i) % $n;
    $lv = $itens[$idx];

    $id     = (int)($lv['id'] ?? 0);
    $img    = htmlspecialchars((string)($lv['img'] ?? ''), ENT_QUOTES, 'UTF-8');
    $titulo = htmlspecialchars((string)($lv['titulo'] ?? ''), ENT_QUOTES, 'UTF-8');
    $preco  = number_format((float)($lv['preco'] ?? 0), 2, ',', '.');
    $href   = "/public/livro/ver.php?id=".$id;

    $badge  = ($i % 3 === 0) ? "<span class='badge-top'>Top</span>" : "";
    $isFav  = in_array($id, $userFavIds, true);

    // DESC para o modal
    $desc   = htmlspecialchars((string)($lv['descricao'] ?? ''), ENT_QUOTES, 'UTF-8');

    $favBtn = $isFav
      ? "<form method='post' action='/favoritos/remover.php' class='d-inline'>
           <input type='hidden' name='livro_id' value='{$id}'>
           <input type='hidden' name='redirect' value='{$currentUrl}'>
           <button class='btn-fav active' type='submit' title='Remover dos favoritos' aria-label='Remover dos favoritos'>
             <i class='bi bi-heart-fill'></i>
           </button>
         </form>"
      : "<form method='post' action='/favoritos/adicionar.php' class='d-inline'>
           <input type='hidden' name='livro_id' value='{$id}'>
           <input type='hidden' name='redirect' value='{$currentUrl}'>
           <button class='btn-fav' type='submit' title='Adicionar aos favoritos' aria-label='Adicionar aos favoritos'>
             <i class='bi bi-heart'></i>
           </button>
         </form>";

    // Botão de detalhes abre modal
    $eyeBtn = "<button type='button' class='btn-eye btn-details'
                data-bs-toggle='modal'
                data-bs-target='#detalheModal'
                data-title='{$titulo}'
                data-desc='{$desc}'
                aria-label='Ver detalhes'
                title='Ver detalhes'>
                <i class='bi bi-search'></i>
              </button>";

    $html .= "
      <div class='col' style='--i:{$i}'>
        <div class='card h-100' data-tilt='1'>
          {$badge}
          <div class='card-actions'>
            {$favBtn}
            {$eyeBtn}
          </div>

          <a class='card-fig' href='{$href}' title='Ler {$titulo}'>
            <img src='{$img}' alt='Capa do livro' class='card-img-top' loading='lazy'>
          </a>

          <div class='card-body'>
            <h6 class='card-title mb-1'>
              <a href='{$href}' class='link-underline link-underline-opacity-0'>{$titulo}</a>
            </h6>
            <p class='card-price mb-0'>R$ {$preco}</p>
          </div>
        </div>
      </div>";
  }
  return $html;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>LeiaTudo - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/default.css?v=2" />
  <style>
    :root{
      --cards-gap:1rem;
      --card-radius:1rem;
      --btn-size:44px;
      --shadow: 0 10px 30px rgba(0,0,0,.10);
      --shadow-hover: 0 16px 40px rgba(0,0,0,.14);
      --grad: linear-gradient(135deg,#4da3ff, #7cc1ff 60%, #cfeaff);
    }

    .home-body{ background:#fff; }
    h2{ font-weight:700; letter-spacing:.2px; }

    .section-head{ gap:.75rem; flex-wrap:wrap; }
    .section-actions{ display:flex; align-items:center; gap:.5rem; }
    .nav-btn{
      width:var(--btn-size); height:var(--btn-size);
      display:inline-flex; align-items:center; justify-content:center;
      border-radius:999px; position:relative; overflow:hidden;
      user-select:none;
    }
    .nav-btn::after{
      content:''; position:absolute; inset:0; transform:scale(0);
      background:rgba(255,255,255,.35); border-radius:inherit;
      transition:transform .25s ease, opacity .25s ease; opacity:0;
    }
    .nav-btn:active::after{ transform:scale(1); opacity:1; }
    .nav-btn:focus-visible{ outline:3px solid rgba(13,110,253,.35); outline-offset:2px; }

    .cards-viewport{ position:relative; overflow:hidden; }
    .cards{ transition: opacity .2s ease; }
    .cards .col{ display:flex; }

    .cards-layer{ position:absolute; inset:0; z-index:1; pointer-events:none; }
    .slide-anim{ transition: transform .5s cubic-bezier(.22,.61,.36,1), opacity .5s ease; will-change:transform,opacity; }
    .slide-in-right{  transform: translateX(70px);  opacity:0; }
    .slide-in-left{   transform: translateX(-70px); opacity:0; }
    .slide-out-left{  transform: translateX(-70px); opacity:0; }
    .slide-out-right{ transform: translateX(70px);  opacity:0; }

    .card{
      width:100%; border:none; border-radius:var(--card-radius);
      box-shadow:var(--shadow); overflow:hidden; background:#fff;
      position:relative; transform:translateZ(0);
      transition: box-shadow .22s ease, transform .22s ease;
      isolation:isolate;
    }
    .card:hover{ box-shadow:var(--shadow-hover); transform: translateY(-2px); }

    .card::before{
      content:''; position:absolute; inset:0; border-radius:inherit; padding:1px;
      background:var(--grad);
      -webkit-mask-composite: xor; mask-composite: exclude; opacity:.28; pointer-events:none;
    }

    .card-fig{ position:relative; overflow:hidden; }
    .card-img-top{
      width:100%; aspect-ratio: 3/4; object-fit:cover; background:#f5f7fa;
      transform:scale(1.03); opacity:0; transition: transform .35s ease, opacity .35s ease;
    }
    .card.loaded .card-img-top{ opacity:1; }
    .card:hover .card-img-top{ transform:scale(1.00); }

    .card-actions{
      position:absolute; top:.5rem; right:.5rem; display:flex; gap:.45rem; z-index:2;
      opacity:0; transition: opacity .2s ease;
    }
    .card:hover .card-actions{ opacity:1; }

    .badge-top{
      position:absolute; left:.75rem; top:.75rem; z-index:2;
      background: linear-gradient(135deg,#ff7d7d,#ffb199); color:#fff; border-radius:999px;
      padding:.25rem .6rem; font-weight:600; font-size:.72rem; box-shadow:0 4px 10px rgba(0,0,0,.15);
      opacity:0; transition: opacity .2s ease;
    }
    .card:hover .badge-top{ opacity:1; }

    .card-body{ padding:.8rem .9rem; }
    .card-title{ font-weight:700; line-height:1.25; }
    .card-price{ font-weight:800; letter-spacing:.2px; color:#111; }

    /* Favorito — clean, profissional */
    .btn-fav{
      width:38px; height:38px; border-radius:50%; border:none;
      display:inline-flex; align-items:center; justify-content:center;
      background:rgba(255,255,255,.92);
      box-shadow:0 3px 8px rgba(0,0,0,.12);
      transition: all .22s ease;
    }
    .btn-fav i{ font-size:1.15rem; color:#6c757d; transition: color .22s ease, transform .22s ease; }
    .btn-fav:hover{ background:#fff; transform:scale(1.06); }
    .btn-fav:hover i{ color:#dc3545; }
    .btn-fav.active{ background:#ffe6f0; box-shadow:0 4px 10px rgba(220,53,69,.28); }
    .btn-fav.active i{ color:#e83e8c; }

    /* Details */
    .btn-eye{
      width:38px; height:38px; border-radius:50%; border:none;
      display:inline-flex; align-items:center; justify-content:center;
      background:rgba(255,255,255,.92);
      box-shadow:0 3px 8px rgba(0,0,0,.12);
      transition: all .22s ease;
      text-decoration:none;
    }
    .btn-eye i{ font-size:1.05rem; color:#0d6efd; transition: color .22s ease; }
    .btn-eye:hover{ background:#fff; transform:scale(1.06); }
    .btn-eye:hover i{ color:#0a58ca; }

    @media (max-width:575.98px){
      .cards-viewport{ margin-inline:-.25rem; }
      .section-actions{ width:100%; justify-content:flex-end; }
    }
    @media (prefers-reduced-motion: reduce){
      .slide-anim, .cards .card, .card-img-top, .card, .card-price{
        animation:none !important; transition:none !important;
      }
    }
  </style>
</head>
<body class="home-body text-center">

<?php include __DIR__ . '/navbar.php'; ?>

<?php
// ====== FLASH por sessão (em vez de ?msg= na URL) ======
$flash = $_SESSION['flash'] ?? null;
if (is_array($flash) && !empty($flash['type']) && array_key_exists('html', $flash)) {
  $type = htmlspecialchars((string)$flash['type'], ENT_QUOTES, 'UTF-8');
  $html = (string)$flash['html']; // já vem pronto e seguro do server
  echo '<div class="container mt-3"><div class="alert alert-'.$type.' shadow-sm auto-dismiss" role="alert">'.$html.'</div></div>';
}
unset($_SESSION['flash']); // consome o flash (não reaparece no reload)
?>

<?php include __DIR__ . '/secoes.php'; ?>

<!-- Modal de Detalhes -->
<div class="modal fade" id="detalheModal" tabindex="-1" aria-labelledby="detalheModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold" id="detalheModalLabel">Detalhes do E-book</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p id="detalheModalDesc" class="mb-0 text-secondary"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
// IDs favoritados (se logado) — usado no render JS
window.userFavIds = <?= $userFavJson ?: '[]' ?>;
</script>

<script>
(function(){
  function scrollSectionIntoCenter(section, opts={}){
    const nav = document.querySelector('.navbar');
    const navH = nav ? nav.offsetHeight : 0;
    const rect = section.getBoundingClientRect();
    const pageY = window.pageYOffset || document.documentElement.scrollTop || 0;
    const vh = window.innerHeight || document.documentElement.clientHeight;
    const bigSection = rect.height > vh * 0.9;
    const margin = 12;
    let targetY;
    if (bigSection) {
      targetY = pageY + rect.top - navH - margin;
    } else {
      const centerY = pageY + rect.top + (rect.height/2);
      targetY = centerY - (vh/2) - navH/2;
    }
    targetY = Math.max(0, Math.round(targetY));
    window.scrollTo({ top: targetY, behavior: (opts.behavior || 'smooth') });
  }

  function enableHashCentering(){
    document.querySelectorAll('a[href^="#sec-"]').forEach(a=>{
      a.addEventListener('click',(ev)=>{
        const id = a.getAttribute('href');
        const el = document.querySelector(id);
        if(el){ ev.preventDefault(); history.pushState(null,'',id); scrollSectionIntoCenter(el); }
      });
    });
    function handleHash(){
      const id = decodeURIComponent(location.hash||''); if(!id) return;
      const el = document.querySelector(id);
      if(el){ setTimeout(()=>scrollSectionIntoCenter(el,{behavior:'smooth'}), 0); }
    }
    window.addEventListener('hashchange', handleHash);
    handleHash();
  }

  const sections = document.querySelectorAll('section[data-genre]');
  const favSet = new Set(Array.isArray(window.userFavIds) ? window.userFavIds : []);

  sections.forEach(section=>{
    const items = JSON.parse(section.dataset.items||'[]');
    const viewport = section.querySelector('.cards-viewport');
    let cardsEl = viewport.querySelector('.cards');
    const pageSize = Number(section.dataset.pageSize||6)||6;
    let offset = Number(section.dataset.offset||0)||0;
    let animating = false;

    function renderTo(container){
      const n = items.length;
      let html = '';
      for (let i=0;i<Math.min(pageSize,n);i++){
        const it = items[(offset+i)%n];
        const preco = (Number(it.preco)||0).toFixed(2).replace('.', ',');
        const badge = (i % 3 === 0) ? "<span class='badge-top'>Top</span>" : "";
        const href = `/public/livro/ver.php?id=${encodeURIComponent(it.id)}`;
        const isFav = favSet.has(it.id);

        // redirect dinâmico (voltar para a mesma URL/hash)
        const redirectValue = `${location.pathname}${location.search}${location.hash}`;

        const favForm = isFav
          ? `
            <form method="post" action="/favoritos/remover.php" class="d-inline">
              <input type="hidden" name="livro_id" value="${String(it.id)}">
              <input type="hidden" name="redirect" value="${redirectValue}">
              <button class="btn-fav active" type="submit" title="Remover dos favoritos" aria-label="Remover dos favoritos">
                <i class="bi bi-heart-fill"></i>
              </button>
            </form>`
          : `
            <form method="post" action="/favoritos/adicionar.php" class="d-inline">
              <input type="hidden" name="livro_id" value="${String(it.id)}">
              <input type="hidden" name="redirect" value="${redirectValue}">
              <button class="btn-fav" type="submit" title="Adicionar aos favoritos" aria-label="Adicionar aos favoritos">
                <i class="bi bi-heart"></i>
              </button>
            </form>`;

        // Botão de detalhes abrindo o modal com descrição (vem do data-items)
        const safeTitle = String(it.titulo || '').replace(/"/g,'&quot;');
        const safeDesc  = String(it.descricao || '').replace(/"/g,'&quot;');
        const eyeBtn = `
          <button type="button" class="btn-eye btn-details"
                  data-bs-toggle="modal"
                  data-bs-target="#detalheModal"
                  data-title="${safeTitle}"
                  data-desc="${safeDesc}"
                  aria-label="Ver detalhes"
                  title="Ver detalhes">
            <i class="bi bi-search"></i>
          </button>`;

        html += `
        <div class="col" style="--i:${i}">
          <div class="card h-100" data-tilt="1">
            ${badge}
            <div class="card-actions">
              ${favForm}
              ${eyeBtn}
            </div>

            <a class="card-fig" href="${href}" title="Ler ${safeTitle}">
              <img src="${it.img}" class="card-img-top" alt="Capa do livro" loading="lazy">
            </a>

            <div class="card-body">
              <h6 class="card-title mb-1">
                <a href="${href}" class="link-underline link-underline-opacity-0">${safeTitle}</a>
              </h6>
              <p class="card-price mb-0">R$ ${preco}</p>
            </div>
          </div>
        </div>`;
      }

      container.innerHTML = html;
      container.querySelectorAll('.card .card-img-top').forEach(img=>{
        if (img.complete) img.closest('.card').classList.add('loaded');
        else img.addEventListener('load', ()=> img.closest('.card').classList.add('loaded'), {once:true});
      });
    }

    function firstRender(){
      // Renderiza na carga — já exibe o botão de favorito
      renderTo(cardsEl);
      requestAnimationFrame(()=>cardsEl.classList.add('ready'));
      cardsEl.querySelectorAll('.card .card-img-top').forEach(img=>{
        if (img.complete) img.closest('.card').classList.add('loaded');
        else img.addEventListener('load', ()=> img.closest('.card').classList.add('loaded'), {once:true});
      });
    }

    function step(dir){
      if (animating) return;
      const n = items.length;
      if (!n) return;
      animating = true;

      offset = (offset + dir*pageSize) % n;
      if (offset < 0) offset += n;

      const currentHeight = viewport.clientHeight;
      viewport.style.height = currentHeight + 'px';

      const oldLayer = cardsEl.cloneNode(true);
      oldLayer.classList.remove('ready');
      oldLayer.classList.add('cards-layer','slide-anim',
                             dir>0 ? 'slide-out-left' : 'slide-out-right');

      const newLayer = cardsEl.cloneNode(false);
      newLayer.className = cardsEl.className;
      newLayer.classList.add('cards-layer','slide-anim',
                             dir>0 ? 'slide-in-right' : 'slide-in-left');
      renderTo(newLayer);

      viewport.innerHTML = '';
      viewport.appendChild(oldLayer);
      viewport.appendChild(newLayer);

      requestAnimationFrame(()=>{
        oldLayer.style.transform = (dir>0 ? 'translateX(-60px)' : 'translateX(60px)');
        oldLayer.style.opacity = '0';
        newLayer.style.transform = 'translateX(0)';
        newLayer.style.opacity = '1';
      });

      const onDone = () => {
        const stable = cardsEl.cloneNode(false);
        stable.className = cardsEl.className;
        stable.innerHTML = newLayer.innerHTML;
        viewport.innerHTML = '';
        viewport.appendChild(stable);
        cardsEl = stable;

        requestAnimationFrame(()=>cardsEl.classList.add('ready'));
        viewport.style.height = '';
        animating = false;

        scrollSectionIntoCenter(section);
      };
      newLayer.addEventListener('transitionend', onDone, {once:true});
      setTimeout(()=>{ if(animating){ onDone(); } }, 700);
    }

    section.querySelectorAll('button[data-dir]').forEach(btn=>{
      btn.addEventListener('click', ()=> step(Number(btn.dataset.dir)));
    });

    section.addEventListener('keydown', (ev)=>{
      if (ev.key === 'ArrowRight') step(1);
      if (ev.key === 'ArrowLeft')  step(-1);
    });

    firstRender();
  });

  enableHashCentering();

  document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.card .card-img-top').forEach(img=>{
      if (img.complete) img.closest('.card').classList.add('loaded');
      else img.addEventListener('load', ()=> img.closest('.card').classList.add('loaded'), {once:true});
    });
  });

  // Preenche o modal quando abrir (lê os data-attributes do botão)
  (function(){
    const modalEl = document.getElementById('detalheModal');
    if (!modalEl) return;
    modalEl.addEventListener('show.bs.modal', function (event) {
      const btn = event.relatedTarget;
      if (!btn) return;
      const title = btn.getAttribute('data-title') || 'Detalhes do E-book';
      const desc  = btn.getAttribute('data-desc')  || 'Sem descrição.';
      const titleEl = modalEl.querySelector('#detalheModalLabel');
      const descEl  = modalEl.querySelector('#detalheModalDesc');
      if (titleEl) titleEl.textContent = title;
      if (descEl)  descEl.textContent  = desc;
    });
  })();

})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-dismiss dos alerts em ~3s (após Bootstrap carregado)
document.addEventListener('DOMContentLoaded', function(){
  const alerts = document.querySelectorAll('.alert.auto-dismiss');
  alerts.forEach(el=>{
    setTimeout(()=>{
      try {
        const inst = bootstrap.Alert.getOrCreateInstance(el);
        inst.close();
      } catch(e) {
        el.style.transition = 'opacity .3s ease';
        el.style.opacity = '0';
        setTimeout(()=> el.remove(), 300);
      }
    }, 3000);
  });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
