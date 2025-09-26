<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LeiaTudo - Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/default.css?v=2">


</head>
<body class="home-body text-center">

    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <h2 class="mb-4 text-start">Livros em Destaque</h2>

        <div class="row g-4 justify-content-center">
            <!-- Exemplo de item de livro -->
            <?php for ($i = 0; $i < 18; $i++): ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="card h-100">
                        <img src="/css/imgs/imagem1.png" class="card-img-top img-fluid" alt="Capa do livro">
                        <div class="card-body p-2">
                            <h6 class="card-title">Pequeno Príncipe</h6>
                            <p class="card-price">R$ 40,00</p>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
