<?php
$archivo_json = 'leyes.json';
$leyes = [];

if (file_exists($archivo_json)) {
    $json_data = file_get_contents($archivo_json);
    $leyes = json_decode($json_data, true);
}

// El filtrado y paginación son 100% cliente (JS).
// PHP solo sirve las leyes; el ?q del buscador se pasa a JS vía data attribute.
$q_busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$resultados = $leyes;   // siempre todas
$total_resultados = count($resultados);

$total_leyes   = count($leyes);
$vigentes      = count(array_filter($leyes, fn($l) => $l['estado'] === 'Vigente'));
$comunidades   = count(array_unique(array_column($leyes, 'comunidad')));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduLey — Portal Normativo Educativo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
</head>
<body>

<!-- ═══ HEADER ═══════════════════════════════════════ -->
<header class="main-header">
    <div class="logo-container">
        <a href="index.php" class="logo-link">
            <h1 class="logo">Edu<span>Ley</span></h1>
        </a>
    </div>
    <nav class="top-nav">
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="index.php">Todas las leyes</a></li>
            <li><a href="?q=Nacional">Nacionales</a></li>
        </ul>
    </nav>
    <div class="user-access">
        <button id="btn-mi-cuenta" onclick="abrirModal()">Iniciar Sesión</button>
    </div>
</header>

<!-- ═══ MODAL AUTH ════════════════════════════════════ -->
<div id="auth-modal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-modal" onclick="cerrarModal()">×</span>
        <h2 id="modal-title">Iniciar Sesión</h2>

        <div id="login-form">
            <input type="text" id="login-id" placeholder="Usuario o correo electrónico" class="input-form">
            <input type="password" id="login-pass" placeholder="Contraseña" class="input-form">
            <button type="button" onclick="login()" class="btn-primary-modal">Entrar</button>
            <p>¿No tienes cuenta? <a href="#" onclick="toggleAuthMode()">Regístrate aquí</a></p>
        </div>

        <div id="register-form" style="display:none;">
            <div style="display:flex; gap:10px;">
                <input type="text" id="reg-nombre" placeholder="Nombre" class="input-form" style="margin-bottom:0;">
                <input type="text" id="reg-apellidos" placeholder="Apellidos" class="input-form" style="margin-bottom:0;">
            </div>
            <br>
            <input type="text" id="reg-user" placeholder="Nombre de usuario" class="input-form">
            <input type="email" id="reg-email" placeholder="Correo electrónico" class="input-form">
            <input type="password" id="reg-pass" placeholder="Contraseña" class="input-form">
            <button onclick="registrar()" class="btn-primary-modal">Crear Cuenta</button>
            <p>¿Ya tienes cuenta? <a href="#" onclick="toggleAuthMode()">Inicia sesión</a></p>
        </div>

        <div class="modal-admin-link">
            <p>¿Eres personal autorizado?</p>
            <a href="admin.php">🔐 Acceso al Panel Admin</a>
        </div>
    </div>
</div>

<!-- ═══ HERO / SEARCH BANNER ═════════════════════════ -->
<section class="search-banner">
    <div class="container">
        <h2>Portal de Normativa Educativa Española</h2>
        <p class="tagline">Acceso rápido, actualizado y gratuito a leyes nacionales y autonómicas</p>
        <form action="index.php" method="GET" class="search-form">
            <input type="text" name="q"
                   placeholder="Busca por nombre, comunidad, estado… Ej: LOMLOE, Asturias, Vigente"
                   value="<?php echo htmlspecialchars($q_busqueda); ?>">
            <button type="submit">Buscar</button>
        </form>
        <p class="search-hint">💡 Prueba: "Galicia", "Derogada", "FP", "Bachillerato"</p>

        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-num"><?php echo $total_leyes; ?></span>
                <span class="stat-label">Leyes registradas</span>
            </div>
            <div class="stat-item">
                <span class="stat-num"><?php echo $vigentes; ?></span>
                <span class="stat-label">Vigentes</span>
            </div>
            <div class="stat-item">
                <span class="stat-num"><?php echo $comunidades; ?></span>
                <span class="stat-label">Comunidades</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══ MAIN LAYOUT ═══════════════════════════════════ -->
<main class="main-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-inner">
            <div class="sidebar-header">
                <h2>Explorar normativa</h2>
                <p>Filtra por categoría o región</p>
            </div>
            <nav class="side-nav">

                <div class="side-nav-section">
                    <span class="section-label">Filtros</span>
                    <ul>
                        <li>
                            <a href="#" class="filtro-link activo-filtro" data-filtro="">
                                <span class="icon">🌐</span> Todas las leyes
                            </a>
                        </li>
                        <li>
                            <a href="#" class="filtro-link" data-filtro="vigente">
                                <span class="icon">✅</span> Solo Vigentes
                            </a>
                        </li>
                        <li>
                            <a href="#" class="filtro-link" data-filtro="derogada">
                                <span class="icon">❌</span> Derogadas
                            </a>
                        </li>
                        <li>
                            <a href="#" class="filtro-link" data-filtro="nacional">
                                <span class="icon">🏛️</span> Nacionales
                            </a>
                        </li>
                        <li>
                            <a href="#" onclick="mostrarSoloFavoritos(); return false;" id="btn-ver-favs">
                                <span class="icon">⭐</span> Mis Favoritos
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="side-nav-section">
                    <span class="section-label">Comunidades Autónomas</span>
                    <ul class="comunidades-list">
                        <?php
                        $ccaa = ['Andalucía','Aragón','Asturias','Canarias','Cantabria',
                                 'Castilla y León','Cataluña','Comunidad Valenciana',
                                 'Galicia','Madrid','País Vasco'];
                        foreach ($ccaa as $c):
                        ?>
                        <li>
                            <a href="#" class="filtro-link" data-filtro="<?php echo htmlspecialchars(strtolower($c)); ?>">
                                <span class="icon">📍</span> <?php echo $c; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </nav>
        </div>
    </aside>

    <!-- CONTENT -->
    <section class="content-area">

        <div class="results-meta">
            <span class="results-count"></span>
        </div>

        <?php if (count($resultados) > 0): ?>
            <!-- Todas las tarjetas se renderizan; JS controla visibilidad, filtrado y paginación -->
            <div class="laws-grid" id="contenedor-leyes"
                 data-busqueda="<?php echo htmlspecialchars($q_busqueda); ?>">
                <?php foreach ($resultados as $ley): ?>
                    <?php
                    $estadoClass = strpos(strtolower($ley['estado']), 'derogada') !== false ? 'derogada' : 'vigente';
                    ?>
                    <article class="law-card <?php echo $estadoClass; ?>"
                             data-id="<?php echo $ley['id']; ?>"
                             data-titulo="<?php echo htmlspecialchars(strtolower($ley['titulo'])); ?>"
                             data-estado="<?php echo htmlspecialchars(strtolower($ley['estado'])); ?>"
                             data-comunidad="<?php echo htmlspecialchars(strtolower($ley['comunidad'])); ?>"
                             data-desc="<?php echo htmlspecialchars(strtolower($ley['desc'])); ?>">
                        <div class="law-card-inner">
                            <div class="law-header">
                                <span class="badge <?php echo $estadoClass; ?>"><?php echo $ley['estado']; ?></span>
                                <span class="badge-comunidad"><?php echo htmlspecialchars($ley['comunidad']); ?></span>
                                <span class="law-id">#<?php echo $ley['id']; ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($ley['titulo']); ?></h3>
                            <p class="law-desc"><?php echo htmlspecialchars($ley['desc']); ?></p>
                        </div>
                        <div class="law-footer">
                            <span class="law-date">Aprobación: <?php echo htmlspecialchars($ley['fecha']); ?></span>
                            <div class="actions">
    <?php if (!empty($ley['pdf_url'])): ?>
        <a href="pdfs/<?php echo $ley['pdf_url']; ?>" 
           download="Ley_<?php echo $ley['id']; ?>.pdf" 
           class="btn-pdf">Descargar PDF</a>
    <?php else: ?>
        <button class="btn-pdf" style="background:var(--text-muted); cursor:not-allowed;" 
                onclick="alert('PDF no disponible todavía')">Sin PDF</button>
    <?php endif; ?>
    
    <button class="btn-fav" 
            id="fav-btn-<?php echo $ley['id']; ?>" 
            onclick="toggleFavorito(<?php echo $ley['id']; ?>)">♡ Favorito</button>
</div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>Sin resultados para "<?php echo htmlspecialchars($q_busqueda); ?>"</h3>
                <p>Intenta buscar por el nombre de la ley, comunidad autónoma o estado (Vigente / Derogada).</p>
            </div>
        <?php endif; ?>

        <!-- PAGINACIÓN — gestionada por JS -->
        <div class="pagination-web" id="paginacion-js" style="display:none;"></div>

    </section>
</main>

<!-- ═══ FOOTER ════════════════════════════════════════ -->
<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-section">
            <span class="footer-logo">Edu<span>Ley</span></span>
            <p>Tu portal de referencia para la normativa educativa española. Acceso rápido, actualizado y gratuito a todas las leyes vigentes y derogadas.</p>
        </div>
        <div class="footer-section">
            <h3>Navegación</h3>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="?q=Nacional">Leyes Nacionales</a></li>
                <li><a href="#" onclick="mostrarSoloFavoritos()">Mis Favoritos</a></li>
                <li><a href="admin.php">Panel Admin</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Referencias Oficiales</h3>
            <ul>
                <li><a href="https://www.boe.es" target="_blank" rel="noopener">BOE — B.O. del Estado</a></li>
                <li><a href="https://www.educacionfpydeportes.gob.es" target="_blank" rel="noopener">Ministerio de Educación</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Contacto</h3>
            <p>📍 Asturias, España</p>
            <br>
            <p>📧 soporte@eduley.es</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> <span>EduLey</span>. Todos los derechos reservados.</p>
        <p>Proyecto Final de Curso — Portal Normativo Educativo - Enol Álvarez Velasco</p>
    </div>
</footer>

<script src="script.js"></script>
</body>
</html>
