<div class="container py-5 text-start">
  <?php
    $secoes = [
      'destaque' => 'Livros em Destaque',
      'romance'  => 'Romance',
      'infantil' => 'Infantil',
      'terror'   => 'Terror',
      'misterio' => 'Mistério',
      'documentario' => 'Documentário',
      'ficcao'   => 'Ficção Científica',
      'fantasia' => 'Fantasia',
      'poesia'   => 'Poesia',
      'biografia' => 'Biografia',
      'didatico'  => 'Didático',
    ];
    $pageSize = 6;

    foreach ($secoes as $slug=>$titulo):
      // itens crus vindos do banco
      $itemsRaw = $catalogo[$slug] ?? [];
      $total    = count($itemsRaw);

      // se não tiver nada, nem renderiza a seção
      if ($total === 0) continue;

      // normaliza para garantir id/titulo/preco/img/descricao no JSON do data-items
      $items = array_map(function($it){
        return [
          'id'        => isset($it['id']) ? (int)$it['id'] : 0,
          'titulo'    => (string)($it['titulo'] ?? ''),
          'user'      => (float)($it['user_id'] ?? 0),
          'img'       => (string)($it['img'] ?? ''),         // capa_path
          'descricao' => (string)($it['descricao'] ?? ''),   // *** ADICIONADO ***
          'publicado_por' => (string)($it['publicado_por'] ?? ''),
          'criado_por_username' => (string)($it['criado_por_username'] ?? ''),
        ];
      }, $itemsRaw);

      $offset = getOffset($slug, $total, min($pageSize, max(1,$total)));

      // JSON seguro para atributo HTML
      $itemsJson = htmlspecialchars(
        json_encode($items, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ENT_QUOTES,
        'UTF-8'
      );
  ?>
  <section id="sec-<?= $slug ?>"
           class="py-4"
           data-genre="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
           data-items='<?= $itemsJson ?>'
           data-page-size="<?= (int)$pageSize ?>"
           data-offset="<?= (int)$offset ?>">
    <div class="d-flex section-head justify-content-between align-items-center mb-3">
      <h2 class="mb-0"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h2>
      <div class="section-actions">
        <button type="button" class="btn btn-outline-primary nav-btn" data-dir="-1" aria-label="Voltar" title="Voltar">&#x2190;</button>
        <button type="button" class="btn btn-outline-primary nav-btn" data-dir="1" aria-label="Avançar" title="Avançar">&#x2192;</button>
      </div>
    </div>

    <!-- VIEWPORT + grade inicial -->
    <div class="cards-viewport">
      <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-4 justify-content-start cards">
        <?= renderCards($items, $offset, min($pageSize, max(1,$total))) ?>
      </div>
    </div>
  </section>
  <?php endforeach; ?>
</div>
