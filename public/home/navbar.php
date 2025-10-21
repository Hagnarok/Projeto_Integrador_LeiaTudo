<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
?>

<body class="home-nav">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/default.css?v=2">

    <nav class="navbar navbar-expand-lg navbar-custom shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex flex-column align-items-start">
                <div class="d-flex align-items-center gap-2">
                    <img src="/css/imgs/logo.png" alt="Ícone" width="70" height="70" class="rounded-circle">
                    <div>
                        <span class="fs-4 fw-bold ms-1">LeiaTudo</span>
                        <small class="text-light ms-1">👋 Bem-vindo, <?= htmlspecialchars($user['username']) ?></small>
                    </div>
                </div>
                
                
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
                            <li><a class="dropdown-text px-3">Funções:</a></li>
                            <li>
                                <a class="dropdown-item" href="/cadastro/main.php">
                                    <i class="bi bi-plus-circle me-1"></i> Cadastrar E-book
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/biblioteca_user/main.php">
                                    <i class="bi bi-book me-1"></i> Minha biblioteca
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-text px-3">Gêneros:</a></li>
                            <li><a class="dropdown-item" href="#sec-terror">Terror</a></li>
                            <li><a class="dropdown-item" href="#sec-misterio">Mistério</a></li>
                            <li><a class="dropdown-item" href="#sec-infantil">Infantil</a></li>
                            <li><a class="dropdown-item" href="#sec-documentario">Documentário</a></li>
                            <li><a class="dropdown-item" href="#sec-romance">Romance</a></li>
                            <li><a class="dropdown-item" href="#sec-ficcao">Ficção Ciêntifica</a></li>
                            <li><a class="dropdown-item" href="#sec-fantasia">Fantasia</a></li>
                            <li><a class="dropdown-item" href="#sec-didatico">Didático</a></li>
                            <li><a class="dropdown-item" href="#sec-biografia">Biografia</a></li>
                            <li><a class="dropdown-item" href="#sec-poesia">Poesia</a></li>
                            <li><a class="dropdown-item" href="#sec-suspense">Suspense</a></li>
         
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    const genreLinks = document.querySelectorAll('.dropdown-menu a[href^="#sec-"]');

    genreLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);

            if (!targetSection) {
                event.preventDefault();
                mostrarAviso(`Nenhum livro encontrado na seção "${this.textContent.trim()}".`);
                return;
            }

            // Remove destaque anterior
            document.querySelectorAll('.section-highlight').forEach(sec => {
                sec.classList.remove('section-highlight', 'section-fade');
            });

            // Adiciona destaque suave
            targetSection.classList.add('section-highlight');

            // Scroll centralizado
            const sectionTop = targetSection.getBoundingClientRect().top + window.scrollY;
            const scrollTo = sectionTop - (window.innerHeight / 2) + (targetSection.offsetHeight / 2);
            window.scrollTo({ top: scrollTo, behavior: 'smooth' });

            // Remove com fade suave
            setTimeout(() => {
                targetSection.classList.add('section-fade');
                setTimeout(() => targetSection.classList.remove('section-highlight', 'section-fade'), 1200);
            }, 2000);
        });
    });

    // Cria aviso com botão de fechar
    function mostrarAviso(mensagem) {
        if (document.querySelector('.alert-floating')) return;

        const aviso = document.createElement('div');
        aviso.className = 'alert alert-warning alert-floating shadow-sm d-flex align-items-center justify-content-between';
        aviso.innerHTML = `
            <div><i class="bi bi-exclamation-triangle-fill me-2"></i> ${mensagem}</div>
            <button type="button" class="btn-close ms-3" aria-label="Fechar"></button>
        `;

        document.body.appendChild(aviso);

        aviso.querySelector('.btn-close').addEventListener('click', () => aviso.remove());
        setTimeout(() => aviso.remove(), 3000);
    }
});
</script>

<style>
/* ---------- AVISO FLOATING ---------- */
.alert-floating {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 2000;
    padding: 0.9rem 1.2rem;
    border-radius: 0.75rem;
    font-weight: 500;
    font-size: 0.95rem;
    background-color: #fff8e1;
    border: 1px solid #ffe69c;
    color: #856404;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    animation: fadeIn 0.3s ease-out;
    max-width: 320px;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ---------- DESTAQUE ---------- */
.section-highlight {
    position: relative;
    transition: background-color 0.5s ease;
}

.section-highlight::before {
    content: "";
    position: absolute;
    top: 0;
    left: 50%;
    width: 100vw; /* cobre até as bordas */
    height: 100%;
    transform: translateX(-50%);
    background-color: rgba(13, 110, 253, 0.07);
    box-shadow: 0 0 0 3px rgba(13,110,253,0.18);
    border-radius: 10px;
    z-index: -1;
    animation: pulseOutline 1.5s ease-out;
}

.section-fade::before {
    animation: fadeOutHighlight 1s ease forwards;
}

@keyframes pulseOutline {
    0%   { box-shadow: 0 0 0 0 rgba(13,110,253,0.25); }
    70%  { box-shadow: 0 0 0 10px rgba(13,110,253,0); }
    100% { box-shadow: 0 0 0 0 rgba(13,110,253,0); }
}

@keyframes fadeOutHighlight {
    from { opacity: 1; }
    to { opacity: 0; }
}
</style>


</body>
