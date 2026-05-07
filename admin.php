<?php
ini_set('session.cookie_lifetime', 0);
session_start();
$archivo_json = 'leyes.json';

// --- LOGIN ---
if (isset($_POST['login'])) {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);
    if ($user === 'admin' && $pass === 'admin123') {
        $_SESSION['admin_logged'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error_login = "Credenciales incorrectas. Verifica usuario y contraseña.";
    }
}

// --- LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    if (!isset($_GET['ajax'])) {
        header("Location: admin.php");
        exit;
    }
    exit;
}

// --- PROTECCIÓN ---
if (!isset($_SESSION['admin_logged'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Admin — EduLey</title>
        <link rel="stylesheet" href="style.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600&display=swap" rel="stylesheet">
    </head>
    <body class="login-page">
        <div class="login-box">
            <div class="login-logo">Edu<span>Ley</span></div>
            <span class="login-sub">Panel de Administración</span>
            <p class="login-title">Introduce tus credenciales</p>

            <?php if (isset($error_login)): ?>
                <div class="error-msg">⚠️ <?php echo $error_login; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="username" placeholder="Usuario" class="input-form" required autofocus>
                <input type="password" name="password" placeholder="Contraseña" class="input-form" required>
                <button type="submit" name="login" class="btn-primary-modal" style="margin-top:8px;">
                    Entrar al Panel
                </button>
            </form>

            <p class="login-back"><a href="index.php">← Volver a la web pública</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- CARGAR DATOS ---
$leyes_all = [];
if (file_exists($archivo_json)) {
    $leyes_all = json_decode(file_get_contents($archivo_json), true) ?: [];
}

// --- GUARDAR NUEVA LEY ---
if (isset($_POST['add_ley'])) {
    $nuevo_id = empty($leyes_all) ? 1 : max(array_column($leyes_all, 'id')) + 1;
    $leyes_all[] = [
        "id"       => $nuevo_id,
        "titulo"   => trim($_POST['titulo']),
        "fecha"    => trim($_POST['fecha']),
        "pdf_url"  => trim($_POST['pdf_url']),
        "estado"   => $_POST['estado'],
        "comunidad"=> trim($_POST['comunidad']),
        "desc"     => trim($_POST['desc'])
    ];
    file_put_contents($archivo_json, json_encode($leyes_all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: admin.php?msg=añadido");
    exit;
}

// --- BORRAR LEY ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_borrar  = $_GET['id'];
    $leyes_all  = array_filter($leyes_all, fn($l) => $l['id'] != $id_borrar);
    file_put_contents($archivo_json, json_encode(array_values($leyes_all), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: admin.php?msg=borrado");
    exit;
}

// --- EDITAR LEY ---
if (isset($_POST['edit_ley'])) {
    $id_editar = $_POST['id'];
    foreach ($leyes_all as &$ley) {
        if ($ley['id'] == $id_editar) {
            $ley['titulo']    = trim($_POST['titulo']);
            $ley['fecha']     = trim($_POST['fecha']);
            $ley['pdf_url']   = trim($_POST['pdf_url']);
            $ley['estado']    = $_POST['estado'];
            $ley['comunidad'] = trim($_POST['comunidad']);
            $ley['desc']      = trim($_POST['desc']);
            break;
        }
    }
    unset($ley);
    file_put_contents($archivo_json, json_encode(array_values($leyes_all), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: admin.php?msg=editado");
    exit;
}

// --- FILTRADO Y ORDENACIÓN ---
$leyes  = $leyes_all;
$filtro = $_GET['filtro'] ?? 'todos';
$orden  = $_GET['orden']  ?? 'reciente';

if ($filtro !== 'todos') {
    $leyes = array_filter($leyes, function($l) use ($filtro) {
        if ($filtro === 'nacionales') return strtolower($l['comunidad']) === 'nacional';
        if ($filtro === 'vigentes')   return $l['estado'] === 'Vigente';
        if ($filtro === 'derogadas')  return $l['estado'] === 'Derogada';
        return true;
    });
}

usort($leyes, function($a, $b) use ($orden) {
    if ($orden === 'id_asc')  return $a['id'] - $b['id'];
    if ($orden === 'id_desc') return $b['id'] - $a['id'];
    return $b['id'] - $a['id'];
});

// --- EDICIÓN ---
$ley_a_editar = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_buscar = $_GET['id'];
    foreach ($leyes_all as $ley) {
        if ($ley['id'] == $id_buscar) { $ley_a_editar = $ley; break; }
    }
}

// Stats
$total     = count($leyes_all);
$vigentes  = count(array_filter($leyes_all, fn($l) => $l['estado'] === 'Vigente'));
$derogadas = count(array_filter($leyes_all, fn($l) => $l['estado'] === 'Derogada'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin — EduLey</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600&display=swap" rel="stylesheet">
    <style>
        /* Admin-only extra styles */
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 20px 24px;
            box-shadow: var(--shadow-xs);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-card-icon {
            width: 44px; height: 44px;
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .stat-card-icon.blue  { background: #eff6ff; }
        .stat-card-icon.green { background: var(--success-light); }
        .stat-card-icon.red   { background: var(--danger-light); }
        .stat-card-value {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }
        .stat-card-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 4px;
        }
        @media(max-width:600px){
            .admin-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ═══ HEADER ═══════════════════════════════════════ -->
<header class="main-header">
    <div class="logo-container">
        <h1 class="logo">Edu<span>Ley</span> <small>Admin</small></h1>
    </div>
    <nav class="top-nav">
        <ul>
            <li><a href="index.php">Ver web pública</a></li>
        </ul>
    </nav>
    <div class="user-access">
        <a href="admin.php?logout=1" class="btn-danger">Cerrar sesión</a>
    </div>
</header>

<!-- TOAST -->
<?php if (isset($_GET['msg'])): ?>
<?php
$msgs = [
    'añadido' => ['✅ Ley añadida correctamente.', 'var(--success)'],
    'borrado'  => ['🗑️ Ley eliminada.', 'var(--danger)'],
    'editado'  => ['✏️ Ley actualizada correctamente.', '#854d0e'],
];
$m = $msgs[$_GET['msg']] ?? null;
if ($m):
?>
<div class="admin-toast" style="background:<?php echo $m[1]; ?>">
    <?php echo $m[0]; ?>
</div>
<?php endif; endif; ?>

<!-- ═══ ADMIN CONTAINER ═══════════════════════════════ -->
<div class="admin-container">

    <h1 class="admin-page-title">Panel de Administración</h1>
    <p class="admin-page-subtitle">Gestiona el contenido del portal normativo</p>

    <!-- Stats -->
    <div class="admin-stats">
        <div class="stat-card">
            <div class="stat-card-icon blue">📚</div>
            <div>
                <div class="stat-card-value"><?php echo $total; ?></div>
                <div class="stat-card-label">Leyes registradas</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon green">✅</div>
            <div>
                <div class="stat-card-value"><?php echo $vigentes; ?></div>
                <div class="stat-card-label">Vigentes</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon red">❌</div>
            <div>
                <div class="stat-card-value"><?php echo $derogadas; ?></div>
                <div class="stat-card-label">Derogadas</div>
            </div>
        </div>
    </div>

    <!-- ── FORMULARIO AÑADIR / EDITAR ── -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="card-icon"><?php echo $ley_a_editar ? '✏️' : '➕'; ?></div>
            <h2><?php echo $ley_a_editar ? 'Editar Ley #' . $ley_a_editar['id'] : 'Añadir Nueva Ley'; ?></h2>
        </div>
        <div class="admin-card-body">

            <?php if ($ley_a_editar): ?>
                <div class="edit-banner">
                    ✏️ Estás editando: <strong><?php echo htmlspecialchars($ley_a_editar['titulo']); ?></strong>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-admin">
                <?php if ($ley_a_editar): ?>
                    <input type="hidden" name="id" value="<?php echo $ley_a_editar['id']; ?>">
                <?php endif; ?>

                <input type="text" name="titulo"
                       placeholder="Título (Ej: LOMLOE — Ley Orgánica 3/2020)"
                       value="<?php echo $ley_a_editar ? htmlspecialchars($ley_a_editar['titulo']) : ''; ?>"
                       class="input-form" required>

                <input type="text" name="pdf_url"
                       placeholder="Archivo PDF (ej: lomloe.pdf)"
                       value="<?php echo $ley_a_editar ? htmlspecialchars($ley_a_editar['pdf_url']) : ''; ?>"
                       class="input-form">

                <input type="text" name="fecha"
                       placeholder="Fecha (Ej: 29/12/2020)"
                       value="<?php echo $ley_a_editar ? htmlspecialchars($ley_a_editar['fecha']) : ''; ?>"
                       class="input-form" required>

                <select name="estado" class="input-form" required style="margin-bottom:0;">
                    <option value="Vigente"   <?php echo ($ley_a_editar && $ley_a_editar['estado'] === 'Vigente')   ? 'selected' : ''; ?>>Vigente</option>
                    <option value="Derogada"  <?php echo ($ley_a_editar && $ley_a_editar['estado'] === 'Derogada')  ? 'selected' : ''; ?>>Derogada</option>
                </select>

                <input type="text" name="comunidad"
                       placeholder="Comunidad (Ej: Nacional, Asturias)"
                       value="<?php echo $ley_a_editar ? htmlspecialchars($ley_a_editar['comunidad']) : ''; ?>"
                       class="input-form" required>

                <textarea name="desc" rows="3"
                          placeholder="Descripción breve de la ley..."
                          required><?php echo $ley_a_editar ? htmlspecialchars($ley_a_editar['desc']) : ''; ?></textarea>

                <div class="form-admin-actions">
                    <?php if ($ley_a_editar): ?>
                        <button type="submit" name="edit_ley" class="btn-save btn-edit">
                            ✏️ Guardar cambios
                        </button>
                        <a href="admin.php" class="btn-cancel-edit">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="add_ley" class="btn-save">
                            ➕ Guardar ley
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ── TABLA ── -->
    <div class="admin-table-wrap" id="tabla-gestion">

        <div class="admin-table-head">
            <h2>Leyes registradas</h2>
            <!-- Controls -->
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <div class="admin-controls" style="margin-bottom:0; border:none; padding:0; background:transparent; box-shadow:none;">
                    <div class="controls-group">
                        <span class="controls-label">Filtro:</span>
                        <a href="?filtro=todos#tabla-gestion"     class="btn-filter <?php echo $filtro==='todos'?'active':''; ?>">Todos</a>
                        <a href="?filtro=nacionales#tabla-gestion" class="btn-filter <?php echo $filtro==='nacionales'?'active':''; ?>">Nacionales</a>
                        <a href="?filtro=vigentes#tabla-gestion"   class="btn-filter <?php echo $filtro==='vigentes'?'active':''; ?>">Vigentes</a>
                        <a href="?filtro=derogadas#tabla-gestion"  class="btn-filter <?php echo $filtro==='derogadas'?'active':''; ?>">Derogadas</a>
                    </div>
                    <div class="controls-group">
                        <span class="controls-label">ID:</span>
                        <a href="?orden=id_asc&filtro=<?php echo $filtro; ?>#tabla-gestion"  class="btn-filter <?php echo $orden==='id_asc'?'active':''; ?>">↑ Asc</a>
                        <a href="?orden=id_desc&filtro=<?php echo $filtro; ?>#tabla-gestion" class="btn-filter <?php echo $orden==='id_desc'?'active':''; ?>">↓ Desc</a>
                    </div>
                </div>
            </div>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Comunidad</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla">
                <?php foreach ($leyes as $ley): ?>
                <tr>
                    <td class="td-id">#<?php echo $ley['id']; ?></td>
                    <td class="td-title"><?php echo htmlspecialchars($ley['titulo']); ?></td>
                    <td><?php echo htmlspecialchars($ley['comunidad']); ?></td>
                    <td style="font-size:13px; color:var(--text-muted);"><?php echo $ley['fecha']; ?></td>
                    <td>
                        <?php
                        $eClass = strpos(strtolower($ley['estado']), 'derogada') !== false ? 'derogada' : 'vigente';
                        ?>
                        <span class="badge <?php echo $eClass; ?>"><?php echo $ley['estado']; ?></span>
                    </td>
                    <td>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <a href="admin.php?action=edit&id=<?php echo $ley['id']; ?>"
                               class="btn-table-edit">✏️ Editar</a>
                            <a href="admin.php?action=delete&id=<?php echo $ley['id']; ?>"
                               class="btn-table-delete"
                               onclick="return confirm('¿Eliminar «<?php echo htmlspecialchars($ley['titulo']); ?>»? Esta acción no se puede deshacer.');">
                               🗑️ Borrar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div><!-- /admin-container -->

<div class="footer-bottom" style="background:var(--primary); padding:16px 5%; text-align:center;">
    <p style="color:rgba(255,255,255,0.35); font-size:12.5px;">
        Panel de Administración — <span style="color:var(--accent-light);">EduLey</span> &copy; <?php echo date("Y"); ?>
    </p>
</div>

<script>
/* ── Fetch filter/sort without scroll jump ── */
document.addEventListener('click', function(e) {
    const link = e.target.closest('.btn-filter');
    if (!link) return;
    e.preventDefault();
    const url = link.getAttribute('href');

    fetch(url)
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newBody = doc.getElementById('cuerpo-tabla').innerHTML;
            document.getElementById('cuerpo-tabla').innerHTML = newBody;
            window.history.pushState({}, '', url);
        })
        .catch(() => { window.location.href = url; });
});

/* ── Auto-hide toast ── */
const toast = document.querySelector('.admin-toast');
if (toast) {
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        toast.style.transition = 'all 0.4s ease';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}
</script>

</body>
</html>
