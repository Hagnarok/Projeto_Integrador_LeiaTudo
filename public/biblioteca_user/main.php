<?php
// public/biblioteca_user/main.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Exigir login
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
  header('Location: /login/main.php?next=/biblioteca_user/main.php');
  exit;
}

$user = $_SESSION['user'];
$uid  = (int)($user['id'] ?? 0);

// DB
require_once __DIR__ . '/../lib/db.php';
$pdo = db();

/* =========================
   DADOS DO USUÁRIO
   ========================= */

// IDs já favoritados (para pintar o botão/definir toggle)
$favIdsStmt = $pdo->prepare("SELECT livro_id FROM user_favoritos WHERE user_id = :uid");
$favIdsStmt->execute([':uid' => $uid]);
$favoritadosIds = array_map('intval', array_column($favIdsStmt->fetchAll(PDO::FETCH_ASSOC), 'livro_id'));

// Seção: Favoritos (lista de livros favoritados)
$favStmt = $pdo->prepare("
  SELECT l.id, l.titulo, l.preco, l.publicado_por, l.criado_por_username, l.capa_path AS img, uf.criado_em AS favoritado_em
  FROM user_favoritos uf
  JOIN livros l ON l.id = uf.livro_id
  WHERE uf.user_id = :uid
  ORDER BY uf.criado_em DESC
");
$favStmt->execute([':uid'=>$uid]);
$favoritos = $favStmt->fetchAll(PDO::FETCH_ASSOC);

// Seção: Meus livros (cadastrados por mim)
$mineStmt = $pdo->prepare("
  SELECT id, titulo, preco, publicado_por, criado_por_username, capa_path AS img, criado_em
  FROM livros
  WHERE criado_por_id = :uid
  ORDER BY criado_em DESC
");
$mineStmt->execute([':uid' => $uid]);
$meusLivros = $mineStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   HELPERS / PAGINAÇÃO
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

function sliceWindow(array $arr, int $offset, int $pageSize): array{
  $n = count($arr);
  if ($n === 0) return [];
  $out = [];
  for ($i=0; $i<min($pageSize,$n); $i++) {
    $idx = ($offset + $i) % $n;
    $out[] = $arr[$idx];
  }
  return $out;
}

function formatItemsForDataAttr(array $arr): string{
  $out = [];
  foreach($arr as $it){
    $out[] = [
      'id'     => (int)$it['id'],
      'titulo' => (string)$it['titulo'],
      'publicado_por'  => (string)($it['publicado_por'] ?? ''),
      'criado_por_username' => (string)($it['criado_por_username'] ?? ''),
      'img'    => (string)($it['img'] ?? ''),
    ];
  }
  return htmlspecialchars(json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}

$pageSize = 6;

// offsets e “janelas” de exibição
$totFav  = count($favoritos);
$offFav  = getOffset('favoritos', $totFav, $pageSize);
$viewFav = sliceWindow($favoritos, $offFav, $pageSize);

$totMine  = count($meusLivros);
$offMine  = getOffset('meus', $totMine, $pageSize);
$viewMine = sliceWindow($meusLivros, $offMine, $pageSize);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Minha biblioteca • LeiaTudo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
    body.home-body{ background:#fff; }
    h1,h2{ font-weight:700; letter-spacing:.2px; }
    .section-head{ gap:.75rem; flex-wrap:wrap; }
    .section-actions{ display:flex; align-items:center; gap:.5rem; }
    .nav-btn{
      width:var(--btn-size); height:var(--btn-size);
      display:inline-flex; align-items:center; justify-content:center;
      border-radius:999px; position:relative; overflow:hidden;
      touch-action:manipulation; user-select:none;
    }
    .nav-btn::after{
      content:''; position:absolute; inset:0; transform:scale(0);
      background:rgba(255,255,255,.35); border-radius:inherit;
      transition:transform .35s ease, opacity .35s ease; opacity:0;
    }
    .nav-btn:active::after{ transform:scale(1); opacity:1; }
    .nav-btn:focus-visible{ outline:3px solid rgba(13,110,253,.35); outline-offset:2px; }

    .cards-viewport{ position:relative; overflow:hidden; }
    .cards{ transition: opacity .2s ease; }
    .cards .row{ row-gap: var(--cards-gap); }
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
      transition: box-shadow .22s ease;
      isolation:isolate;
    }
    .card:hover{ box-shadow:var(--shadow-hover); }

    .card::before{
      content:''; position:absolute; inset:0; border-radius:inherit; padding:1px;
      background:var(--grad);
      -webkit-mask-composite: xor; mask-composite: exclude; opacity:.35; pointer-events:none;
    }
    .card-fig{ position:relative; overflow:hidden; }
    .card-img-top{
      width:100%; aspect-ratio: 3/4; object-fit:cover; background:#f5f7fa;
      transform:scale(1.05); opacity:0; transition: transform .35s ease, opacity .35s ease;
    }
    .card.loaded .card-img-top{ opacity:1; }
    .card:hover .card-img-top{ transform:scale(1.00); }

    .card-actions{
      position:absolute; top:.5rem; right:.5rem; display:flex; gap:.4rem; z-index:2;
      opacity:0; transition: opacity .2s ease;
    }
    .card:hover .card-actions{ opacity:1; }
    .btn-ghost{
      width:36px; height:36px; border-radius:999px; border:none;
      display:inline-flex; align-items:center; justify-content:center;
      background:rgba(255,255,255,.85); box-shadow:0 4px 12px rgba(0,0,0,.12);
    }
    .btn-ghost:hover{ background:#fff; }

    .badge-top{
      position:absolute; left:.75rem; top:.75rem; z-index:2;
      background: linear-gradient(135deg,#ff7d7d,#ffb199); color:#fff; border-radius:999px;
      padding:.25rem .6rem; font-weight:600; font-size:.72rem; box-shadow:0 4px 10px rgba(0,0,0,.15);
      opacity:0; transition: opacity .2s ease;
    }
    .card:hover .badge-top{ opacity:1; }

    /* espaço extra para o label 'Por:' no canto inferior esquerdo */
    .card-body{ position:relative; padding:.95rem .9rem 2.6rem .9rem; }
    /* garantir espaçamento entre título e publisher */
    .card-title.mb-1 { margin-bottom: .5rem; }
    .card-publisher{
      position:absolute;
      left:.9rem;
      bottom:.5rem;
      margin:0;
      font-size:0.78rem;
      color:#6c757d;
      line-height:1.1;
      pointer-events:none;
    }
    .card-title{ font-weight:700; line-height:1.25; }
    .card-title a{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; text-overflow:ellipsis; }
    .card-price{ font-weight:800; letter-spacing:.2px; color:#111; }

    /* Botão de favorito (toggle) */
    .btn-ghost.btn-fav { background: rgba(255,255,255,.9); }
    .btn-ghost.btn-fav i { font-size: 1rem; vertical-align: -1px; }
    .btn-ghost.btn-fav.active { background: #ffe6f0; }
    .btn-ghost.btn-fav.active i { color: #e83e8c; }

    @media (max-width:575.98px){
      .cards-viewport{ margin-inline:-.25rem; }
      .section-actions{ width:100%; justify-content:flex-end; }
    }
    @media (prefers-reduced-motion: reduce){
      .slide-anim, .cards .card, .card-img-top, .card{ transition:none !important; animation:none !important; }
    }

    /* Empty state */
    .empty{
      border: 2px dashed #cfe3ff; border-radius: 16px; padding: 28px;
      background: #f7fbff; color:#0b5ed7;
    }
  </style>
</head>
<body class="home-body text-center">

<?php
// Navbar (ajuste o nome do arquivo se o seu for nav.php)
$navOk = false;
$try1 = __DIR__ . '/../home/navbar.php';
$try2 = __DIR__ . '/../home/nav.php';
if (is_file($try1)) { include $try1; $navOk=true; }
elseif (is_file($try2)) { include $try2; $navOk=true; }
// Se não existir, segue sem navbar.
?>

<div class="container py-4">

  <!-- Cabeçalho + Botão Home -->
  <div class="d-flex align-items-center justify-content-between section-head mb-3">
    <h2 class="mb-0">Minha biblioteca</h2>
    <div class="d-flex align-items-center gap-2">
      <a href="/home/main.php" class="btn btn-outline-primary">
        <i class="bi bi-house-door"></i> Home
      </a>
    </div>
  </div>

  <!-- ===== Seção: Favoritos ===== -->
  <div class="d-flex align-items-center justify-content-between section-head mb-3">
    <h2 class="mb-0">Favoritos</h2>
    <?php if ($totFav > 0): 
      $prevFav = (($offFav - $pageSize) % $totFav + $totFav) % $totFav;
      $nextFav = ($offFav + $pageSize) % $totFav;
    ?>
      <div class="section-actions">
        <a class="btn btn-outline-primary nav-btn" href="?offset_favoritos=<?= $prevFav ?>" title="Anterior" aria-label="Anterior">‹</a>
        <a class="btn btn-primary nav-btn" href="?offset_favoritos=<?= $nextFav ?>" title="Próximo" aria-label="Próximo">›</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($totFav === 0): ?>
    <div class="empty text-start mb-4">
      <p class="mb-0">Você ainda não favoritou nenhum e-book. Na Home, clique no <strong>coração</strong> para adicioná-lo aqui.</p>
    </div>
  <?php else: ?>
    <section
      data-genre="favoritos"
      data-items='<?= formatItemsForDataAttr($favoritos) ?>'
      data-page-size="<?= $pageSize ?>"
      data-offset="<?= $offFav ?>"
      aria-label="Seção Favoritos"
    >
      <div class="cards-viewport">
        <div class="cards ready">
          <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 g-3">
            <?php foreach ($viewFav as $i => $it):
              $id     = (int)$it['id'];
              $img    = htmlspecialchars($it['img'] ?? '');
              $titulo = htmlspecialchars($it['titulo'] ?? '');
              // Preferir o nome do usuário que criou o livro; caso não exista, usar o campo publicado_por (texto livre)
              $publicado_por_raw = '';
              if (!empty($it['criado_por_username'])) {
                $publicado_por_raw = (string)$it['criado_por_username'];
              } elseif (!empty($it['publicado_por'])) {
                $publicado_por_raw = (string)$it['publicado_por'];
              }
              $publicado_por = htmlspecialchars($publicado_por_raw, ENT_QUOTES, 'UTF-8');
              $href   = "/public/livro/ver.php?id=".$id;
              $badge  = ($i % 3 === 0) ? "<span class='badge-top'>Top</span>" : "";
              $isFav  = in_array($id, $favoritadosIds, true);
            ?>
            <div class="col" style="--i:<?= $i ?>">
              <div class="card h-100" data-tilt="1">
                <?= $badge ?>
                <div class="card-actions">
                  <?php if ($isFav): ?>
                    <form method="post" action="/favoritos/remover.php" class="d-inline">
                      <input type="hidden" name="livro_id" value="<?= $id ?>">
                      <button class="btn-ghost btn-fav active" type="submit" title="Remover dos favoritos" aria-label="Remover dos favoritos">
                        <i class="bi bi-heart-fill"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="/favoritos/adicionar.php" class="d-inline">
                      <input type="hidden" name="livro_id" value="<?= $id ?>">
                      <button class="btn-ghost btn-fav" type="submit" title="Adicionar aos favoritos" aria-label="Adicionar aos favoritos">
                        <i class="bi bi-heart"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  <a class="btn-ghost" href="<?= $href ?>" aria-label="Ver detalhes" title="Ver detalhes">&#x1F50D;</a>
                </div>
                <a class="card-fig" href="<?= $href ?>" title="Ler <?= $titulo ?>">
                  <img src="<?= $img ?>" alt="Capa do livro" class="card-img-top" loading="lazy">
                </a>
                <div class="card-body">
                  <h6 class="card-title mb-1">
                    <a href="<?= $href ?>" class="link-underline link-underline-opacity-0" title="<?= $titulo ?>"><?= $titulo ?></a>
                  </h6>
                  <?php if ($publicado_por_raw !== ''): ?>
                    <p class="card-publisher">Por: <?= $publicado_por ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <hr class="my-5">

  <!-- ===== Seção: Meus e-books (cadastrados por mim) ===== -->
  <div class="d-flex align-items-center justify-content-between section-head mb-3">
    <h2 class="mb-0">Meus e-books</h2>
    <?php if ($totMine > 0): 
      $prevMine = (($offMine - $pageSize) % $totMine + $totMine) % $totMine;
      $nextMine = ($offMine + $pageSize) % $totMine;
    ?>
      <div class="section-actions">
        <a class="btn btn-outline-primary nav-btn" href="?offset_meus=<?= $prevMine ?>" title="Anterior" aria-label="Anterior">‹</a>
        <a class="btn btn-primary nav-btn" href="?offset_meus=<?= $nextMine ?>" title="Próximo" aria-label="Próximo">›</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($totMine === 0): ?>
    <div class="empty text-start">
      <p class="mb-0">Você ainda não cadastrou e-books. Use <strong>Cadastrar E-book</strong> no menu para enviar o seu PDF.</p>
    </div>
  <?php else: ?>
    <section
      data-genre="meus"
      data-items='<?= formatItemsForDataAttr($meusLivros) ?>'
      data-page-size="<?= $pageSize ?>"
      data-offset="<?= $offMine ?>"
      aria-label="Seção Meus e-books"
    >
      <div class="cards-viewport">
        <div class="cards ready">
          <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 g-3">
            <?php foreach ($viewMine as $i => $it):
              $id     = (int)$it['id'];
              $img    = htmlspecialchars($it['img'] ?? '');
              $titulo = htmlspecialchars($it['titulo'] ?? '');
              // Preferir mostrar o nome de quem publicou (criado_por_username) ou o texto publicado_por
              $publicado_por_raw = '';
              if (!empty($it['criado_por_username'])) {
                $publicado_por_raw = (string)$it['criado_por_username'];
              } elseif (!empty($it['publicado_por'])) {
                $publicado_por_raw = (string)$it['publicado_por'];
              }
              $publicado_por = htmlspecialchars($publicado_por_raw, ENT_QUOTES, 'UTF-8');
              $href   = "/public/livro/ver.php?id=".$id;
              $badge  = ($i % 3 === 0) ? "<span class='badge-top'>Top</span>" : "";
              $isFav  = in_array($id, $favoritadosIds, true);
            ?>
            <div class="col" style="--i:<?= $i ?>">
              <div class="card h-100" data-tilt="1">
                <?= $badge ?>
                <div class="card-actions">
                  <?php if ($isFav): ?>
                    <form method="post" action="/favoritos/remover.php" class="d-inline">
                      <input type="hidden" name="livro_id" value="<?= $id ?>">
                      <button class="btn-ghost btn-fav active" type="submit" title="Remover dos favoritos" aria-label="Remover dos favoritos">
                        <i class="bi bi-heart-fill"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="/favoritos/adicionar.php" class="d-inline">
                      <input type="hidden" name="livro_id" value="<?= $id ?>">
                      <button class="btn-ghost btn-fav" type="submit" title="Adicionar aos favoritos" aria-label="Adicionar aos favoritos">
                        <i class="bi bi-heart"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  <a class="btn-ghost" href="<?= $href ?>" aria-label="Ver detalhes" title="Ver detalhes">&#x1F50D;</a>
                </div>
                <a class="card-fig" href="<?= $href ?>" title="Ler <?= $titulo ?>">
                  <img src="<?= $img ?>" alt="Capa do livro" class="card-img-top" loading="lazy">
                </a>
                <div class="card-body">
                  <h6 class="card-title mb-1">
                    <a href="<?= $href ?>" class="link-underline link-underline-opacity-0" title="<?= $titulo ?>"><?= $titulo ?></a>
                  </h6>
                  <?php if ($publicado_por_raw !== ''): ?>
                    <p class="card-publisher">Por: <?= $publicado_por ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

</div>

<script>
// melhora percepção de carregamento das capas (mesmo truque da Home)
document.addEventListener('DOMContentLoaded', ()=>{
  document.querySelectorAll('.card .card-img-top').forEach(img=>{
    if (img.complete) img.closest('.card').classList.add('loaded');
    else img.addEventListener('load', ()=> img.closest('.card').classList.add('loaded'), {once:true});
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
 <footer><?php include __DIR__ . '/../footer.php'; ?></footer>
</body>
</html>
