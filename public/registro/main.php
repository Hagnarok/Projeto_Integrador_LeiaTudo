<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registro - LeiaTudo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/default.css?v=2">

</head>
<body class="home-registro">
    <div class="container text-center mt-4">
        <a class="navbar-brand" href="#">
            <img src="/css/imgs/logo.png" alt="Ícone" width="190" height="190">
        </a>
    </div>

    <div class="container mt-5">
        <h2 class="text-center">Cadastre-se</h2>

        <!-- Formulário de cadastro -->
        <form action="/home/main.php" method="GET">
            <div class="mb-3 d-flex flex-column align-items-start w-50 mx-auto">
                <label for="usuario" class="form-label fw-normal fs-6">Nome de usuário:</label>
                <input type="text" name="usuario" id="usuario" class="form-control" required>
            </div>

            <div class="mb-3 d-flex flex-column align-items-start w-50 mx-auto">
                <label for="cpf" class="form-label fw-normal fs-6">CPF:</label>
                <input type="text" name="cpf" id="cpf" class="form-control" required>
            </div>

            <div class="mb-3 d-flex flex-column align-items-start w-50 mx-auto">
                <label for="email" class="form-label fw-normal fs-6">Email:</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>

            <div class="mb-1 d-flex flex-column align-items-start w-50 mx-auto">
                <label for="senha" class="form-label fw-normal fs-6">Senha:</label>
                <input type="password" name="senha" id="senha" class="form-control" required>
            </div>

            <div class="mb-3 d-flex flex-column align-items-start w-50 mx-auto">
                <label for="confirmar_senha" class="form-label fw-normal fs-6">Confirme a senha:</label>
                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required>
            </div>

            <div class="d-flex justify-content-center gap-3">
                <button type="submit" class="btn btn-primary btn-lg w-25">Cadastrar</button>
            </div>  
        </form>

        <!-- Botão/link para redirecionar (sem enviar o form) -->
        <div class="d-flex justify-content-center mt-3">
            <a href="/login/main.php" class="btn btn-secondary btn-lg w-25">Já tenho conta</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
