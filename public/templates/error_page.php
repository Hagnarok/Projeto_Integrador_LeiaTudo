<?php
// small reusable error page renderer
declare(strict_types=1);

if (!function_exists('render_error_page')) {
  function render_error_page(string $title, string $message, ?string $backUrl = null): void {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMsg = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
  $backUrlEsc = $backUrl ? htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') : '';
    ?>
    <!doctype html>
    <html lang="pt-br">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <title><?= $safeTitle ?></title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>body{background:#f8f9fa;padding:30px}</style>
    </head>
    <body>
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-8">
            <div class="card shadow-sm">
              <div class="card-body">
                <h3 class="card-title text-danger mb-3"><?= $safeTitle ?></h3>
                <div class="alert alert-warning" role="alert">
                  <strong>Atenção:</strong>
                  <div class="mt-2"><?= $safeMsg ?></div>
                </div>
                <div class="d-flex gap-2 mt-3">
                  <button class="btn btn-secondary" data-backurl="<?= $backUrlEsc ?>" onclick="(function(){var u=this.getAttribute('data-backurl'); if(u) window.location.href = u; else window.history.back(); }).call(this)">Voltar</button>
                  <a href="/home/main.php" class="btn btn-outline-primary">Ir para a página inicial</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
  }
}
