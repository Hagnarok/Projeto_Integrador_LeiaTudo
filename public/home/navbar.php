<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
?>

<!-- navbar fragment: não deve conter <body> ou links de <head> quando incluída em páginas que já têm <head> -->

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
                    <button id="tema" class="btn btn-theme-toggle" title="Tema Escuro" aria-pressed="false">
                        <i class="bi bi-moon" aria-hidden="true"></i>
                    </button>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fs-5 px-3" href="#" id="menuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-list"></i> 
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
                            <li><a class="scrool-bar dropdown-text px-3">Gêneros:</a></li>
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

    <!-- Barra de pesquisa abaixo da navbar (canto direito) -->
    <div class="nav-search container-fluid px-3 py-2">
        <div class="d-flex justify-content-end">
            <form class="d-flex position-relative" role="search" action="/home/main.php" method="get" autocomplete="off">
                <input id="siteSearch" name="q" class="form-control form-control-sm me-2" type="search" placeholder="Pesquisar livros..." aria-label="Pesquisar livros" value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES) ?>">
                <button class="btn btn-sm btn-outline-primary" type="submit" title="Pesquisar"><i class="bi bi-search"></i></button>

                <!-- sugestões dinâmicas (typeahead) -->
                <div id="searchSuggestions" class="search-suggestions d-none" role="listbox" aria-label="Sugestões de livros"></div>
            </form>
        </div>
    </div>

<script>
// Tema Escuro 
(() => {
  const temaBtn = document.getElementById('tema');
  if (!temaBtn) return;

  
  const moonIcon = temaBtn.querySelector('.bi') || temaBtn.querySelector('m') || temaBtn.querySelector('i');

  
  let moonOn = false;
  try { moonOn = localStorage.getItem('tema_dark') === '1'; } catch (e) { /* ignore */ }

  const applyTheme = (on) => {
    moonOn = !!on;
    document.body.classList.toggle('dark-mode', moonOn);
    if (moonIcon) {
      if (moonOn) {
        moonIcon.classList.remove('bi-moon');
        moonIcon.classList.add('bi-sun');
      } else {
        moonIcon.classList.remove('bi-sun');
        moonIcon.classList.add('bi-moon');
      }
    }
    try { localStorage.setItem('tema_dark', moonOn ? '1' : '0'); } catch(e){}
  };

  applyTheme(moonOn);
  temaBtn.addEventListener('click', () => applyTheme(!moonOn));
})();
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

// Typeahead: fetch sugestões enquanto digita
(function(){
    const input = document.getElementById('siteSearch');
    const box = document.getElementById('searchSuggestions');
    if (!input || !box) return;

    let timer = 0;
    const debounceDelay = 260;

    const escapeHtml = s=> String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    function hideBox(){ box.classList.add('d-none'); box.innerHTML=''; }
    function showBox(){ box.classList.remove('d-none'); }

    input.addEventListener('input', ()=>{
        clearTimeout(timer);
        const q = input.value.trim();
        if (!q) { hideBox(); return; }
        timer = setTimeout(()=> doSearch(q), debounceDelay);
    });

    input.addEventListener('keydown', (ev)=>{
        if (ev.key === 'Escape') { hideBox(); input.blur(); }
    });

    document.addEventListener('click', (ev)=>{
        if (!input.contains(ev.target) && !box.contains(ev.target)) hideBox();
    });

    function doSearch(q){
        fetch('/home/search.php?q='+encodeURIComponent(q), { credentials:'same-origin' })
            .then(r=>r.json())
            .then(json=>{
                const items = (json && Array.isArray(json.results)) ? json.results : [];
                if (!items.length) {
                    box.innerHTML = '<div class="suggestion-empty p-2 text-muted">Nenhum resultado</div>';
                    showBox();
                    return;
                }

                box.innerHTML = items.map(it=>{
                    const img = it.img ? '<img src="'+escapeHtml(it.img)+'" alt="" class="me-2 suggestion-img">' : '<div class="me-2 suggestion-img placeholder"></div>';
                    const title = '<div class="fw-semibold">'+escapeHtml(it.titulo)+'</div>';
                    const subtitle = (it.autor || it.publicado_por) ? '<div class="small text-muted">'+escapeHtml(it.autor || it.publicado_por)+'</div>' : '';
                    return '<a href="/public/livro/ver.php?id='+encodeURIComponent(it.id)+'" class="suggestion-item d-flex align-items-center p-2 text-decoration-none text-reset" role="option">'+img+'<div>'+title+subtitle+'</div></a>';
                }).join('');
                showBox();
            }).catch(()=>{
                box.innerHTML = '<div class="suggestion-empty p-2 text-muted">Erro na busca</div>';
                showBox();
            });
    }
})();
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
