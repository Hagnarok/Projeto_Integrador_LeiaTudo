<?php
declare(strict_types=1);
session_start();

// Protege a página
if (!isset($_SESSION['user'])) {
  header('Location: /login/main.php');
  exit;
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>LeiaTudo - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/default.css?v=2">
  <style>
    .genre-btn{min-width:2.25rem}

    /* Viewport fixa a altura durante a animação (evita “pulo”) */
    .cards-viewport {
      position: relative;
      overflow: hidden;
    }

    /* Camadas animadas sobrepostas */
    .cards-layer {
      position: absolute;
      inset: 0;
    }
    .slide-in-right   { transform: translateX(60px);  opacity: 0; }
    .slide-in-left    { transform: translateX(-60px); opacity: 0; }
    .slide-out-left   { transform: translateX(-60px); opacity: 0; }
    .slide-out-right  { transform: translateX(60px);  opacity: 0; }

    .slide-anim {
      transition: transform .45s cubic-bezier(.22,.61,.36,1), opacity .45s ease;
    }

    /* Stagger nos cards (entra em “cascata”) */
    .cards .card {
      transform: translateY(8px);
      opacity: 0;
      transition: transform .35s ease, opacity .35s ease;
      transition-delay: calc(var(--i, 0) * 40ms);
    }
    .cards.ready .card {
      transform: translateY(0);
      opacity: 1;
    }

    /* Hover sutil */
    .card:hover {
      transform: translateY(-2px);
      transition: transform .2s ease;
    }

    /* Acessibilidade: reduz movimento se o usuário preferir */
    @media (prefers-reduced-motion: reduce) {
      .slide-anim,
      .cards .card {
        transition: none !important;
      }
    }
  </style>
</head>
<body class="home-body text-center">

<?php include 'navbar.php'; ?>

<?php
// ===== Substitua pelo SELECT do seu BD, agrupando por gênero =====
$catalogo = [
  'destaque' => [
    ['titulo'=>'Pequeno Príncipe','preco'=>40.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'1984','preco'=>39.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Dom Casmurro','preco'=>29.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Capitães da Areia','preco'=>34.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Memórias Póstumas','preco'=>32.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Grande Sertão','preco'=>49.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'O Alquimista','preco'=>44.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'A Revolução dos Bichos','preco'=>28.00,'img'=>'/css/imgs/imagem1.png'],
  ],
  'infantil' => [
    ['titulo'=>'Pequeno Príncipe','preco'=>40.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'1984','preco'=>39.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Dom Casmurro','preco'=>29.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Capitães da Areia','preco'=>34.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Memórias Póstumas','preco'=>32.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Grande Sertão','preco'=>49.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'O Alquimista','preco'=>44.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'A Revolução dos Bichos','preco'=>28.00,'img'=>'/css/imgs/imagem1.png'],
  ],
  'romance' => [
    ['titulo'=>'Pequeno Príncipe','preco'=>40.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'1984','preco'=>39.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Dom Casmurro','preco'=>29.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Capitães da Areia','preco'=>34.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Memórias Póstumas','preco'=>32.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Grande Sertão','preco'=>49.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'O Alquimista','preco'=>44.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'A Revolução dos Bichos','preco'=>28.00,'img'=>'/css/imgs/imagem1.png'],
  ],
  'documentario' => [
    ['titulo'=>'Pequeno Príncipe','preco'=>40.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'1984','preco'=>39.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Dom Casmurro','preco'=>29.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Capitães da Areia','preco'=>34.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Memórias Póstumas','preco'=>32.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Grande Sertão','preco'=>49.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'O Alquimista','preco'=>44.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'A Revolução dos Bichos','preco'=>28.00,'img'=>'/css/imgs/imagem1.png'],
  ],
  'terror' => [
    ['titulo'=>'Pequeno Príncipe','preco'=>40.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'1984','preco'=>39.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Dom Casmurro','preco'=>29.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Capitães da Areia','preco'=>34.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Memórias Póstumas','preco'=>32.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Grande Sertão','preco'=>49.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'O Alquimista','preco'=>44.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'A Revolução dos Bichos','preco'=>28.00,'img'=>'/css/imgs/imagem1.png'],
  ],
  'misterio' => [
    ['titulo'=>'Pequeno Príncipe','preco'=>40.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'1984','preco'=>39.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Dom Casmurro','preco'=>29.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Capitães da Areia','preco'=>34.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Memórias Póstumas','preco'=>32.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Grande Sertão','preco'=>49.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'O Alquimista','preco'=>44.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'A Revolução dos Bichos','preco'=>28.00,'img'=>'/css/imgs/imagem1.png'],
  ],
  'ficcao' => [
    ['titulo'=>'Pequeno Príncipe','preco'=>40.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'1984','preco'=>39.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Dom Casmurro','preco'=>29.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Capitães da Areia','preco'=>34.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Memórias Póstumas','preco'=>32.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Grande Sertão','preco'=>49.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'O Alquimista','preco'=>44.90,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'A Revolução dos Bichos','preco'=>28.00,'img'=>'/css/imgs/imagem1.png'],
  ],
];

// (funções renderCards e getOffset iguais às suas)
function renderCards($itens, $inicio, $qtd){
  $html=''; $n=count($itens); if(!$n) return $html;
  for($i=0;$i<$qtd;$i++){
    $idx = ($inicio + $i) % $n;
    $lv = $itens[$idx];
    $img = htmlspecialchars($lv['img']);
    $titulo = htmlspecialchars($lv['titulo']);
    $preco = number_format($lv['preco'], 2, ',', '.');
    $html.="
    <div class='col-6 col-sm-4 col-md-3 col-lg-2'>
      <div class='card h-100'>
        <img src='$img' class='card-img-top img-fluid' alt='Capa do livro'>
        <div class='card-body p-2'>
          <h6 class='card-title mb-1'>$titulo</h6>
          <p class='card-price mb-0'>R$ $preco</p>
        </div>
      </div>
    </div>";
  }
  return $html;
}
function getOffset($slug, $total, $pageSize){
  $k = "offset_$slug";
  $o = isset($_GET[$k]) ? intval($_GET[$k]) : 0;
  if($total>0){
    $o = $o % $total;
    if($o<0) $o += $total;
    $o = intdiv($o, $pageSize) * $pageSize % max($total,1);
  }
  return $o;
}
?>

<div class="container py-5">
  <?php
    $secoes = [
      'destaque' => 'Livros em Destaque',
      'romance'  => 'Romance',
      'infantil' => 'Infantil',
      'terror'   => 'Terror',
      'misterio' => 'Mistério',
      'documentario' => 'Documentário',
      'ficcao'   => 'Ficção Ciêntifica',
    ];
    $pageSize = 6;

    foreach ($secoes as $slug=>$titulo):
      $items = $catalogo[$slug] ?? [];
      $total = count($items);
      $offset = getOffset($slug, $total, min($pageSize, max(1,$total)));
      $itemsJson = htmlspecialchars(json_encode($items, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
  ?>
  <section id="sec-<?= $slug ?>" class="py-4" data-genre="<?= $slug ?>" data-items='<?= $itemsJson ?>' data-page-size="<?= $pageSize ?>" data-offset="<?= $offset ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0 text-start"><?= $titulo ?></h2>
      <div class="d-flex">
        <button type="button" class="btn btn-outline-primary btn-sm genre-btn me-2" data-dir="-1">&larr;</button>
        <button type="button" class="btn btn-outline-primary btn-sm genre-btn" data-dir="1">&rarr;</button>
      </div>
    </div>

    <!-- VIEWPORT + grade inicial -->
    <div class="cards-viewport">
      <div class="row g-4 justify-content-center cards">
        <?= renderCards($items, $offset, min($pageSize, max(1,$total))) ?>
      </div>
    </div>
  </section>
  <?php endforeach; ?>
</div>

<script>
(function(){
  // ===== util: centralizar seção na viewport (considera navbar fixa) =====
  function scrollSectionIntoCenter(section, opts={}){
    const nav = document.querySelector('.navbar');
    const navH = nav ? nav.offsetHeight : 0;

    const rect = section.getBoundingClientRect();
    const pageY = window.pageYOffset || document.documentElement.scrollTop || 0;
    const vh = window.innerHeight || document.documentElement.clientHeight;

    // Se a seção for maior que a viewport, mira o topo + margem
    const bigSection = rect.height > vh * 0.9;
    const margin = 12;

    let targetY;
    if (bigSection) {
      // leva o topo da seção logo abaixo da navbar
      targetY = pageY + rect.top - navH - margin;
    } else {
      // centra verticalmente
      const centerY = pageY + rect.top + (rect.height/2);
      targetY = centerY - (vh/2);
      // compensa navbar (que ocupa topo da viewport)
      targetY -= navH/2;
    }

    // limites
    targetY = Math.max(0, Math.round(targetY));

    window.scrollTo({
      top: targetY,
      behavior: (opts.behavior || 'smooth')
    });
  }

  // ===== liga centralização aos links #sec-* (menu/ancoras) =====
  function enableHashCentering(){
    // intercepta cliques em âncoras internas
    document.querySelectorAll('a[href^="#sec-"]').forEach(a=>{
      a.addEventListener('click', (ev)=>{
        const id = a.getAttribute('href');
        const el = document.querySelector(id);
        if (el) {
          ev.preventDefault();
          history.pushState(null, '', id); // mantém hash na URL
          scrollSectionIntoCenter(el);
        }
      });
    });

    // se carregar já com hash, centraliza
    function handleHash(){
      const id = decodeURIComponent(location.hash || '');
      if (!id) return;
      const el = document.querySelector(id);
      if (el) {
        // usa timeout p/ esperar layout/ imagens
        setTimeout(()=>scrollSectionIntoCenter(el, {behavior:'smooth'}), 0);
      }
    }
    window.addEventListener('hashchange', handleHash);
    handleHash();
  }

  // ===== integra com seu carrossel existente =====
  const sections = document.querySelectorAll('section[data-genre]');
  sections.forEach(section=>{
    const items = JSON.parse(section.dataset.items||'[]');
    const viewport = section.querySelector('.cards-viewport');
    let cardsEl = viewport.querySelector('.cards');
    const pageSize = Number(section.dataset.pageSize||6)||6;
    let offset = Number(section.dataset.offset||0)||0;

    function renderTo(container){
      const n = items.length;
      let html = '';
      for (let i=0;i<Math.min(pageSize,n);i++){
        const it = items[(offset+i)%n];
        const preco = (Number(it.preco)||0).toFixed(2).replace('.', ',');
        html += `
        <div class="col-6 col-sm-4 col-md-3 col-lg-2" style="--i:${i}">
          <div class="card h-100">
            <img src="${it.img}" class="card-img-top img-fluid" alt="Capa do livro">
            <div class="card-body p-2">
              <h6 class="card-title mb-1">${it.titulo}</h6>
              <p class="card-price mb-0">R$ ${preco}</p>
            </div>
          </div>
        </div>`;
      }
      container.innerHTML = html;
    }

    function firstRender(){
      requestAnimationFrame(()=>cardsEl.classList.add('ready'));
    }

    function step(dir){
      const n = items.length;
      if (!n) return;

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

        // >>> após mudar de página, centraliza a seção na tela
        scrollSectionIntoCenter(section);
      };
      newLayer.addEventListener('transitionend', onDone, {once:true});
    }

    section.querySelectorAll('button[data-dir]').forEach(btn=>{
      btn.addEventListener('click', ()=> step(Number(btn.dataset.dir)));
    });

    firstRender();
  });

  // habilita hash-centering para links de navegação
  enableHashCentering();
})();
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
