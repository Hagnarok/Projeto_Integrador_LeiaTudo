<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - LeiaTudo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/default.css" rel="stylesheet">

</head>
<body class="home-login">
    <div class="container text-center mt-4">
        <a class="navbar-brand" href="#">
            <img src="/css/imgs/logo.png" alt="Ícone" width="190" height="190">
        </a>
    </div>
    <div class="container mt-5">
        
        <h2 class="container text-center">Login</h2>
        <!-- redireciona para main.php -->
        <form action="/home/main.php" method="GET">
            <div class="mb-3 d-flex flex-column align-items-start w-50 mx-auto">
                <label for="usuario" class="form-label fw-normal fs-6">Usuário:</label>
                <input type="text" name="usuario" id="usuario" class="form-control" required>
            </div>

            <div class="mb-1 d-flex flex-column align-items-start w-50 mx-auto">
                <label for="senha" class="form-label fw-normal fs-6">Senha:</label>
                <input type="password" name="senha" id="senha" class="form-control" required>
            </div>

            <div class="mb-3 w-50 mx-auto d-flex justify-content-end">
                <a href="#" id="esqueci_senha" class="small text-decoration-none">Esqueceu a senha?</a>
            </div>

            <div class="d-flex justify-content-center gap-3">
            <button type="submit" class="btn btn-primary btn-lg w-25">Entrar</button>
            <button type="button" class="btn btn-secondary btn-lg w-25">Cadastrar-se</button>
            </div>
                
        </form>
    </div>
</body>
</html>
