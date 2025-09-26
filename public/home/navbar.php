<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/css/default.css?v=1" rel="stylesheet">
<body class="home-nav">
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="/css/imgs/logo.png" alt="Ícone" width="80" height="80">
                LeiaTudo
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto me-5 mb-2 mb-lg-0">
                    <li class="nav-item dropdown bordas">
                        <a class="nav-link dropdown-toggle fs-5" href="#" id="menuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Menu
                        </a>
                        <ul class="dropdown-menu bg-cinza" aria-labelledby="menuLink">
                            <li><a class="dropdown-item bg-cinza" href="#">Terror</a></li>
                            <li><a class="dropdown-item bg-cinza" href="#">Mistério</a></li>
                            <li><a class="dropdown-item bg-cinza" href="#">Infantil</a></li>
                            <li><a class="dropdown-item bg-cinza" href="#">Documentário</a></li>
                            <li><a class="dropdown-item bg-cinza" href="#">Romance</a></li>

                            <li class="nav-item">
                                <a class="dropdown-item bg-cinza" href="/login/main.php" style="color: red;">
                                Sair
                                <i class="bi bi-box-arrow-right"></i> 
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</body>