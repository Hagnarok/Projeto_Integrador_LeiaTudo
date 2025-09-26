<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>LeiaTudo - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/default.css?v=2">
  <style>.genre-btn{min-width:2.25rem}</style>
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
  'romance' => [
    ['titulo'=>'Orgulho e Preconceito','preco'=>45.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Anna Kariênina','preco'=>59.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'O Morro dos Ventos Uivantes','preco'=>39.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Jane Eyre','preco'=>42.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Madame Bovary','preco'=>38.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'A Casa dos Espíritos','preco'=>46.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Cem Anos de Solidão','preco'=>55.00,'img'=>'/css/imgs/imagem1.png'],
  ],
  'infantil' => [
    ['titulo'=>'O Menino Maluquinho','preco'=>25.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Reinações de Narizinho','preco'=>29.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Chapeuzinho Vermelho','preco'=>19.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Rapunzel','preco'=>19.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'O Gato de Botas','preco'=>19.00,'img'=>'/css/imgs/imagem1.png'],
  ],
  'terror' => [
    ['titulo'=>'O Iluminado','preco'=>49.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'It: A Coisa','preco'=>69.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Drácula','preco'=>39.00,'img'=>'/css/imgs/imagem1.png'],
    ['titulo'=>'Frankenstein','preco'=>35.00,'img'=>'/css/imgs/imagem1.png'],
  ],
];

// utilitário: imprime N cards (para fallback sem JS)
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

// Lê offset do fallback (se JS estiver off)
function getOffset($slug, $total, $pageSize){
  $k = "offset_$slug";
  $o = isset($_GET[$k]) ? intval($_GET[$k]) : 0;
  // normaliza em múltiplos do pageSize p/ não “pular” visualmente
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
    ];
    $pageSize = 6; // 6 por vez/linha

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
        <!-- Botões JS -->
        <button type="button" class="btn btn-outline-primary btn-sm genre-btn me-2" data-dir="-1" aria-label="Anterior">&larr;</button>
        <button type="button" class="btn btn-outline-primary btn-sm genre-btn" data-dir="1" aria-label="Próximo">&rarr;</button>
      </div>
    </div>

    <!-- Render inicial (fallback sem JS) -->
    <div class="row g-4 justify-content-center cards">
      <?= renderCards($items, $offset, min($pageSize, max(1,$total))) ?>
    </div>

    <!-- Fallback links sem JS -->
    <noscript>
      <?php
        $k="offset_$slug";
        $step = min($pageSize, max(1,$total));
        $ant = $total? ($offset - $step) % $total : 0; if($ant<0) $ant+=$total;
        $prox = $total? ($offset + $step) % $total : 0;
        $qs = $_GET; $qs[$k]=$ant; $urlAnt='?'.http_build_query($qs);
        $qs[$k]=$prox; $urlProx='?'.http_build_query($qs);
      ?>
      <div class="text-end mt-2">
        <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($urlAnt) ?>">&larr; Anterior</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($urlProx) ?>">Próximo &rarr;</a>
      </div>
    </noscript>
  </section>
  <?php endforeach; ?>
</div>

<!-- JS mínimo só para trocar os 6 cards, sem reload -->
<script>
(function(){
  const sections = document.querySelectorAll('section[data-genre]');
  sections.forEach(section=>{
    const slug = section.dataset.genre;
    const items = JSON.parse(section.dataset.items || '[]');
    const cardsEl = section.querySelector('.cards');
    const pageSizeCfg = Number(section.dataset.pageSize || 6) || 6;

    // pageSize real não pode exceder o total
    const pageSize = Math.min(pageSizeCfg, Math.max(1, items.length || pageSizeCfg));
    let offset = Number(section.dataset.offset || 0) || 0;

    function render(){
      if(!items.length){ cardsEl.innerHTML = ''; return; }
      let html = '';
      const n = items.length;
      for (let i=0;i<Math.min(pageSize,n);i++){
        const it = items[(offset+i)%n];
        const preco = (Number(it.preco)||0).toFixed(2).replace('.', ',');
        html += `
          <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <div class="card h-100">
              <img src="${it.img}" class="card-img-top img-fluid" alt="Capa do livro">
              <div class="card-body p-2">
                <h6 class="card-title mb-1">${it.titulo}</h6>
                <p class="card-price mb-0">R$ ${preco}</p>
              </div>
            </div>
          </div>`;
      }
      cardsEl.innerHTML = html;
    }

    function step(dir){
      if(!items.length) return;
      const n = items.length;
      const s = pageSize % n || pageSize; // avança exatamente 6 (ou n, se n<6)
      offset = (offset + dir * s) % n;
      if(offset<0) offset += n;
      render();
    }

    section.querySelectorAll('button[data-dir]').forEach(btn=>{
      btn.addEventListener('click', ()=> step(Number(btn.dataset.dir)));
    });

    // primeira renderização client-side (substitui o fallback)
    render();
  });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
