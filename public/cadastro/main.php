<?php
// public/cadastro/main.php
// (se tiver controle de sessão, inicie aqui)
// session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastrar E-book • LeiaTudo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/css/default.css?v=2">
</head>
<body class="home-cadastro bg-light">

<header class="container text-center mt-4">
  <a class="navbar-brand d-inline-flex align-items-center gap-2" href="/home/main.php">
    <img src="/css/imgs/logo.png" alt="LeiaTudo" width="120" height="120">
    <span class="fs-3 fw-semibold text-dark">LeiaTudo</span>
  </a>
</header>

<main class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-lg-5">
          <h1 class="h3 text-center mb-4">Cadastrar novo E-book</h1>

          <form action="/public/cadastro/processa_cadastro.php" method="POST" enctype="multipart/form-data" novalidate>
            <!-- Título -->
            <div class="mb-3">
              <label for="titulo" class="form-label">Título do livro</label>
              <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="200" placeholder="Ex.: Dom Casmurro">
            </div>

            <!-- Autor -->
            <div class="mb-3">
              <label for="autor" class="form-label">Autor</label>
              <input type="text" class="form-control" id="autor" name="autor" required maxlength="150" placeholder="Ex.: Machado de Assis">
            </div>

            <!-- Gênero -->
            <div class="mb-3">
              <label for="genero" class="form-label">Gênero</label>
              <select class="form-select" id="genero" name="genero" required>
                <option value="" selected disabled>Selecione…</option>
                <option>Romance</option>
                <option>Ficção Científica</option>
                <option>Fantasia</option>
                <option>Suspense/Thriller</option>
                <option>Mistério</option>
                <option>Didático</option>
                <option>Biografia</option>
                <option>Poesia</option>
                <option>Infantil</option>
                <option>Terror</option>
              </select>
            </div>

            <!-- Preço -->
            <div class="mb-3">
              <label for="publicado_por" class="form-label">Publicado por</label>
              <input type="text" class="form-control" id="publicado_por" name="publicado_por" required placeholder="Nome de quem publicou">
            </div>

            <!-- Descrição -->
            <div class="mb-3">
              <label for="descricao" class="form-label">Descrição (opcional)</label>
              <textarea class="form-control" id="descricao" name="descricao" rows="4" maxlength="2000" placeholder="Resumo curto do livro…"></textarea>
            </div>

            <!-- PDF -->
            <div class="mb-3">
              <label for="pdf" class="form-label">Arquivo do livro (PDF)</label>
              <input type="file" class="form-control" id="pdf" name="pdf" accept="application/pdf" required>
              <div class="form-text">Apenas PDF. Tamanho máximo recomendado: 25 MB.</div>
            </div>

            <!-- Capa -->
            <div class="mb-4">
              <label for="capa" class="form-label">Capa do livro (imagem)</label>
              <input type="file" class="form-control" id="capa" name="capa" accept="image/*" required>
              <div class="form-text">JPEG, PNG ou WebP. Tamanho máximo recomendado: 5 MB.</div>
            </div>

            <!-- Ações -->
            <div class="d-flex gap-3">
              <button type="submit" class="btn btn-primary btn-lg flex-grow-1">Cadastrar livro</button>
              <a href="/home/main.php" class="btn btn-outline-secondary btn-lg">Voltar</a>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
