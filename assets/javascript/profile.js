// assets/javascript/profile.js 
document.addEventListener('DOMContentLoaded', function() {
    console.log("Inicializando profile.js");
    
    // Elementos del DOM
    const btnCambiarNombre = document.querySelector('.btn-cambiar[data-campo="nombre"]') || 
                             document.getElementById('btnCambiarNombre');
    const btnCambiarCorreo = document.querySelector('.btn-cambiar[data-campo="correo"]') || 
                            document.getElementById('btnCambiarCorreo');
    const btnCambiarPassword = document.querySelector('.btn-cambiar[data-campo="contrasena"]') || 
                              document.getElementById('btnCambiarPassword');
    const btnSubirAvatar = document.getElementById('btnSubirAvatar');
    const inputAvatar = document.getElementById('inputAvatar');
    const btnEliminarCuenta = document.getElementById('btnEliminarCuenta') || 
                              document.querySelector('.link-eliminar-cuenta');
    
    // API base para endpoints del perfil
    const apiBase = window.API_BASE_PERFIL || '/PROYECTO_GESTOR_TAREAS/assets/app/endpointsPerfil';
    console.log("API_BASE Perfil configurado:", apiBase);
    let archivoAvatarSeleccionado = null;
    inicializarProfileCompleto();

    // ESCUCHAR EVENTO DE ACTUALIZACIÓN DESDE users.js
    window.addEventListener('usuarioActualizado', (event) => {
        console.log("EVENTO 'usuarioActualizado' RECIBIDO EN PROFILE.JS");
        console.log("Datos:", event.detail);
        
        if (event.detail.user) {
            // ACTUALIZAR TODOS LOS DATOS, INCLUYENDO FECHA
            window.CURRENT_USER.name = event.detail.user.name;
            window.CURRENT_USER.email = event.detail.user.email;
            window.CURRENT_USER.last_login = event.detail.user.last_login;
            window.CURRENT_USER.assigned_count = event.detail.user.assigned_count;
            
            console.log("CURRENT_USER actualizado completamente:", window.CURRENT_USER);
        } else {
            if (event.detail.nombre) window.CURRENT_USER.name = event.detail.nombre;
            if (event.detail.email) window.CURRENT_USER.email = event.detail.email;
        }
        
        // RECARGAR DATOS DEL PERFIL PARA REFLEJAR CAMBIOS
        console.log("Recargando datos del perfil...");
        cargarDatosPerfil();
        
        console.log("Perfil actualizado con datos de users.js");
    });

    console.log("Listener 'usuarioActualizado' registrado en profile.js");

    // --- CARGAR DATOS DEL PERFIL ---
    function cargarDatosPerfil() {
        console.log("=== CARGANDO DATOS DEL PERFIL ===");
        
        const currentUser = window.CURRENT_USER;
        console.log("Usuario actual (window.CURRENT_USER):", currentUser);
        
        if (!currentUser) {
            console.error("No hay usuario en sesión (window.CURRENT_USER es null/undefined)");
            return;
        }

        const nombreInput = document.querySelector('#perfil .nombre-usuario');
        const correoInput = document.querySelector('#perfil .correo-usuario');
        const avatarDiv = document.getElementById('avatarPerfil') || document.querySelector('.foto-perfil');

        console.log("Elementos encontrados:");
        console.log("   - Input nombre:", nombreInput ? "Encontrado" : "NO encontrado");
        console.log("   - Input correo:", correoInput ? "Encontrado" : "NO encontrado");
        console.log("   - Avatar div:", avatarDiv ? "Encontrado" : "NO encontrado");

        if (nombreInput) {
            nombreInput.value = currentUser.name || 'Usuario';
            console.log("Nombre actualizado:", nombreInput.value);
        }
        
        if (correoInput) {
            correoInput.value = currentUser.email || 'correo@example.com';
            console.log("Correo actualizado:", correoInput.value);
        }
        
        // CARGAR AVATAR
        if (avatarDiv) {
            if (currentUser.avatar_url) {
                const timestamp = new Date().getTime();
                const avatarUrl = `${currentUser.avatar_url}?t=${timestamp}`;
                avatarDiv.innerHTML = `<img src="${avatarUrl}" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                console.log("Avatar con imagen cargado:", currentUser.avatar_url);
            } else {
                const initial = (currentUser.name || 'U').charAt(0).toUpperCase();
                avatarDiv.textContent = initial;
                avatarDiv.style.backgroundImage = 'none';
                console.log("Avatar con inicial cargado:", initial);
            }
        }
        
        actualizarAvatarEnHeader(currentUser.avatar_url);
        console.log("=== FIN CARGA DATOS PERFIL ===");
    }

    // --- CARGAR ACTIVIDAD DEL USUARIO ---
    async function cargarActividadUsuario(forceRefresh = false) {
        try {
            const timestamp = new Date().getTime();
            const url = `${apiBase}/get_user_activity.php?t=${timestamp}`;
            
            console.log("Cargando actividad desde:", url);
            
            const res = await fetch(url, { 
                method: 'GET',
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                }
            });
            
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            
            const json = await res.json();
            console.log("Actividad recibida:", json);

            if (json.ok && json.tareas) {
                console.log(` ${json.tareas.length} tareas cargadas para usuario`);
                renderizarActividad(json.tareas);
            } else {
                console.warn("No hay tareas o respuesta inválida");
                renderizarActividad([]);
            }
            
            return json.tareas || [];
        } catch (error) {
            console.error('Error cargando actividad:', error);
            renderizarActividad([]);
            return [];
        }
    }

    // Actualizar automáticamente cada 30 segundos
    function iniciarActualizacionAutomatica() {
        console.log("Iniciando actualización automática cada 30 segundos");
        setInterval(() => {
            console.log("Actualización automática de actividad...");
            cargarActividadUsuario(true);
        }, 30000); // 30 segundos
    }

    // Actualizar la función formatActivityDate
    function renderizarActividad(tareas) {
        const tbody = document.querySelector('#tablaActividad tbody') || 
                    document.querySelector('.tabla-actividad tbody');
        if (!tbody) {
            console.warn("No se encontró tabla de actividad");
            return;
        }

        if (!tareas || tareas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No hay tareas asignadas</td></tr>';
            return;
        }

        tbody.innerHTML = tareas.map(tarea => `
            <tr>
                <td>${escapeHtml(tarea.tarea)}</td>
                <td><span>${escapeHtml(tarea.estado)}</span></td>
                <td>${formatActivityDate(tarea.fecha_limite)}</td> <!-- Usar la nueva función -->
            </tr>
        `).join('');
        
        console.log("Actividad renderizada:", tareas.length, "tareas");
    }

    // --- CAMBIAR NOMBRE ---
    btnCambiarNombre?.addEventListener('click', () => {
        console.log("Editando nombre");
        const currentUser = window.CURRENT_USER;
        
        const inputNuevoNombre = document.querySelector('#editar-nombre .input-edicion');
        if (inputNuevoNombre && currentUser) {
            inputNuevoNombre.value = currentUser.name || '';
            inputNuevoNombre.placeholder = currentUser.name || 'Tu nombre actual';
        }
        
        if (typeof window.mostrarSeccion === 'function') {
            window.mostrarSeccion('editar-nombre');
        }
    });

    // Formulario cambiar nombre
    const formNombre = document.querySelector('#editar-nombre form');
    formNombre?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const inputNombre = document.querySelector('#editar-nombre .input-edicion');
        const nuevoNombre = inputNombre?.value.trim();
        
        if (!nuevoNombre) {
            mostrarError('El nombre no puede estar vacío', 'editar-nombre');
            return;
        }

        await actualizarPerfil({ name: nuevoNombre });
    });

    // --- CAMBIAR CORREO ---
    btnCambiarCorreo?.addEventListener('click', () => {
        console.log("Editando correo");
        const currentUser = window.CURRENT_USER;
        
        // Precargar correo actual
        const inputNuevoCorreo = document.getElementById('nuevo-correo');
        const inputConfirmarCorreo = document.getElementById('confirmar-correo');
        
        if (inputNuevoCorreo && currentUser) {
            inputNuevoCorreo.placeholder = currentUser.email || 'Tu correo actual';
        }
        
        if (inputConfirmarCorreo && currentUser) {
            inputConfirmarCorreo.placeholder = currentUser.email || 'Confirmar correo';
        }
        
        if (typeof window.mostrarSeccion === 'function') {
            window.mostrarSeccion('editar-correo');
        }
    });

    // Formulario cambiar correo
    const formCorreo = document.querySelector('#editar-correo form');
    formCorreo?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const nuevoCorreo = document.getElementById('nuevo-correo')?.value.trim();
        const confirmarCorreo = document.getElementById('confirmar-correo')?.value.trim();

        if (!nuevoCorreo || !confirmarCorreo) {
            mostrarError('Todos los campos son obligatorios', 'editar-correo');
            return;
        }

        if (nuevoCorreo !== confirmarCorreo) {
            mostrarError('Los correos electrónicos no coinciden', 'editar-correo');
            return;
        }

        if (!validarEmail(nuevoCorreo)) {
            mostrarError('El formato del correo electrónico no es válido', 'editar-correo');
            return;
        }

        await actualizarPerfil({ email: nuevoCorreo });
    });

    // --- CAMBIAR CONTRASEÑA ---
    btnCambiarPassword?.addEventListener('click', () => {
        console.log("Editando contraseña");
        
        // Limpiar campos
        const inputs = document.querySelectorAll('#editar-contrasena input[type="password"]');
        inputs.forEach(input => {
            input.value = '';
            input.placeholder = input.id === 'currentPassword' ? 'Contraseña actual' : 
                            input.id === 'newPassword' ? 'Nueva contraseña (mín. 6 caracteres)' : 
                            'Confirma tu nueva contraseña';
        });
        
        if (typeof window.mostrarSeccion === 'function') {
            window.mostrarSeccion('editar-contrasena');
        }
    });

    // Formulario cambiar contraseña
    const formPassword = document.querySelector('#editar-contrasena form');
    formPassword?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const currentPassword = document.getElementById('currentPassword')?.value;
        const newPassword = document.getElementById('newPassword')?.value;
        const confirmPassword = document.getElementById('confirmPassword')?.value;

        console.log("Validando contraseñas:", {
            currentPassword: currentPassword ? '***' : 'vacío',
            newPassword: newPassword ? '***' : 'vacío', 
            confirmPassword: confirmPassword ? '***' : 'vacío'
        });

        if (!currentPassword || !newPassword || !confirmPassword) {
            mostrarError('Todos los campos son obligatorios', 'editar-contrasena');
            return;
        }

        if (newPassword !== confirmPassword) {
            mostrarError('Las contraseñas no coinciden', 'editar-contrasena');
            return;
        }

        if (newPassword.length < 6) {
            mostrarError('La contraseña debe tener al menos 6 caracteres', 'editar-contrasena');
            return;
        }

        await cambiarPassword({
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword
        });
    });

    // --- FLUJO COMPLETO PARA SUBIR AVATAR ---
    function inicializarFlujoAvatar() {
        console.log("Inicializando flujo de avatar...");
        
        const cancelarCambiarFoto = document.getElementById('cancelarCambiarFoto');
        const subirCambiarFoto = document.getElementById('subirCambiarFoto');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        
        // 1. Botón "Importar foto" -> Mostrar alerta-cambiar-foto
        btnSubirAvatar?.addEventListener('click', (e) => {
            e.preventDefault();
            console.log("Click en Importar foto");
            
            if (typeof window.mostrarSeccion === 'function') {
                window.mostrarSeccion('alerta-cambiar-foto');
            }
        });
        
        // 2. En la alerta-cambiar-foto, botón "Subir" -> Abrir selector de archivos
        subirCambiarFoto?.addEventListener('click', (e) => {
            e.preventDefault();
            console.log("Abriendo selector de archivos...");
            
            inputAvatar?.click();
            
            if (typeof window.mostrarSeccion === 'function') {
                window.mostrarSeccion('perfil');
            }
        });
        
        // 3. Botón "Cancelar" en alerta-cambiar-foto -> Volver al perfil
        cancelarCambiarFoto?.addEventListener('click', (e) => {
            e.preventDefault();
            console.log("Cancelando cambio de foto");
            
            if (typeof window.mostrarSeccion === 'function') {
                window.mostrarSeccion('perfil');
            }
        });
        
        // 4. Cuando se selecciona un archivo -> Mostrar modal de confirmación
        inputAvatar?.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) {
                console.log("No se seleccionó archivo");
                return;
            }
            
            console.log("Archivo seleccionado:", file.name, file.type, file.size);
            
            // Validaciones
            if (!file.type.startsWith('image/')) {
                mostrarError('Solo se permiten archivos de imagen (JPG, PNG, GIF)');
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                mostrarError('La imagen no debe superar los 2MB');
                return;
            }
            
            // Guardar archivo seleccionado
            archivoAvatarSeleccionado = file;
            
            // Mostrar modal de confirmación
            mostrarModalConfirmacionAvatar();
        });
        
        // 5. Modal confirmación - Botón Confirmar
        confirmBtn?.addEventListener('click', async (e) => {
            e.preventDefault();
            console.log("Confirmando subida de avatar...");
            
            if (archivoAvatarSeleccionado) {
                await subirAvatar(archivoAvatarSeleccionado);
                ocultarModalConfirmacionAvatar();
            } else {
                mostrarError('No hay archivo seleccionado');
            }
        });
        
        // 6. Modal confirmación - Botón Cancelar
        cancelBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            console.log("Cancelando desde modal");
            ocultarModalConfirmacionAvatar();
            
            if (inputAvatar) {
                inputAvatar.value = '';
            }
            archivoAvatarSeleccionado = null;
        });
        
        console.log("Flujo de avatar inicializado");
    }

    // --- FUNCIÓN PRINCIPAL PARA SUBIR AVATAR ---
    async function subirAvatar(file) {
        console.log("Iniciando subida de avatar...");
        
        const formData = new FormData();
        formData.append('avatar', file);
        
        try {
            const url = `${apiBase}/upload_avatar.php`;
            console.log("Enviando a:", url);
            
            const res = await fetch(url, {
                method: 'POST',
                body: formData
            });

            console.log("Respuesta recibida, status:", res.status);
            
            const responseText = await res.text();
            console.log("Respuesta completa:", responseText);
            
            let json;
            try {
                json = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Error parseando JSON:', parseError);
                console.error('Contenido recibido:', responseText);
                
                if (responseText.includes('<br />') || responseText.includes('<b>')) {
                    throw new Error('Error del servidor PHP. Revisa la configuración.');
                }
                throw new Error('Respuesta inválida del servidor');
            }

            console.log("JSON parseado:", json);

            if (json.ok) {
                console.log("Avatar subido exitosamente:", json.avatar_url);
                
                // Actualizar avatar en la página de perfil
                const avatarDiv = document.getElementById('avatarPerfil') || document.querySelector('.foto-perfil');
                if (avatarDiv && json.avatar_url) {
                    const timestamp = new Date().getTime();
                    const avatarUrl = `${json.avatar_url}?t=${timestamp}`;
                    
                    avatarDiv.innerHTML = `<img src="${avatarUrl}" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                    console.log("Avatar actualizado en UI del perfil");
                }

                // Actualizar CURRENT_USER global y header
                if (window.CURRENT_USER) {
                    window.CURRENT_USER.avatar_url = json.avatar_url;
                    console.log("Avatar actualizado en CURRENT_USER");
                }

                actualizarAvatarEnHeader(json.avatar_url);
                
                // ALERTA con callback para limpiar
                mostrarExito('Avatar actualizado correctamente', () => {
                    console.log("Alerta cerrada, limpiando datos...");
                    archivoAvatarSeleccionado = null;
                    const inputAvatar = document.getElementById('inputAvatar');
                    if (inputAvatar) inputAvatar.value = '';
                });
                
            } else {
                console.error("Error del servidor:", json.message);
                mostrarError(json.message || 'Error al subir el avatar');
            }
            
        } catch (error) {
            console.error('Error subiendo avatar:', error);
            mostrarError(error.message || 'Error de conexión. Intenta nuevamente.');
        }
    }

    // --- FUNCIÓN PARA ACTUALIZAR AVATAR EN HEADER ---
    function actualizarAvatarEnHeader(avatarUrl) {
        console.log("Actualizando avatar en header...");
        
        const profileCircle = document.querySelector('.profile-circle');
        if (!profileCircle) {
            console.log("No se encontró .profile-circle en el header");
            return;
        }
        
        if (avatarUrl) {
            const timestamp = new Date().getTime();
            const urlConTimestamp = `${avatarUrl}?t=${timestamp}`;
            
            profileCircle.innerHTML = `
                <img src="${urlConTimestamp}" 
                     alt="Avatar" 
                     style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
            `;
            console.log("Avatar actualizado en header:", urlConTimestamp);
        } else {
            const userName = window.CURRENT_USER?.name || 'U';
            const initial = userName.charAt(0).toUpperCase();
            profileCircle.textContent = initial;
            console.log("Inicial mostrada en header:", initial);
        }
    }

    // --- MODAL DE CONFIRMACIÓN AVATAR ---
    function mostrarModalConfirmacionAvatar() {
        const modal = document.getElementById('confirmationModal');
        if (modal) {
            modal.style.display = 'flex';
            console.log("Modal de confirmación mostrado");
        }
    }

    function ocultarModalConfirmacionAvatar() {
        const modal = document.getElementById('confirmationModal');
        if (modal) {
            modal.style.display = 'none';
            console.log("Modal de confirmación oculto");
        }
    }

    // --- FUNCIONES API ---
    async function actualizarPerfil(datos) {
        try {
            console.log("Actualizando perfil con datos:", datos);
            const url = `${apiBase}/update_profile.php`;
            
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });

            const json = await res.json();
            console.log("Respuesta del servidor:", json);

            if (json.ok) {
                // ACTUALIZAR CON DATOS COMPLETOS DEL SERVIDOR
                if (json.user) {
                    window.CURRENT_USER.name = json.user.name;
                    window.CURRENT_USER.email = json.user.email;
                    window.CURRENT_USER.last_login = json.user.last_login;
                    window.CURRENT_USER.assigned_count = json.user.assigned_count;
                    
                    actualizarNombreEnUI(json.user.name);
                    actualizarCorreoEnUI(json.user.email);
                    console.log("Datos completos actualizados en CURRENT_USER");
                } else {
                    // Fallback por si no viene user completo
                    if (datos.name) {
                        window.CURRENT_USER.name = datos.name;
                        actualizarNombreEnUI(datos.name);
                    }
                    if (datos.email) {
                        window.CURRENT_USER.email = datos.email;
                        actualizarCorreoEnUI(datos.email);
                    }
                }
                
                cargarDatosPerfil();
                
                // DISPARAR EVENTO con datos completos
                console.log("===== DISPARANDO EVENTO 'perfilActualizado' =====");
                const eventoActualizacion = new CustomEvent('perfilActualizado', {
                    detail: { 
                        nombre: datos.name, 
                        email: datos.email,
                        timestamp: new Date().getTime(),
                        user: json.user  
                    }
                });
                window.dispatchEvent(eventoActualizacion);
                console.log("Evento 'perfilActualizado' disparado con datos completos");
                
                mostrarExitoYRegresar(json.message || 'Perfil actualizado correctamente');
            } else {
                const seccionActual = datos.name ? 'editar-nombre' : 'editar-correo';
                mostrarError(json.message || 'Error al actualizar el perfil', seccionActual);
            }
        } catch (error) {
            console.error('Error actualizando perfil:', error);
            const seccionActual = datos.name ? 'editar-nombre' : 'editar-correo';
            mostrarError('Error de conexión. Intenta nuevamente.', seccionActual);
        }
    }

    async function cambiarPassword(datos) {
        try {
            const url = `${apiBase}/change_password.php`;
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });

            const json = await res.json();

            if (json.ok) {
                mostrarExitoYRegresar(json.message || 'Contraseña actualizada correctamente');
            } else {
                mostrarError(json.message || 'Error al cambiar la contraseña', 'editar-contrasena');
            }
        } catch (error) {
            console.error('Error cambiando contraseña:', error);
            mostrarError('Error de conexión. Intenta nuevamente.', 'editar-contrasena');
        }
    }

    // --- ACTUALIZAR UI ---
    function actualizarNombreEnUI(nuevoNombre) {
        const headerName = document.querySelector('.user-info h3');
        const profileCircle = document.querySelector('.profile-circle');
        
        if (headerName) {
            headerName.textContent = nuevoNombre;
        }
        
        if (profileCircle && !window.CURRENT_USER.avatar_url) {
            const initial = nuevoNombre.charAt(0).toUpperCase();
            profileCircle.textContent = initial;
        }
        
        const nombreInput = document.querySelector('#perfil .nombre-usuario');
        if (nombreInput) {
            nombreInput.value = nuevoNombre;
        }
        
        console.log("Nombre actualizado en toda la UI");
    }

    function actualizarCorreoEnUI(nuevoCorreo) {
        const correoInput = document.querySelector('#perfil .correo-usuario');
        if (correoInput) {
            correoInput.value = nuevoCorreo;
        }
        
        console.log("Correo actualizado en UI");
    }

    // --- ALERTAS CORREGIDAS ---
    function mostrarExitoYRegresar(mensaje) {
        if (typeof window.configurarAlerta === 'function') {
            window.configurarAlerta(
                "Éxito", 
                mensaje, 
                "exito", 
                {
                    soloAceptar: true,
                    onConfirmar: () => {
                        console.log("Alerta de éxito confirmada - regresando al perfil");
                        if (typeof window.mostrarSeccion === 'function') {
                            window.mostrarSeccion('perfil');
                        }
                    }
                }
            );
        } else {
            alert(mensaje);
            if (typeof window.mostrarSeccion === 'function') {
                window.mostrarSeccion('perfil');
            }
        }
    }

    function mostrarExito(mensaje, callback = null) {
        if (typeof window.configurarAlerta === 'function') {
            window.configurarAlerta(
                "Éxito", 
                mensaje, 
                "exito", 
                { 
                    soloAceptar: true,
                    onConfirmar: () => {
                        console.log("Alerta de éxito confirmada - ejecutando callback");
                        if (callback && typeof callback === 'function') {
                            callback();
                        }
                    }
                }
            );
        } else {
            alert(mensaje);
            if (callback && typeof callback === 'function') {
                callback();
            }
        }
    }

    function mostrarError(mensaje, seccionVolver = 'perfil') {
        if (typeof window.configurarAlerta === 'function') {
            window.configurarAlerta(
                "Error", 
                mensaje, 
                "alerta", 
                { 
                    soloAceptar: true,
                    onConfirmar: () => {
                        console.log("Alerta de error confirmada, volviendo a:", seccionVolver);
                        if (typeof window.mostrarSeccion === 'function') {
                            window.mostrarSeccion(seccionVolver);
                        }
                    }
                }
            );
        } else {
            alert(mensaje);
            if (typeof window.mostrarSeccion === 'function') {
                window.mostrarSeccion(seccionVolver);
            }
        }
    }

    // --- BOTONES CANCELAR ---
    function inicializarBotonesCancelar() {
        console.log("Inicializando botones cancelar...");
        
        const btnCancelarNombre = document.querySelector('#editar-nombre .btn-cancelar-edicion');
        const btnCancelarCorreo = document.querySelector('#editar-correo .btn-cancelar-edicion');
        const btnCancelarPassword = document.querySelector('#editar-contrasena .btn-cancelar-edicion');
        
        // console.log("Botones cancelar encontrados:", {
        //     nombre: btnCancelarNombre ? '' : '',
        //     correo: btnCancelarCorreo ? '' : '',
        //     password: btnCancelarPassword ? '' : ''
        // });
        
        btnCancelarNombre?.addEventListener('click', (e) => {
            e.preventDefault();
            console.log("Cancelando edición de nombre");
            if (typeof window.mostrarSeccion === 'function') {
                window.mostrarSeccion('perfil');
            }
        });
        
        btnCancelarCorreo?.addEventListener('click', (e) => {
            e.preventDefault();
            console.log("Cancelando edición de correo");
            if (typeof window.mostrarSeccion === 'function') {
                window.mostrarSeccion('perfil');
            }
        });
        
        btnCancelarPassword?.addEventListener('click', (e) => {
            e.preventDefault();
            console.log("Cancelando edición de contraseña");
            if (typeof window.mostrarSeccion === 'function') {
                window.mostrarSeccion('perfil');
            }
        });
        
        console.log("Botones cancelar inicializados");
    }

    // --- ELIMINAR CUENTA ---
    btnEliminarCuenta?.addEventListener('click', () => {
        if (typeof window.configurarAlerta === 'function') {
            window.configurarAlerta(
                "Eliminar Cuenta",
                "¿Estás seguro de que deseas eliminar tu cuenta?<br><strong>Esta acción no se puede deshacer.</strong>",
                "alerta",
                {
                    textoConfirmar: "Eliminar",
                    onConfirmar: async () => {
                        const password = prompt("Ingresa tu contraseña para confirmar:");
                        if (password) {
                            await eliminarCuenta(password);
                        }
                    },
                    onCancelar: () => {}
                }
            );
        }
    });

    async function eliminarCuenta(password) {
        try {
            const url = `${apiBase}/delete_account.php`;
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password })
            });

            const json = await res.json();

            if (json.ok) {
                if (typeof window.configurarAlerta === 'function') {
                    window.configurarAlerta(
                        "Cuenta Eliminada",
                        "Tu cuenta ha sido eliminada. Serás redirigido al login.",
                        "exito",
                        {
                            soloAceptar: true,
                            onConfirmar: () => {
                                window.location.href = '../../view/login.html';
                            }
                        }
                    );
                } else {
                    window.location.href = '../../view/login.html';
                }
            } else {
                mostrarError(json.message || 'Error al eliminar la cuenta');
            }
        } catch (error) {
            console.error('Error eliminando cuenta:', error);
            mostrarError('Error de conexión. Intenta nuevamente.');
        }
    }

    btnEliminarCuenta?.addEventListener('click', () => {
        if (typeof window.configurarAlerta === 'function') {
            window.configurarAlerta(
                "Eliminar Cuenta",
                "¿Estás seguro de que deseas eliminar tu cuenta?<br><strong>Esta acción no se puede deshacer.</strong>",
                "alerta",
                {
                    textoConfirmar: "Eliminar",
                    onConfirmar: async () => {
                        mostrarModalContrasenaEliminar();
                    },
                    onCancelar: () => {}
                }
            );
        }
    });

    // AGREGAR FUNCIÓN PARA MODAL DE CONTRASEÑA
    function mostrarModalContrasenaEliminar() {
        const modalHTML = `
            <div id="modalContrasena" style="
                position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background: rgba(0,0,0,0.7); display: flex; justify-content: center; 
                align-items: center; z-index: 10000; font-family: 'Poppins', sans-serif;
            ">
                <div style="
                    background: white; padding: 30px; border-radius: 12px; 
                    width: 600px; text-align: center; border: 4px solid #1b5cff;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
                ">
                    <h3 style="
                        color: #1b5cff; margin-bottom: 10px; font-size: 32px; 
                        font-weight: bold; border-bottom: 3px solid #1b5cff;
                        padding-bottom: 10px;
                    ">
                        Confirmar Eliminación
                    </h3>
                    
                    <img src="../../assets/img/alerta.png" alt="Alerta" style="
                        width: 150px; height: 150px; margin: 15px 0;
                    ">
                    
                    <p style="
                        color: #333; font-size: 20px; margin: 10px 0 25px 0;
                        line-height: 1.5;
                    ">
                        Ingresa tu contraseña para confirmar la eliminación permanente de tu cuenta.
                    </p>
                    
                    <input type="password" id="inputContrasenaEliminar" 
                        placeholder="Ingresa tu contraseña actual" 
                        style="
                            width: 100%; padding: 12px 15px; margin: 15px 0; 
                            border: 2px solid #1b5cff; border-radius: 5px; 
                            font-size: 16px; background: #1b5cff; color: white;
                            outline: none; transition: border-color 0.3s;
                            font-family: 'Poppins', sans-serif;
                        "
                        autocomplete="current-password"
                    >
                    
                    <div style="
                        margin-top: 25px; display: flex; justify-content: center; 
                        gap: 40px;
                    ">
                        <button id="cancelarContrasena" style="
                            border: none; border-radius: 6px; padding: 10px 60px;
                            font-weight: bold; cursor: pointer; font-size: 16px;
                            transition: 0.3s; background: #a1b3ff; color: #000;
                        ">Cancelar</button>
                        <button id="confirmarContrasena" style="
                            border: none; border-radius: 6px; padding: 10px 60px;
                            font-weight: bold; cursor: pointer; font-size: 16px;
                            transition: 0.3s; background: #ff0f0f; color: white;
                        ">Eliminar Cuenta</button>
                    </div>
                </div>
            </div>
        `;
        
        const modalExistente = document.getElementById('modalContrasena');
        if (modalExistente) {
            modalExistente.remove();
        }
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        const modal = document.getElementById('modalContrasena');
        const inputContrasena = document.getElementById('inputContrasenaEliminar');
        const btnConfirmar = document.getElementById('confirmarContrasena');
        const btnCancelar = document.getElementById('cancelarContrasena');
        
        btnCancelar.addEventListener('mouseenter', () => {
            btnCancelar.style.background = '#7f97ff';
        });
        btnCancelar.addEventListener('mouseleave', () => {
            btnCancelar.style.background = '#a1b3ff';
        });
        
        btnConfirmar.addEventListener('mouseenter', () => {
            btnConfirmar.style.background = '#cc0c0c';
        });
        btnConfirmar.addEventListener('mouseleave', () => {
            btnConfirmar.style.background = '#ff0f0f';
        });
        
        inputContrasena.addEventListener('focus', () => {
            inputContrasena.style.borderColor = '#003bcc';
            inputContrasena.style.boxShadow = '0 0 5px rgba(27, 92, 255, 0.5)';
        });
        inputContrasena.addEventListener('blur', () => {
            inputContrasena.style.borderColor = '#1b5cff';
            inputContrasena.style.boxShadow = 'none';
        });
        
        const eliminarModal = () => {
            if (modal && modal.parentNode) {
                modal.remove();
            }
        };
        
        btnConfirmar.addEventListener('click', async () => {
            const password = inputContrasena.value.trim();
            if (password) {
                console.log("Enviando contraseña para eliminar cuenta...");
                eliminarModal();
                await eliminarCuenta(password);
            } else {
                inputContrasena.style.borderColor = '#dc3545';
                inputContrasena.style.background = '#ffebee';
                inputContrasena.style.color = '#dc3545';
                inputContrasena.placeholder = '¡La contraseña es requerida!';
                
                setTimeout(() => {
                    inputContrasena.style.borderColor = '#1b5cff';
                    inputContrasena.style.background = '#1b5cff';
                    inputContrasena.style.color = 'white';
                    inputContrasena.placeholder = 'Ingresa tu contraseña actual';
                }, 2000);
                inputContrasena.focus();
            }
        });
        
        btnCancelar.addEventListener('click', eliminarModal);
        
        setTimeout(() => {
            inputContrasena.focus();
        }, 100);
    }

    function formatActivityDate(dateString) {
        if (!dateString) return '-';
        
        try {
            // Para La Paz, BCS (UTC-7/-6)
            const date = new Date(dateString + 'T12:00:00-07:00');
            
            if (isNaN(date.getTime())) {
                return dateString;
            }
            
            return date.toLocaleDateString('es-MX', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                timeZone: 'America/Mazatlan'
            });
        } catch (error) {
            console.error('Error formateando fecha:', error, dateString);
            return dateString;
        }
    }

    // --- INICIALIZACIÓN COMPLETA ---
    function inicializarProfileCompleto() {
        console.log("Inicializando profile.js completo...");
        
        cargarDatosPerfil();
        
        // Cargar actividad inicial
        setTimeout(() => {
            cargarActividadUsuario(true);
        }, 500);
        
        inicializarFlujoAvatar();
        inicializarBotonesCancelar();
        
        // SOLO iniciar actualización automática 
        setTimeout(() => {
            iniciarActualizacionAutomatica();
        }, 1000);
        
        console.log("Profile.js completamente inicializado con auto-actualización");
    }

    // ESCUCHAR EVENTO DE ACTUALIZACIÓN DESDE users.js
    window.addEventListener('usuarioActualizado', (event) => {
        console.log("====== EVENTO RECIBIDO ======");
        console.log("'usuarioActualizado' detectado en profile.js");
        console.log("Datos del evento:", event.detail);
        
        if (event.detail.nombre) {
            console.log("Actualizando nombre:", window.CURRENT_USER.name, "->", event.detail.nombre);
            window.CURRENT_USER.name = event.detail.nombre;
        }
        if (event.detail.email) {
            console.log("Actualizando email:", window.CURRENT_USER.email, "->", event.detail.email);
            window.CURRENT_USER.email = event.detail.email;
        }
        
        console.log("Recargando datos del perfil...");
        cargarDatosPerfil();
        console.log("====== FIN EVENTO ======");
    });

    // ESCUCHAR EVENTO DE TAREA CREADA
    window.addEventListener('tareaCreada', (event) => {
        console.log("====== EVENTO TAREA CREADA ======");
        console.log("Nueva tarea creada, recargando actividad...");
        console.log("Detalles:", event.detail);
        
        // Recargar actividad inmediatamente
        cargarActividadUsuario(true);
        
        console.log("====== FIN EVENTO TAREA CREADA ======");
    });

    console.log("Listener 'usuarioActualizado' registrado en profile.js");
    console.log("Listener 'tareaCreada' registrado en profile.js");

    // Actualizar automáticamente cada 30 segundos
    function iniciarActualizacionAutomatica() {
        console.log("Iniciando actualización automática cada 30 segundos");
        setInterval(() => {
            console.log("Actualización automática de actividad...");
            cargarActividadUsuario(true); 
        }, 30000); 
    }

    // --- UTILIDADES ---
    function validarEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        if (isNaN(date)) return dateString;
        return date.toLocaleDateString('es-MX');
    }

    // Función específica para manejar tareas asignadas desde Admin
    async function manejarTareasAsignadasAdmin(event) {
        console.log('PROFILE: Tareas asignadas desde Admin', event.detail);
        
        // Solo actualizar si afecta al usuario actual
        if (event.detail.userId === window.CURRENT_USER?.id) {
            console.log('Afecta al usuario actual, recargando actividad...');
            await cargarActividadUsuario(true);
            console.log('Actividad del perfil actualizada');
        }
    }

    // === ESCUCHAR EVENTOS DE ACTUALIZACIÓN DESDE TABLEROS ===
    window.addEventListener('actividadTareaActualizada', async (event) => {
        console.log("EVENTO RECIBIDO: Actividad de tarea actualizada", event.detail);
        
        // Recargar actividad inmediatamente
        await cargarActividadUsuario(true);
        
        console.log("Actividad actualizada después de movimiento en Tableros");
    });

    // === ESCUCHAR EVENTOS DE TAREAS ===
    window.addEventListener('tareaCreada', async (event) => {
        console.log("EVENTO RECIBIDO: Tarea creada", event.detail);
        await cargarActividadUsuario(true);
    });

    window.addEventListener('tareasEliminadas', async (event) => {
        console.log("EVENTO RECIBIDO: Tareas eliminadas", event.detail);
        await cargarActividadUsuario(true);
    });

    // === LISTENERS PARA AUTO-REFRESH ===
    window.addEventListener('tareaCreadaDesdeTareas', async (event) => {
        console.log('PROFILE: Tarea creada desde Tareas', event.detail);
        await cargarActividadUsuario(true);
        console.log('Actividad del perfil actualizada');
    });

    window.addEventListener('tareaCreadadesdeTableros', async (event) => {
        console.log('PROFILE: Tarea creada desde Tableros', event.detail);
        await cargarActividadUsuario(true);
        console.log('Actividad del perfil actualizada');
    });

    window.addEventListener('tareaEliminadaDesdeTareas', async (event) => {
        console.log('PROFILE: Tarea eliminada desde Tareas', event.detail);
        await cargarActividadUsuario(true);
        console.log('Actividad del perfil actualizada');
    });

    window.addEventListener('tareaEliminadaDesdeTableros', async (event) => {
        console.log('PROFILE: Tarea eliminada desde Tableros', event.detail);
        await cargarActividadUsuario(true);
        console.log('Actividad del perfil actualizada');
    });

    window.addEventListener('tareaActualizadaDesdeTareas', async (event) => {
        console.log('PROFILE: Tarea actualizada desde Tareas', event.detail);
        await cargarActividadUsuario(true);
        console.log('Actividad del perfil actualizada');
    });

    window.addEventListener('tareaActualizadaDesdeTableros', async (event) => {
        console.log('PROFILE: Tarea actualizada desde Tableros', event.detail);
        await cargarActividadUsuario(true);
        console.log('Actividad del perfil actualizada');
    });

    // === LISTENERS PARA AUTO-REFRESH DESDE ADMIN ===
    window.addEventListener('tareasAsignadasDesdeAdmin', manejarTareasAsignadasAdmin);

    window.addEventListener('usuarioActualizadoDesdeAdmin', async (event) => {
        console.log('PROFILE: Usuario actualizado desde Admin', event.detail);
        
        // Solo actualizar si es el usuario actual
        if (event.detail.userId === window.CURRENT_USER?.id) {
            await cargarActividadUsuario(true);
            console.log('Actividad del perfil actualizada');
        }
    });

    console.log("Profile.js inicializado con listeners para eventos de actividad");
});