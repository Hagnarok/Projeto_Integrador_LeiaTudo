<body class="home-nav">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/default.css?v=2">
    <nav class="navbar navbar-expand-lg navbar-custom shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <img src="/css/imgs/logo.png" alt="Ícone" width="50" height="50" class="rounded-circle">
                <span class="fs-4 fw-bold">LeiaTudo</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarMain">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fs-5 px-3" href="#" id="menuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-list"></i> Menu
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end animated-dropdown" aria-labelledby="menuLink">
                            <li><a class="dropdown-text px-3" >Funções:</a></li>
                            <li>
                                <a class="dropdown-item" href="/cadastro/main.php">
                                    <i class="bi bi-plus-circle me-1"></i> Cadastrar E-book
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-wallet2 me-1"></i> Carteira
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-book me-1"></i>Minha biblioteca
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-text px-3">Gêneros:</a></li>
                            <li><a class="dropdown-item" href="#sec-terror">Terror</a></li>
                            <li><a class="dropdown-item" href="#sec-misterio">Mistério</a></li>
                            <li><a class="dropdown-item" href="#sec-infantik">Infantil</a></li>
                            <li><a class="dropdown-item" href="#sec-documentario">Documentário</a></li>
                            <li><a class="dropdown-item" href="#sec-romance">Romance</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger fw-bold" href="/login/main.php">
                                    <i class="bi bi-box-arrow-right me-1"></i> Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</body>
