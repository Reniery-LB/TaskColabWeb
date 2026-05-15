// assets/javascript/boards.js
document.addEventListener('DOMContentLoaded', function() {
    console.log("Inicializando boards.js...");

    // === ELEMENTOS DEL DOM ===
    const columnas = {
        pending: document.querySelector('.columna:nth-child(1) .tarjetas'),
        in_progress: document.querySelector('.columna:nth-child(2) .tarjetas'),
        done: document.querySelector('.columna:nth-child(3) .tarjetas')
    };

    const botonesAdd = document.querySelectorAll('.add-card');
    const formTarjeta = document.getElementById('form-tarjeta');
    const btnCancelarTarjeta = document.querySelector('#formulario-tarjeta .cancelar');
    const tituloFormTarjeta = document.getElementById('titulo-form-tarjeta');

    // Inputs del formulario
    const inputTitulo = document.getElementById('titulo-tarjeta');
    const inputDescripcion = document.getElementById('descripcion-tarjeta');
    const selectUsuario = document.getElementById('asignar-tarjeta');
    const selectPrioridad = document.getElementById('prioridad-tarjeta');
    const inputFecha = document.getElementById('fecha-tarjeta');

    // Variable para saber en qué columna se está creando
    let columnaActual = 'pending';

    // API base para tableros
    const apiBase = window.API_BASE_TABLEROS || '/assets/app/endpointsTableros';
    
    // === Click directo en el botón submit ===
    // const btnCrearTarjeta = document.querySelector('#form-tarjeta button[type="submit"]');
    // if (btnCrearTarjeta) {
    //     console.log("Botón submit encontrado");
        
    //     btnCrearTarjeta.addEventListener('click', (e) => {
    //         console.log("Click directo en botón submit");
    //         console.log("   inputTitulo.value en click:", inputTitulo?.value);
            
    //         const form = document.getElementById('form-tarjeta');
    //         console.log("   Formulario válido:", form?.checkValidity());
    //     });
    // }

    // === Ver qué elementos encuentra boards.js (DESPUÉS DE DECLARAR) ===
    // console.log("DEBUG boards.js:");
    // console.log("   - formTarjeta:", formTarjeta ? "Encontrado" : "NO encontrado");
    // console.log("   - inputTitulo:", inputTitulo ? "Encontrado" : "NO encontrado");
    // console.log("   - inputDescripcion:", inputDescripcion ? "Encontrado" : "NO encontrado");
    // console.log("   - selectUsuario:", selectUsuario ? "Encontrado" : "NO encontrado");
    // console.log("   - selectPrioridad:", selectPrioridad ? "Encontrado" : "NO encontrado");
    // console.log("   - inputFecha:", inputFecha ? "Encontrado" : "NO encontrado");
    // console.log("   - botonesAdd:", botonesAdd.length, "botones encontrados");
    // console.log("   - API_BASE Tableros:", apiBase);

    // === Verificar que los inputs funcionan ===
    if (inputTitulo) {
        console.log("TEST inputTitulo encontrado");
        
        // Agregar listener para ver cuando cambias el valor
        inputTitulo.addEventListener('input', (e) => {
            console.log("inputTitulo valor cambiado:", e.target.value);
        });
        
        inputTitulo.addEventListener('blur', (e) => {
            console.log("inputTitulo pierde foco, valor:", e.target.value);
        });
    }

    // === Verificar que no hay inputs duplicados ===
    const todosLosTitulos = document.querySelectorAll('#titulo-tarjeta');
    console.log("Inputs con id='titulo-tarjeta' encontrados:", todosLosTitulos.length);
    if (todosLosTitulos.length > 1) {
        console.error("ERROR: Hay inputs duplicados!");
        todosLosTitulos.forEach((input, index) => {
            console.error(`   Input ${index + 1}:`, input);
        });
    }

    // === MAPEO DE ESTADOS ===
    const statusMap = {
        'Pendiente': 'pending',
        'En proceso': 'in_progress',
        'Completado': 'done',
        'pending': 'Pendiente',
        'in_progress': 'En proceso',
        'done': 'Completado'
    };

    const priorityMap = {
        'Alta prioridad': 'high',
        'Media prioridad': 'medium',
        'Baja prioridad': 'low',
        'high': 'Alta prioridad',
        'medium': 'Media prioridad',
        'low': 'Baja prioridad'
    };

    // === INICIALIZACIÓN ===
    loadBoard();
    loadUsersForAssignment();

    // === CARGAR TABLERO ===
    async function loadBoard() {
        try {
            console.log('Cargando tablero...');
            const timestamp = new Date().getTime();
            const url = `${apiBase}/get_board_tasks.php?t=${timestamp}`;

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
            console.log('Tablero recibido:', json);

            if (json.ok && json.board) {
                renderBoard(json.board);
                console.log(`Tablero cargado: ${json.total_tasks} tareas`);
            } else {
                console.warn('No hay tareas en el tablero');
                limpiarTablero();
            }
        } catch (error) {
            console.error('Error cargando tablero:', error);
            limpiarTablero();
        }
    }

    // === RENDERIZAR TABLERO ===
    function renderBoard(board) {
        console.log('Renderizando tablero...');

        // Limpiar columnas
        Object.values(columnas).forEach(col => {
            if (col) col.innerHTML = '';
        });

        // Renderizar cada columna
        for (const [columnKey, tasks] of Object.entries(board)) {
            const columna = columnas[columnKey];
            if (!columna) {
                console.warn(`Columna ${columnKey} no encontrada`);
                continue;
            }

            if (tasks.length === 0) {
                columna.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Sin tareas</p>';
                continue;
            }

            tasks.forEach(task => {
                const card = createCard(task, columnKey);
                columna.appendChild(card);
            });

            console.log(`Columna ${columnKey}: ${tasks.length} tareas`);
        }
    }

    // === CREAR TARJETA ===
    function createCard(task, column) {
        const card = document.createElement('div');
        card.className = 'tarjeta';
        card.dataset.taskId = task.id;
        card.dataset.column = column;

        // Formatear fecha
        const fecha = task.due_date ? formatDate(task.due_date) : 'Sin fecha';

        // Usuarios asignados
        const usuarios = task.assigned_users || 'Sin asignar';

        // Prioridad
        const prioridad = task.priority || 'Media';

        // Determinar qué botones mostrar según la columna
        const mostrarIzq = column !== 'pending';
        const mostrarDer = column !== 'done';

        card.innerHTML = `
            <h4>${escapeHtml(task.title)}</h4>
            <p><img src="../../assets/img/icono-calendario.png" class="icono"> ${fecha}</p>
            <p><img src="../../assets/img/icono-usuario.png" class="icono"> ${escapeHtml(usuarios)}</p>
            <p><img src="../../assets/img/icono-prioridad.png" class="icono"> ${escapeHtml(prioridad)}</p>
            <div class="botones-tarjeta">
                <button class="eliminar" data-task-id="${task.id}">
                    <img src="../../assets/img/basura.png" class="delete-card" alt="Eliminar">
                </button>
                <div class="botones-derecha">
                    ${mostrarIzq ? `<button class="mover-izq" data-task-id="${task.id}" data-direction="left"><</button>` : ''}
                    ${mostrarDer ? `<button class="mover-der" data-task-id="${task.id}" data-direction="right">></button>` : ''}
                </div>
            </div>
        `;

        return card;
    }

    // === LIMPIAR TABLERO ===
    function limpiarTablero() {
        Object.values(columnas).forEach(col => {
            if (col) {
                col.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Sin tareas</p>';
            }
        });
    }

    // === BOTONES "+" PARA AÑADIR TARJETA ===
    botonesAdd.forEach(boton => {
        boton.addEventListener('click', () => {
            const seccion = boton.dataset.seccion;

            // Mapear sección a columna
            const columnaMap = {
                'pendiente': 'pending',
                'proceso': 'in_progress',
                'completado': 'done'
            };

            columnaActual = columnaMap[seccion] || 'pending';

            // Actualizar título del formulario
            const tituloMap = {
                'pending': 'Pendiente',
                'in_progress': 'En proceso',
                'done': 'Completado'
            };

            if (tituloFormTarjeta) {
                tituloFormTarjeta.textContent = `Añadir Tarjeta - ${tituloMap[columnaActual]}`;
            }

            // Mostrar formulario
            if (typeof window.mostrarSeccion === 'function') {
                window.mostrarSeccion('formulario-tarjeta');
            }
        });
    });

    // === CANCELAR FORMULARIO ===
    btnCancelarTarjeta?.addEventListener('click', (e) => {
        e.preventDefault(); 
        e.stopPropagation(); 
        
        console.log("Formulario cancelado");
        
        // Limpiar formulario SOLO al cancelar
        if (formTarjeta) {
            formTarjeta.reset();
        }
        
        // Volver a tableros
        if (typeof window.mostrarSeccion === 'function') {
            window.mostrarSeccion('tableros');
        }
    });

    // === ENVIAR FORMULARIO - CREAR TARJETA ===
    formTarjeta?.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        console.log("===== FORMULARIO DE TARJETA ENVIADO =====");
        
        // CAPTURAR VALORES INMEDIATAMENTE del evento
        const form = e.target;
        const formData = new FormData(form);
        
        // Leer desde FormData 
        const titulo = formData.get('titulo-tarjeta')?.trim() || '';
        const descripcion = formData.get('descripcion-tarjeta')?.trim() || '';
        const usuarioId = formData.get('asignar-tarjeta') || '';
        const prioridad = formData.get('prioridad-tarjeta') || 'Media prioridad';
        const fecha = formData.get('fecha-tarjeta') || '';
        
        const tituloBackup = inputTitulo?.value?.trim() || '';
        const descripcionBackup = inputDescripcion?.value?.trim() || '';
        
        console.log("Datos capturados desde FormData:");
        console.log("   - titulo:", titulo);
        console.log("   - descripcion:", descripcion);
        console.log("   - usuarioId:", usuarioId);
        console.log("   - prioridad:", prioridad);
        console.log("   - fecha:", fecha);
        console.log("   - columnaActual:", columnaActual);
        
        console.log("Datos backup desde variables:");
        console.log("   - tituloBackup:", tituloBackup);
        console.log("   - descripcionBackup:", descripcionBackup);
        
        // Usar el que tenga valor
        const tituloFinal = titulo || tituloBackup;
        const descripcionFinal = descripcion || descripcionBackup;
        
        console.log("Datos finales a usar:");
        console.log("   - tituloFinal:", tituloFinal);
        console.log("   - descripcionFinal:", descripcionFinal);
        
        // VALIDAR TÍTULO
        if (!tituloFinal || tituloFinal === '') {
            console.error("Validación falló: Título vacío");
            console.error("   FormData titulo:", titulo);
            console.error("   Variable titulo:", tituloBackup);
            mostrarError('Por favor, ingresa un título para la tarjeta');
            return;
        }
        
        console.log("Validación pasada, creando tarea...");
        
        // CREAR TAREA
        await crearTarea({
            titulo: tituloFinal,
            descripcion: descripcionFinal,
            usuarioId: usuarioId,
            prioridad: prioridad,
            fecha: fecha,
            columna: columnaActual
        });
    });

    // === CREAR TAREA ===
    async function crearTarea(datos) {
        try {
            console.log('Enviando tarea:', datos);

            // Convertir prioridad al formato correcto
            const priorityMap = {
                'Alta prioridad': 'Alta',
                'Media prioridad': 'Media',
                'Baja prioridad': 'Baja',
                'Alta': 'Alta',
                'Media': 'Media',
                'Baja': 'Baja'
            };

            const payload = {
                title: datos.titulo,
                description: datos.descripcion || '',
                column: datos.columna, // pending, in_progress, done
                priority: priorityMap[datos.prioridad] || 'Media', 
                due_date: datos.fecha || null,
                assigned_to: datos.usuarioId ? parseInt(datos.usuarioId) : null,
                board_id: 1 // Por defecto, tablero principal
            };

            console.log('Payload final:', payload);

            const url = `${apiBase}/create_board_task.php`;
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const json = await res.json();
            console.log('Respuesta:', json);

            if (json.ok) {
                console.log('Tarea creada exitosamente');

                // Limpiar formulario
                formTarjeta?.reset();

                // Recargar tablero
                await loadBoard();

                // DISPARAR EVENTOS PARA ACTUALIZAR OTRAS SECCIONES
                console.log('Disparando eventos de actualización...');
                
                // Evento para Tareas (actualizar lista)
                const eventoTareas = new CustomEvent('tareaCreadaEnTableros', {
                    detail: {
                        taskId: json.task_id,
                        timestamp: new Date().getTime(),
                        action: 'create',
                        source: 'boards'
                    }
                });
                window.dispatchEvent(eventoTareas);
                console.log("Evento 'tareaCreadaEnTableros' disparado para tasks.js");
                
                // Evento para Perfil (actualizar actividad)
                const eventoPerfil = new CustomEvent('tareaCreada', {
                    detail: {
                        taskId: json.task_id,
                        timestamp: new Date().getTime()
                    }
                });
                window.dispatchEvent(eventoPerfil);
                console.log("Evento 'tareaCreada' disparado para profile.js");
                
                // Evento para Usuarios (actualizar conteos)
                const eventoUsuarios = new CustomEvent('actualizarConteoTareas', {
                    detail: {
                        timestamp: new Date().getTime(),
                        task_id: json.task_id
                    }
                });
                window.dispatchEvent(eventoUsuarios);
                console.log("Evento 'actualizarConteoTareas' disparado para users.js");

                // Mostrar éxito y volver a tableros
                mostrarExito('Tarjeta creada exitosamente', () => {
                    if (typeof window.mostrarSeccion === 'function') {
                        window.mostrarSeccion('tableros');
                    }
                });
            } else {
                throw new Error(json.message || 'Error al crear la tarea');
            }
        } catch (error) {
            console.error('Error creando tarea:', error);
            mostrarError(error.message || 'Error al crear la tarea');
        }
    }

    // === DELEGACIÓN DE EVENTOS - BOTONES DE TARJETAS ===
    document.querySelector('#tableros')?.addEventListener('click', async (e) => {
        // BOTÓN ELIMINAR
        const btnEliminar = e.target.closest('.eliminar');
        if (btnEliminar) {
            const taskId = btnEliminar.dataset.taskId;
            if (taskId) {
                confirmarEliminarTarea(parseInt(taskId));
            }
            return;
        }

        // BOTÓN MOVER IZQUIERDA
        const btnMoverIzq = e.target.closest('.mover-izq');
        if (btnMoverIzq) {
            const taskId = btnMoverIzq.dataset.taskId;
            if (taskId) {
                await moverTarea(parseInt(taskId), 'left');
            }
            return;
        }

        // BOTÓN MOVER DERECHA
        const btnMoverDer = e.target.closest('.mover-der');
        if (btnMoverDer) {
            const taskId = btnMoverDer.dataset.taskId;
            if (taskId) {
                await moverTarea(parseInt(taskId), 'right');
            }
            return;
        }
    });

    // === MOVER TAREA ===
    async function moverTarea(taskId, direction) {
        try {
            console.log(`Moviendo tarea ${taskId} hacia ${direction}`);

            const url = `${apiBase}/move_task.php`;
            const payload = {
                task_id: taskId,
                direction: direction // 'left' o 'right'
            };

            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const json = await res.json();
            console.log('Respuesta:', json);

            if (json.ok) {
                console.log(`Tarea movida de ${json.old_column} a ${json.new_column}`);

                // DISPARAR EVENTOS MEJORADOS
                dispararEventoActualizacion('movimiento', {
                    taskId: taskId,
                    oldColumn: json.old_column,
                    newColumn: json.new_column,
                    oldStatus: json.old_status,
                    newStatus: json.new_status,
                    direction: direction
                });

                await loadBoard();
            } else {
                throw new Error(json.message || 'Error al mover la tarea');
            }
        } catch (error) {
            console.error('Error moviendo tarea:', error);
            mostrarError('Error al mover la tarea');
        }
    }

    // === CONFIRMAR ELIMINAR TAREA ===
    function confirmarEliminarTarea(taskId) {
        if (typeof window.configurarAlerta === 'function') {
            window.configurarAlerta(
                "Eliminar Tarjeta",
                "¿Estás seguro de que deseas eliminar esta tarjeta?<br><strong>Esta acción no se puede deshacer.</strong>",
                "alerta",
                {
                    textoConfirmar: "Eliminar",
                    onConfirmar: async () => {
                        await eliminarTarea(taskId);
                    }
                }
            );
        } else {
            if (confirm('¿Eliminar esta tarjeta?')) {
                eliminarTarea(taskId);
            }
        }
    }

    // === ELIMINAR TAREA ===
    async function eliminarTarea(taskId) {
        try {
            console.log(`Eliminando tarea ${taskId}`);

            const url = `${apiBase}/delete_board_task.php`;
            const payload = { task_id: taskId };

            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const json = await res.json();
            console.log('Respuesta:', json);

            if (json.ok) {
                console.log('Tarea eliminada exitosamente');

                await loadBoard();

                // Usar mostrarExito con callback
                mostrarExito('Tarjeta eliminada correctamente', () => {
                    console.log("Callback ejecutado después de eliminar tarea");
                });

                // Disparar evento para actualizar contadores
                dispararEventoActualizacion('eliminacion', {
                    taskId: taskId,
                    action: 'delete'
                });
            } else {
                throw new Error(json.message || 'Error al eliminar la tarea');
            }
        } catch (error) {
            console.error('Error eliminando tarea:', error);
            mostrarError('Error al eliminar la tarea');
        }
    }

    // === CARGAR USUARIOS PARA ASIGNAR ===
    async function loadUsersForAssignment() {
        if (!selectUsuario) return;

        try {
            const apiUsuarios = window.API_BASE || '/assets/app/endpoints';
            const res = await fetch(`${apiUsuarios}/list_users.php`);

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const json = await res.json();
            console.log('Usuarios para asignar:', json);

            if (json.ok && json.users) {
                const usuariosActivos = json.users.filter(user =>
                    user.is_active == 1 || user.is_active === "1" || user.is_active === true
                );

                const currentUser = window.CURRENT_USER;
                const isAdmin = currentUser && currentUser.is_admin == 1;

                selectUsuario.innerHTML = '<option value="">Seleccionar usuario</option>';

                if (isAdmin) {
                    // ADMIN: Ver todos los usuarios activos
                    usuariosActivos.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.name;
                        selectUsuario.appendChild(option);
                    });
                    console.log(`Admin: ${usuariosActivos.length} usuarios cargados`);
                } else {
                    // USUARIO NORMAL: Solo puede asignarse a sí mismo
                    const currentUserActive = usuariosActivos.find(user =>
                        user.id == currentUser.id
                    );

                    if (currentUserActive) {
                        const option = document.createElement('option');
                        option.value = currentUser.id;
                        option.textContent = currentUser.name + ' (Yo)';
                        selectUsuario.appendChild(option);
                        console.log('Usuario normal: Solo puede asignarse a sí mismo');
                    }
                }
            }
        } catch (error) {
            console.error('Error cargando usuarios:', error);
            selectUsuario.innerHTML = '<option value="">Error al cargar usuarios</option>';
        }
    }

    // === DISPARAR EVENTO DE ACTUALIZACIÓN ===
    function dispararEventoActualizacion() {
        console.log('Disparando eventos de actualización...');

        // Evento para Usuarios
        const eventoUsuarios = new CustomEvent('actualizarConteoTareas', {
            detail: { timestamp: new Date().getTime() }
        });
        window.dispatchEvent(eventoUsuarios);

        // Evento para Perfil
        const eventoPerfil = new CustomEvent('tareaCreada', {
            detail: { timestamp: new Date().getTime() }
        });
        window.dispatchEvent(eventoPerfil);

        console.log('Eventos disparados');
    }

    // === FUNCIONES AUXILIARES ===
    function formatDate(dateString) {
        if (!dateString) return '-';

        try {
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
            console.error('Error formateando fecha:', error);
            return dateString;
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // === FUNCIÓN MOSTRAR ÉXITO MEJORADA ===
    function mostrarExito(mensaje, callback = null) {
        console.log("Mostrando éxito:", mensaje);
        
        if (typeof window.configurarAlerta === 'function') {
            window.configurarAlerta(
                "Éxito", 
                mensaje, 
                "exito", 
                { 
                    soloAceptar: true,
                    onConfirmar: () => {
                        console.log("Alerta de éxito confirmada - cerrando alerta");
                        
                        // Cerrar la alerta automáticamente
                        if (typeof window.mostrarSeccion === 'function') {
                            window.mostrarSeccion("tableros");
                        }
                        
                        // Ejecutar callback después de cerrar
                        if (callback && typeof callback === 'function') {
                            console.log("Ejecutando callback...");
                            setTimeout(() => {
                                callback();
                            }, 100);
                        }
                    }
                }
            );
        } else {
            // Fallback si configurarAlerta no está disponible
            alert(mensaje);
            if (callback && typeof callback === 'function') {
                callback();
            }
        }
    }

    function mostrarError(mensaje) {
        if (typeof window.configurarAlerta === 'function') {
            window.configurarAlerta(
                "Error",
                mensaje,
                "alerta",
                { soloAceptar: true }
            );
        } else {
            alert(mensaje);
        }
    }

    // === ESCUCHAR EVENTOS DE ACTUALIZACIÓN DESDE OTROS MÓDULOS ===
    window.addEventListener('tareaActualizadaDesdeTableros', (event) => {
        console.log("Recibiendo evento de actualización desde Tableros:", event.detail);
        loadBoard(); 
    });

    // === DISPARAR EVENTOS MEJORADOS ===
    function dispararEventoActualizacion(tipo, detalles = {}) {
        console.log(`Disparando eventos de ${tipo}...`);
        
        const timestamp = new Date().getTime();
        
        // Evento para Tareas (actualizar lista)
        const eventoTareas = new CustomEvent('tareaEliminadaEnTableros', {
            detail: {
                ...detalles,
                timestamp: timestamp,
                action: 'delete',
                source: 'boards'
            }
        });
        window.dispatchEvent(eventoTareas);
        console.log("Evento 'tareaEliminadaEnTableros' disparado para tasks.js");
        
        // Evento para Perfil (actualizar actividad)
        const eventoPerfil = new CustomEvent('actividadTareaActualizada', {
            detail: {
                ...detalles,
                timestamp: timestamp,
                action: 'delete',
                source: 'boards'
            }
        });
        window.dispatchEvent(eventoPerfil);
        console.log("Evento 'actividadTareaActualizada' disparado para profile.js");
        
        // Evento para Usuarios (actualizar conteos)
        const eventoUsuarios = new CustomEvent('actualizarConteoTareas', {
            detail: {
                timestamp: timestamp,
                action: 'delete',
                task_id: detalles.taskId
            }
        });
        window.dispatchEvent(eventoUsuarios);
        console.log("Evento 'actualizarConteoTareas' disparado para users.js");
    }

    async function cargarUsuariosParaAsignar() {
        if (!selectUsuario) return;

        try {
            const apiUsuarios = window.API_BASE || '/assets/app/endpoints';
            const res = await fetch(`${apiUsuarios}/list_users.php`);

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const json = await res.json();
            console.log('Usuarios para asignar:', json);

            if (json.ok && json.users) {
                const usuariosActivos = json.users.filter(user =>
                    user.is_active == 1 || user.is_active === "1" || user.is_active === true
                );

                const currentUser = window.CURRENT_USER;
                const isAdmin = currentUser && currentUser.is_admin == 1;

                selectUsuario.innerHTML = '<option value="">Seleccionar usuario</option>';

                if (isAdmin) {
                    // ADMIN: Ver todos los usuarios activos
                    usuariosActivos.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.name;
                        selectUsuario.appendChild(option);
                    });
                    console.log(`Admin: ${usuariosActivos.length} usuarios cargados`);
                } else {
                    // USUARIO NORMAL: Solo puede asignarse a sí mismo
                    const currentUserActive = usuariosActivos.find(user =>
                        user.id == currentUser.id
                    );

                    if (currentUserActive) {
                        const option = document.createElement('option');
                        option.value = currentUser.id;
                        option.textContent = currentUser.name + ' (Yo)';
                        selectUsuario.appendChild(option);
                        console.log('Usuario normal: Solo puede asignarse a sí mismo');
                    }
                }
            }
        } catch (error) {
            console.error('Error cargando usuarios:', error);
            selectUsuario.innerHTML = '<option value="">Error al cargar usuarios</option>';
        }
    }

    // Función específica para manejar tareas asignadas desde Admin
    async function manejarTareasAsignadasAdmin(event) {
        console.log('BOARDS: Tareas asignadas desde Admin', event.detail);
        console.log('User ID afectado:', event.detail.userId);
        console.log('Tareas asignadas:', event.detail.taskIds?.length || 0);
        
        // Recargar el tablero completo
        console.log('Recargando tablero...');
        await loadBoard();
        
        console.log('Tablero actualizado después de asignación desde Admin');
    }

    // === ESCUCHAR EVENTOS DE ACTUALIZACIÓN DESDE TAREAS ===
    window.addEventListener('tareaCreadaDesdeTareas', async (event) => {
        console.log("EVENTO RECIBIDO: Tarea creada desde Tareas", event.detail);
        
        // Recargar tablero inmediatamente
        await loadBoard();
        
        console.log("Tablero actualizado después de creación en Tareas");
    });


    // === LISTENERS PARA AUTO-REFRESH ===
    window.addEventListener('tareaCreadaDesdeTareas', async (event) => {
        console.log('BOARDS: Tarea creada desde Tareas', event.detail);
        await loadBoard();
        console.log('Tablero actualizado después de creación en Tareas');
    });

    window.addEventListener('tareaEliminadaDesdeTareas', async (event) => {
        console.log('BOARDS: Tarea eliminada desde Tareas', event.detail);
        await loadBoard();
        console.log('Tablero actualizado después de eliminación en Tareas');
    });

    window.addEventListener('tareaActualizadaDesdeTareas', async (event) => {
        console.log('BOARDS: Tarea actualizada desde Tareas', event.detail);
        await loadBoard();
        console.log('Tablero actualizado después de modificación en Tareas');
    });

    window.addEventListener('usuarioActualizado', async (event) => {
        console.log('BOARDS: Usuario actualizado', event.detail);
        await loadBoard();
        console.log('Tablero actualizado después de cambio de usuario');
    });

    // === LISTENERS PARA AUTO-REFRESH DESDE ADMIN ===
    window.addEventListener('tareasAsignadasDesdeAdmin', manejarTareasAsignadasAdmin);

    window.addEventListener('usuarioCreadoDesdeAdmin', async (event) => {
        console.log('BOARDS: Usuario creado desde Admin', event.detail);
        await loadBoard();
        await cargarUsuariosParaAsignar(); 
        console.log('Tablero y usuarios actualizados');
    });

    window.addEventListener('usuarioEliminadoDesdeAdmin', async (event) => {
        console.log('BOARDS: Usuario eliminado desde Admin', event.detail);
        await loadBoard();
        await cargarUsuariosParaAsignar(); 
        console.log('Tablero y usuarios actualizados');
    });

    window.addEventListener('usuarioActualizadoDesdeAdmin', async (event) => {
        console.log('BOARDS: Usuario actualizado desde Admin', event.detail);
        await loadBoard();
        console.log('Tablero actualizado después de edición en Admin');
    });

    // === LISTENERS PARA ACTUALIZACIÓN DE TAREAS ASIGNADAS ===
    window.addEventListener('tareasAsignadasDesdeAdmin', async (event) => {
        console.log('BOARDS: Tareas asignadas desde Admin', event.detail);
        console.log('User ID afectado:', event.detail.userId);
        
        // Recargar el tablero completo
        await loadBoard();
        
        // También recargar usuarios para el formulario de creación
        await cargarUsuariosParaAsignar();
        
        console.log('Tablero actualizado después de asignación desde Admin');
    });

    window.addEventListener('usuarioActualizadoDesdeAdmin', async (event) => {
        console.log('BOARDS: Usuario actualizado desde Admin', event.detail);
        await cargarUsuariosParaAsignar();
        console.log('Usuarios actualizados en formulario de tableros');
    });

    console.log("Boards.js inicializado correctamente");
});