/* ===================================================
   EDULEY — script.js
   Filtrado, paginación y favoritos 100% cliente.
   Los filtros del sidebar NO recargan la página.
   =================================================== */
'use strict';

const POR_PAGINA = 6;

let currentUser      = null;
let modoFav          = false;
let filtroActual     = '';      // '' = todas, 'vigente', 'derogada', 'nacional', o nombre CCAA
let busquedaActual   = '';      // texto del buscador
let paginaActual     = 1;
let todasLasTarjetas = [];
let listaActiva      = [];

/* ─── ARRANQUE ───────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    inyectarEstilos();

    todasLasTarjetas = Array.from(
        document.querySelectorAll('#contenedor-leyes .law-card')
    );

    // Ocultar todo de golpe antes de que el navegador lo pinte
    todasLasTarjetas.forEach(c => c.classList.add('card-oculta'));

    // Login + corazones
    checkLogin();

    // Leer búsqueda inicial desde data-busqueda del contenedor (puesta por PHP)
    const contenedor = document.getElementById('contenedor-leyes');
    busquedaActual = (contenedor?.dataset.busqueda || '').toLowerCase().trim();

    // Iniciar filtros de sidebar
    initFiltros();

    // Aplicar filtro/búsqueda inicial y mostrar página 1
    aplicarFiltro();

    initModalClose();
});

/* ═══════════════════════════════════════════════════
   FILTRADO CLIENTE
═══════════════════════════════════════════════════ */

/**
 * Calcula qué tarjetas coinciden con el filtro activo + búsqueda activa.
 * Actualiza listaActiva y re-renderiza desde página 1.
 * NO mueve el scroll.
 */
function aplicarFiltro(irPagina = 1) {
    modoFav = false;
    document.getElementById('btn-ver-favs')?.classList.remove('activo-fav');
    document.getElementById('btn-volver-todas')?.remove();

    listaActiva = todasLasTarjetas.filter(card => {
        const titulo    = card.dataset.titulo    || '';
        const estado    = card.dataset.estado    || '';
        const comunidad = card.dataset.comunidad || '';
        const desc      = card.dataset.desc      || '';

        // Filtro de sidebar
        let pasaFiltro = true;
        if (filtroActual) {
            pasaFiltro =
                estado.includes(filtroActual) ||
                comunidad.includes(filtroActual) ||
                titulo.includes(filtroActual);
        }

        // Filtro de buscador
        let pasaBusqueda = true;
        if (busquedaActual) {
            pasaBusqueda =
                titulo.includes(busquedaActual) ||
                estado.includes(busquedaActual) ||
                comunidad.includes(busquedaActual) ||
                desc.includes(busquedaActual);
        }

        return pasaFiltro && pasaBusqueda;
    });

    mostrarPagina(irPagina);
}

function initFiltros() {
    document.querySelectorAll('.filtro-link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();

            // Marcar activo
            document.querySelectorAll('.filtro-link').forEach(l => l.classList.remove('activo-filtro'));
            link.classList.add('activo-filtro');

            filtroActual = link.dataset.filtro || '';
            aplicarFiltro(1);

            // Sin scroll — el usuario ya ve el contenido
        });
    });

    // Buscador: interceptar submit para filtrar en cliente sin recargar
    const form = document.querySelector('.search-form');
    if (form) {
        form.addEventListener('submit', e => {
            e.preventDefault();
            const input = form.querySelector('input[name="q"]');
            busquedaActual = (input?.value || '').toLowerCase().trim();
            filtroActual   = '';
            document.querySelectorAll('.filtro-link').forEach(l => l.classList.remove('activo-filtro'));
            document.querySelector('.filtro-link[data-filtro=""]')?.classList.add('activo-filtro');
            aplicarFiltro(1);
            // Hacer scroll suave hasta las tarjetas
            document.querySelector('.content-area')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
}

/* ═══════════════════════════════════════════════════
   PAGINACIÓN
═══════════════════════════════════════════════════ */

function mostrarPagina(pagina) {
    paginaActual = pagina;

    todasLasTarjetas.forEach(c => {
        c.classList.add('card-oculta');
        c.classList.remove('card-visible');
    });

    const total        = listaActiva.length;
    const totalPaginas = Math.max(1, Math.ceil(total / POR_PAGINA));

    if (paginaActual > totalPaginas) paginaActual = totalPaginas;
    if (paginaActual < 1)            paginaActual = 1;

    const inicio = (paginaActual - 1) * POR_PAGINA;
    const slice  = listaActiva.slice(inicio, inicio + POR_PAGINA);

    slice.forEach((card, i) => {
        card.classList.remove('card-oculta');
        setTimeout(() => card.classList.add('card-visible'), i * 55);
    });

    actualizarContador(total);
    renderPaginacion(totalPaginas);

    document.querySelector('.no-results-js')?.remove();
    if (total === 0) {
        const div = document.createElement('div');
        div.className = 'no-results no-results-js';
        div.innerHTML = `
            <div class="no-results-icon">${modoFav ? '⭐' : '🔍'}</div>
            <h3>${modoFav
                ? 'Aún no tienes favoritos guardados.'
                : 'Sin resultados para este filtro.'}</h3>
            <p>${modoFav
                ? 'Pulsa ♡ en cualquier ley para añadirla a favoritos.'
                : 'Prueba otro término o selecciona otra categoría.'}</p>
        `;
        document.getElementById('paginacion-js')?.insertAdjacentElement('beforebegin', div);
    }
}

function renderPaginacion(totalPaginas) {
    const cont = document.getElementById('paginacion-js');
    if (!cont) return;

    if (totalPaginas <= 1) {
        cont.style.display = 'none';
        cont.innerHTML = '';
        return;
    }

    cont.style.display = 'flex';
    let html = '';

    if (paginaActual > 1)
        html += `<button class="btn-pagi" onclick="cambiarPagina(${paginaActual - 1})">« Anterior</button>`;

    html += '<div style="display:flex;gap:5px;align-items:center;">';
    for (let i = 1; i <= totalPaginas; i++) {
        const esActual  = i === paginaActual;
        const esVisible = i === 1 || i === totalPaginas || Math.abs(i - paginaActual) <= 1;
        const esElipsis = Math.abs(i - paginaActual) === 2 && i !== 1 && i !== totalPaginas;
        if (esVisible) {
            const st = esActual
                ? 'padding:8px 14px;background:var(--primary);color:#fff;border-color:var(--primary);'
                : 'padding:8px 14px;';
            html += `<button class="btn-pagi" style="${st}" onclick="cambiarPagina(${i})">${i}</button>`;
        } else if (esElipsis) {
            html += `<span style="color:var(--text-muted);padding:0 4px;">…</span>`;
        }
    }
    html += '</div>';

    if (paginaActual < totalPaginas)
        html += `<button class="btn-pagi" onclick="cambiarPagina(${paginaActual + 1})">Siguiente »</button>`;

    cont.innerHTML = html;
}

function cambiarPagina(num) {
    mostrarPagina(num);
    // Solo scroll hasta las tarjetas, sin ir al top de la página
    document.querySelector('.content-area')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function actualizarContador(total) {
    const span = document.querySelector('.results-count');
    if (!span) return;

    if (modoFav) {
        span.innerHTML = `Mostrando <strong>${total}</strong> ley${total !== 1 ? 'es' : ''} en ⭐ Mis Favoritos
            <button onclick="volverATodas()" style="font-size:13px;color:var(--text-muted);background:none;border:none;cursor:pointer;font-family:inherit;text-decoration:underline;padding:0;margin-left:8px;">× Ver todas</button>`;
        return;
    }

    let label = 'Mostrando';
    if (busquedaActual) label = `Resultados para "<strong>${busquedaActual}</strong>":`;

    const comunidadesNombres = {
        'andalucía':'Andalucía','aragón':'Aragón','asturias':'Asturias',
        'canarias':'Canarias','cantabria':'Cantabria','castilla y león':'Castilla y León',
        'cataluña':'Cataluña','comunidad valenciana':'Comunidad Valenciana',
        'galicia':'Galicia','madrid':'Madrid','país vasco':'País Vasco'
    };

    if (filtroActual && !busquedaActual) {
        const nombre = {
            'vigente': '✅ Solo Vigentes',
            'derogada': '❌ Derogadas',
            'nacional': '🏛️ Nacionales',
        }[filtroActual] || ('📍 ' + (comunidadesNombres[filtroActual] || filtroActual));
        label = `Filtro: <strong>${nombre}</strong> —`;
    }

    span.innerHTML = `${label} <strong>${total}</strong> documentos
        ${filtroActual || busquedaActual
            ? `<button onclick="limpiarFiltros()" style="font-size:13px;color:var(--text-muted);background:none;border:none;cursor:pointer;font-family:inherit;text-decoration:underline;padding:0;margin-left:8px;">× Limpiar</button>`
            : ''}`;
}

function limpiarFiltros() {
    filtroActual   = '';
    busquedaActual = '';
    const input = document.querySelector('.search-form input[name="q"]');
    if (input) input.value = '';
    document.querySelectorAll('.filtro-link').forEach(l => l.classList.remove('activo-filtro'));
    document.querySelector('.filtro-link[data-filtro=""]')?.classList.add('activo-filtro');
    aplicarFiltro(1);
}

/* ═══════════════════════════════════════════════════
   FAVORITOS
═══════════════════════════════════════════════════ */

function getFavoritosIds() {
    if (!currentUser) return [];
    const db = JSON.parse(localStorage.getItem('eduley_users') || '{}');
    return db[currentUser]?.favoritos || [];
}

function getTarjetasFavoritas() {
    const ids = getFavoritosIds();
    return todasLasTarjetas.filter(c => ids.includes(parseInt(c.dataset.id)));
}

function toggleFavorito(idLey) {
    if (!currentUser) {
        showNotification('Inicia sesión para guardar favoritos.', 'info');
        abrirModal();
        return;
    }

    const db      = JSON.parse(localStorage.getItem('eduley_users') || '{}');
    const profile = db[currentUser];
    if (!profile) return;

    const idx = profile.favoritos.indexOf(idLey);
    const btn = document.getElementById(`fav-btn-${idLey}`);

    if (idx === -1) {
        profile.favoritos.push(idLey);
        if (btn) { btn.classList.add('es-favorito'); btn.textContent = '❤️ Favorito'; }
        showNotification('Añadido a favoritos.', 'success');
    } else {
        profile.favoritos.splice(idx, 1);
        if (btn) { btn.classList.remove('es-favorito'); btn.textContent = '♡ Favorito'; }
        showNotification('Eliminado de favoritos.', 'info');
        if (modoFav) {
            listaActiva = getTarjetasFavoritas();
            mostrarPagina(1);
        }
    }

    localStorage.setItem('eduley_users', JSON.stringify(db));
}

function pintarCorazonesGuardados() {
    if (!currentUser) return;
    const ids = getFavoritosIds();
    document.querySelectorAll('.btn-fav').forEach(btn => {
        const id = parseInt(btn.id.replace('fav-btn-', ''));
        if (ids.includes(id)) {
            btn.classList.add('es-favorito');
            btn.textContent = '❤️ Favorito';
        }
    });
}

function mostrarSoloFavoritos() {
    if (!currentUser) {
        showNotification('Inicia sesión para ver tus favoritos.', 'info');
        abrirModal();
        return;
    }

    modoFav = true;
    filtroActual   = '';
    busquedaActual = '';
    document.querySelectorAll('.filtro-link').forEach(l => l.classList.remove('activo-filtro'));
    document.getElementById('btn-ver-favs')?.classList.add('activo-fav');

    listaActiva = getTarjetasFavoritas();
    mostrarPagina(1);
    // Sin scroll — el usuario ya ve el área de contenido
}

function volverATodas() {
    modoFav = false;
    filtroActual   = '';
    busquedaActual = '';
    document.getElementById('btn-ver-favs')?.classList.remove('activo-fav');
    document.querySelector('.filtro-link[data-filtro=""]')?.classList.add('activo-filtro');
    const input = document.querySelector('.search-form input[name="q"]');
    if (input) input.value = '';
    listaActiva = todasLasTarjetas;
    mostrarPagina(1);
}

/* ═══════════════════════════════════════════════════
   AUTH
═══════════════════════════════════════════════════ */

function checkLogin() {
    currentUser = localStorage.getItem('eduley_currentUser');
    const btn   = document.getElementById('btn-mi-cuenta');
    if (!btn) return;

    if (currentUser) {
        btn.textContent = `Hola, ${currentUser} ▾`;
        btn.classList.add('logged-in');
        pintarCorazonesGuardados();
    } else {
        btn.textContent = 'Iniciar Sesión';
        btn.classList.remove('logged-in');
    }
}

function abrirModal() {
    if (currentUser) {
        if (confirm(`¿Deseas cerrar la sesión de "${currentUser}"?`)) {
            localStorage.removeItem('eduley_currentUser');
            location.reload();
        }
        return;
    }
    const modal = document.getElementById('auth-modal');
    modal.style.display = 'flex';
    setTimeout(() => modal.querySelector('input')?.focus(), 80);
}

function cerrarModal() {
    document.getElementById('auth-modal').style.display = 'none';
}

function toggleAuthMode() {
    const lf    = document.getElementById('login-form');
    const rf    = document.getElementById('register-form');
    const title = document.getElementById('modal-title');
    const isLogin = lf.style.display !== 'none';
    lf.style.display  = isLogin ? 'none'  : 'block';
    rf.style.display  = isLogin ? 'block' : 'none';
    title.textContent = isLogin ? 'Crear Cuenta' : 'Iniciar Sesión';
}

function registrar() {
    const nombre = document.getElementById('reg-nombre').value.trim();
    const user   = document.getElementById('reg-user').value.trim();
    const email  = document.getElementById('reg-email').value.trim();
    const pass   = document.getElementById('reg-pass').value.trim();

    if (!nombre || !user || !email || !pass) {
        showNotification('Rellena todos los campos obligatorios.', 'error'); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showNotification('Correo electrónico no válido.', 'error'); return;
    }

    const db = JSON.parse(localStorage.getItem('eduley_users') || '{}');
    for (let u in db) {
        if (u.toLowerCase() === user.toLowerCase()) {
            showNotification('Nombre de usuario ya registrado.', 'error'); return;
        }
        if (db[u].email?.toLowerCase() === email.toLowerCase()) {
            showNotification('Correo ya en uso.', 'error'); return;
        }
    }

    db[user] = {
        password: pass, nombre,
        apellidos: document.getElementById('reg-apellidos').value.trim(),
        email, favoritos: []
    };
    localStorage.setItem('eduley_users', JSON.stringify(db));
    showNotification('¡Registro exitoso! Ya puedes iniciar sesión.', 'success');
    setTimeout(toggleAuthMode, 1200);
}

function login() {
    const idEl   = document.getElementById('login-id');
    const passEl = document.getElementById('login-pass');
    if (!idEl || !passEl) return;

    const identifier = idEl.value.trim();
    const pass       = passEl.value.trim();
    if (!identifier || !pass) {
        showNotification('Rellena todos los campos.', 'error'); return;
    }

    const db = JSON.parse(localStorage.getItem('eduley_users') || '{}');
    let found = db[identifier] ? identifier : null;
    if (!found) {
        for (let u in db) {
            if (db[u].email === identifier) { found = u; break; }
        }
    }

    if (found && db[found].password === pass) {
        localStorage.setItem('eduley_currentUser', found);
        showNotification(`¡Bienvenido/a, ${found}!`, 'success');
        setTimeout(() => { cerrarModal(); location.reload(); }, 900);
    } else {
        showNotification('Usuario o contraseña incorrectos.', 'error');
        passEl.value = ''; passEl.focus();
    }
}

function initModalClose() {
    const overlay = document.getElementById('auth-modal');
    if (!overlay) return;
    overlay.addEventListener('click', e => { if (e.target === overlay) cerrarModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });
}

/* ─── ESTILOS DINÁMICOS ──────────────────────────── */
function inyectarEstilos() {
    if (document.getElementById('_edu_styles')) return;
    const s = document.createElement('style');
    s.id = '_edu_styles';
    s.textContent = `
        .law-card.card-oculta { display: none !important; }
        .law-card {
            opacity: 0;
            transform: translateY(14px);
            transition: opacity .35s ease, transform .35s ease, box-shadow .2s ease;
        }
        .law-card.card-visible { opacity: 1; transform: translateY(0); }

        /* Filtro activo en sidebar */
        .filtro-link.activo-filtro {
            color: var(--primary) !important;
            font-weight: 700;
            background: var(--surface);
            border-radius: var(--radius-sm);
        }
        #btn-ver-favs.activo-fav {
            color: #f59e0b !important;
            font-weight: 700;
        }
    `;
    document.head.appendChild(s);
}

/* ─── TOAST ──────────────────────────────────────── */
function showNotification(msg, type = 'info') {
    document.querySelectorAll('.notif-toast').forEach(n => n.remove());
    const col  = { success: 'var(--success)', error: 'var(--danger)', info: 'var(--primary)' };
    const icon = { success: '✅', error: '⚠️', info: 'ℹ️' };
    const el   = document.createElement('div');
    el.className = 'notif-toast';
    el.innerHTML = `${icon[type]} ${msg}`;
    el.style.cssText = `
        position:fixed;top:84px;right:20px;
        background:${col[type]};color:#fff;
        padding:12px 20px;border-radius:10px;
        font-family:inherit;font-size:14px;font-weight:500;
        box-shadow:0 8px 24px rgba(0,0,0,.2);z-index:9999;
        display:flex;align-items:center;gap:8px;
        opacity:0;transform:translateX(20px);
        transition:opacity .25s ease,transform .25s ease;
        max-width:300px;pointer-events:none;
    `;
    document.body.appendChild(el);
    requestAnimationFrame(() => { el.style.opacity='1'; el.style.transform='translateX(0)'; });
    setTimeout(() => {
        el.style.opacity='0'; el.style.transform='translateX(20px)';
        setTimeout(() => el.remove(), 280);
    }, 3000);
}
