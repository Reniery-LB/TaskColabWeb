// assets/javascript/users.js - VERSIÓN CORREGIDA PARA PRODUCCIÓN
console.log("users.js cargado - Inicializando...");

document.addEventListener('DOMContentLoaded', () => {
    // === ELEMENTOS DEL DOM ===
    const tbody = document.querySelector('.tabla-usuarios');
    const detalleNombre = document.getElementById('detNombre');
    const detalleCorreo = document.getElementById('detCorreo');
    const detalleTareas = document.getElementById('detTareas');
    const detalleEstado = document.getElementById('detEstado');
    const detalleFecha = document.getElementById('detFecha');

    const inputEditNombre = document.getElementById('editNombre');
    const inputEditCorreo = document.getElementById('editCorreo');
    const inputEditTareas = document.getElementById('editTareas');
    const inputEditEstado = document.getElementById('editEstado');
    const inputEditFecha = document.getElementById('editFecha');

    const btnVolver = document.getElementById('btnVolverUsuario');
    const btnEditarDesdeDetalle = document.getElementById('btnEditarUsuario');
    const btnEliminarDesdeDetalle = document.getElementById('btnEliminarUsuario');
    const btnAceptarEditar = document.getElementById('btnAceptarEditar');
    const btnCancelarEditar = document.getElementById('btnCancelarEditar');

    // === VARIABLES GLOBALES ===
    let usersCache = [];
    let usuarioSeleccionado = null;
    
    // SISTEMA DE CONTROL DE EVENTOS
    let controlEventos = {
        ultimoEvento: null,
        enProgreso: false,
        timeout: null
    };

    // === FUNCIONES AUXILIARES ===
    
    // Función para manejar eventos de tareas asignadas desde Admin
    async function manejarTareasAsignadas(event) {
        console.log("===== USERS.JS: EVENTO 'tareasAsignadasDesdeAdmin' =====");
        console.log("Detalles:", event.detail);
        console.log("Hora:", new Date().toLocaleTimeString());
        
        console.log("FORZANDO RECARGA INMEDIATA DE USUARIOS");
        await loadUsers();
        console.log("Usuarios recargados");
        console.log("===== FIN EVENTO =====\n");
    }

    // Función para manejar actualización de conteo de tareas
    async function manejarActualizarConteo(event) {
        console.log("===== USERS.JS: EVENTO 'actualizarConteoTareas' =====");
        console.log("Detalles:", event.detail);
        console.log("Hora:", new Date().toLocaleTimeString());
        
        console.log("Recargando tabla de usuarios...");
        await loadUsers();
        console.log("Tabla de usuarios actualizada");
        console.log("===== FIN EVENTO =====\n");
    }

    // API base - PARA PRODUCCIÓN
    const apiBase = window.API_BASE || '/assets/app/endpoints';
    console.log("API_BASE configurado:", apiBase);

    // === CARGAR USUARIOS ===
    async function loadUsers() {
        try {
            const timestamp = Date.now();
            const random = Math.floor(Math.random() * 1000000);
            const url = `${apiBase}/list_users.php?t=${timestamp}&r=${random}&_=${Date.now()}`;
            
            console.log('Cargando usuarios desde:', url);
            
            const res = await fetch(url, { 
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                }
            });
            
            console.log('Respuesta HTTP:', res.status, res.statusText);
            
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            
            const json = await res.json();
            
            if (!json.ok) {
                throw new Error(json.message || 'Error en la respuesta del servidor');
            }
            
            usersCache = Array.isArray(json.users) ? json.users : [];
            
            // Mostrar conteo
            console.log('USUARIOS CARGADOS:');
            if (usersCache.length === 0) {
                console.log('    No hay usuarios para mostrar');
            } else {
                usersCache.forEach(user => {
                    console.log(` ${user.name}: ${user.assigned_count || 0} tareas`);
                });
            }
            
            renderTable(usersCache);
            console.log('Tabla de usuarios actualizada correctamente');
            
        } catch (err) {
            console.error('ERROR en loadUsers:', err);
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; color: red; padding: 20px;">
                            Error al cargar usuarios
                        </td>
                    </tr>
                `;
            }
        }
    }

    // === RENDERIZAR TABLA ===
    function renderTable(users) {
        if (!tbody) {
            console.error("No se encontró tbody para la tabla de usuarios");
            return;
        }

        if (!users || users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align: center; color: #666; padding: 20px;">
                        No hay usuarios registrados
                    </td>
                </tr>
            `;
            return;
        }

        console.log(`Renderizando ${users.length} usuarios en la tabla`);
        
        const current = window.CURRENT_USER || null;
        
        tbody.innerHTML = users.map(user => {
            const esUsuarioActual = current && String(current.id) === String(user.id);
            const esActivo = user.is_active == 1;
            
            let estiloFila = '';
            let marcadorEstado = '';
            
            if (esUsuarioActual) {
                estiloFila = 'background-color: #e3f2fd;'; 
                marcadorEstado = ' (Tú)';
            } else if (!esActivo) {
                estiloFila = 'background-color: #f5f5f5; color: #999;'; 
                marcadorEstado = ' (Inactivo)';
            }
            
            return `
                <tr data-id="${user.id}" style="${estiloFila}">
                    <td>${escapeHtml(user.name)}${marcadorEstado}</td>
                    <td>${escapeHtml(user.email)}</td>
                    <td>${user.assigned_count || 0}</td>
                    <td>${escapeHtml(user.notes || '')}</td>
                </tr>
            `;
        }).join('');
        
        console.log(`${users.length} usuarios renderizados (incluyendo inactivos)`);
    }

    // === FUNCIONES DE DETALLE ===
    function actualizarVistaDetalle(user) {
        console.log("Actualizando vista detalle:", user);
        
        if (!user) {
            console.error("Usuario es null");
            return;
        }
        
        if (detalleNombre) detalleNombre.textContent = user.name || '';
        if (detalleCorreo) detalleCorreo.textContent = user.email || '';
        if (detalleTareas) detalleTareas.textContent = `${user.assigned_count || 0} tareas`;
        
        if (detalleEstado) {
            const esActivo = user.is_active == 1;
            detalleEstado.textContent = esActivo ? 'Activo' : 'Inactivo';
        }
        
        if (detalleFecha) {
            if (user.last_login && user.last_login !== '0000-00-00 00:00:00') {
                try {
                    const fecha = new Date(user.last_login);
                    if (!isNaN(fecha.getTime())) {
                        detalleFecha.textContent = fecha.toLocaleDateString('es-MX', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } else {
                        detalleFecha.textContent = 'Fecha inválida';
                    }
                } catch (error) {
                    detalleFecha.textContent = user.last_login;
                }
            } else {
                detalleFecha.textContent = 'Sin actividad';
            }
        }
        
        console.log("Vista detalle actualizada");
    }

    function abrirDetalle(user) {
        console.log(`Abriendo detalle del usuario: ${user.name}`);
        
        usuarioSeleccionado = user;
        actualizarVistaDetalle(user);

        const current = window.CURRENT_USER || null;
        const esMismoUsuario = current && String(current.id) === String(user.id);

        console.log(`Es mismo usuario: ${esMismoUsuario}`);
        console.log(`Edición permitida: ${esMismoUsuario ? 'SÍ' : 'NO'}`);

        if (btnEditarDesdeDetalle) {
            btnEditarDesdeDetalle.style.display = esMismoUsuario ? 'inline-block' : 'none';
        }
        
        if (btnEliminarDesdeDetalle) {
            btnEliminarDesdeDetalle.style.display = 'none';
        }

        if (typeof mostrarSeccion === 'function') {
            mostrarSeccion('detalleUsuario');
        }
    }

    // === EVENT LISTENERS ===

    // Click en fila de usuario
    tbody?.addEventListener('click', (e) => {
        const tr = e.target.closest('tr');
        if (!tr) return;
        const id = tr.dataset.id;
        if (!id) return;
        
        console.log(`Click en usuario ID: ${id}`);
        
        const user = usersCache.find(x => String(x.id) === String(id));
        if (user) {
            abrirDetalle(user);
        }
    });

    // Eventos para actualización automática
    window.addEventListener('actualizarConteoTareas', manejarActualizarConteo);
    window.addEventListener('tareasAsignadasDesdeAdmin', manejarTareasAsignadas);
    window.addEventListener('usuarioCreadoDesdeAdmin', () => loadUsers());
    window.addEventListener('usuarioEliminadoDesdeAdmin', () => loadUsers());
    window.addEventListener('usuarioActualizadoDesdeAdmin', () => loadUsers());

    // Eventos de tareas
    window.addEventListener('tareaCreada', () => loadUsers());
    window.addEventListener('tareaCreadadesdeTableros', () => loadUsers());
    window.addEventListener('tareaCreadaDesdeTareas', () => loadUsers());
    window.addEventListener('tareaEliminadaDesdeTareas', () => loadUsers());
    window.addEventListener('tareaEliminadaDesdeTableros', () => loadUsers());

    // === BOTONES ===
    btnVolver?.addEventListener('click', () => {
        if (typeof mostrarSeccion === 'function') mostrarSeccion('usuarios');
    });

    btnCancelarEditar?.addEventListener('click', () => {
        if (usuarioSeleccionado) {
            abrirDetalle(usuarioSeleccionado);
        } else {
            if (typeof mostrarSeccion === 'function') mostrarSeccion('usuarios');
        }
    });

    btnEditarDesdeDetalle?.addEventListener('click', () => {
        if (!usuarioSeleccionado) return;
        
        console.log(`Abriendo edición para: ${usuarioSeleccionado.name}`);
        
        if (inputEditNombre) inputEditNombre.value = usuarioSeleccionado.name || '';
        if (inputEditCorreo) inputEditCorreo.value = usuarioSeleccionado.email || '';
        if (inputEditTareas) inputEditTareas.value = usuarioSeleccionado.assigned_count || '0';
        if (inputEditEstado) inputEditEstado.value = usuarioSeleccionado.is_active == 1 ? '1' : '0';
        
        if (typeof mostrarSeccion === 'function') {
            mostrarSeccion('editarUsuario');
        }
    });

    btnAceptarEditar?.addEventListener('click', async (e) => {
        e.preventDefault();
        if (!usuarioSeleccionado) return;
        
        const id = usuarioSeleccionado.id;
        if (!id) return;

        console.log("Guardando cambios para usuario ID:", id);
        
        if (typeof configurarAlerta === 'function') {
            configurarAlerta(
                "Confirmar Cambios",
                "¿Estás seguro de que deseas guardar los cambios realizados?",
                "alerta",
                {
                    textoConfirmar: "Confirmar",
                    onConfirmar: async () => {
                        console.log("Usuario confirmó guardar cambios");
                        await guardarCambiosUsuario(id);
                    },
                    onCancelar: () => {
                        console.log("Usuario canceló la edición");
                        if (typeof mostrarSeccion === 'function') mostrarSeccion('editarUsuario');
                    }
                }
            );
        } else {
            if (confirm("¿Estás seguro de guardar los cambios?")) {
                await guardarCambiosUsuario(id);
            }
        }
    });

    async function guardarCambiosUsuario(id) {
        const payload = {
            id,
            name: inputEditNombre.value.trim(),
            email: inputEditCorreo.value.trim(),
            notes: usuarioSeleccionado.notes || '', 
            is_active: parseInt(inputEditEstado.value)
        };

        console.log("ENVIANDO:", payload);

        try {
            const res = await fetch(`${apiBase}/update_user.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            
            const json = await res.json();
            console.log("RESPUESTA SERVIDOR:", json);
            
            if (json.ok && json.user) {
                console.log(`Usuario ${id} actualizado`);
                
                // Actualizar cache
                const idx = usersCache.findIndex(u => String(u.id) === String(id));
                if (idx !== -1) {
                    usersCache[idx] = json.user;
                }
                
                usuarioSeleccionado = json.user;
                actualizarVistaDetalle(json.user);
                
                // Actualizar CURRENT_USER si es el mismo
                const current = window.CURRENT_USER;
                if (current && String(current.id) === String(id)) {
                    window.CURRENT_USER.name = json.user.name;
                    window.CURRENT_USER.email = json.user.email;
                    
                    const headerName = document.querySelector('.user-info h3');
                    if (headerName) headerName.textContent = json.user.name;
                    
                    const profileCircle = document.querySelector('.profile-circle');
                    if (profileCircle && !current.avatar_url) {
                        profileCircle.textContent = json.user.name.charAt(0).toUpperCase();
                    }
                    
                    // Disparar evento para profile.js
                    const evento = new CustomEvent('usuarioActualizado', {
                        detail: {
                            nombre: json.user.name,
                            email: json.user.email,
                            user: json.user,
                            timestamp: new Date().getTime()
                        }
                    });
                    window.dispatchEvent(evento);
                }
                
                // Regresar a detalle
                setTimeout(() => {
                    if (typeof mostrarSeccion === 'function') {
                        mostrarSeccion('detalleUsuario');
                    }
                }, 100);
                
                // Alerta éxito
                if (typeof configurarAlerta === 'function') {
                    configurarAlerta(
                        "Cambios Realizados",
                        "Usuario actualizado correctamente",
                        "exito",
                        { soloAceptar: true }
                    );
                }
                
            } else {
                throw new Error(json.message || 'Error del servidor');
            }
        } catch (err) {
            console.error('ERROR:', err);
            if (typeof configurarAlerta === 'function') {
                configurarAlerta(
                    "Error",
                    "Error al actualizar: " + err.message,
                    "alerta",
                    { soloAceptar: true }
                );
            }
        }
    }

    // === FUNCIONES UTILITARIAS ===
    function escapeHtml(str = '') {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // === INICIALIZACIÓN ===
    console.log("Inicializando tabla de usuarios...");
    console.log("Listeners registrados en profile.js:");
    console.log("   - usuarioActualizado");
    console.log("   - tareaCreada");
    console.log("   - tareasEliminadas");
    
    // Cargar usuarios inicialmente
    setTimeout(() => {
        loadUsers();
    }, 500);

    console.log("users.js inicializado correctamente");
});