// assets/javascript/admin.js
console.log('admin.js cargado - Inicializando...');

class AdminManager {
    constructor() {
        this.baseUrl = window.API_BASE || '/PROYECTO_GESTOR_TAREAS/assets/app/endpoints';
        this.currentUser = window.CURRENT_USER;
        this.users = [];
        this.availableTasks = [];
        this.editingUserId = null;
        
        console.log('AdminManager inicializado');
        console.log('Usuario actual:', this.currentUser);
        console.log('Es admin:', this.currentUser?.is_admin);
        console.log('API_BASE configurado:', this.baseUrl);
        
        window.addEventListener('tareaCreadaDesdeTareas', async (event) => {
            console.log('ADMIN: Tarea creada desde Tareas', event.detail);
            await this.loadAvailableTasks(); // Recargar tareas disponibles
            console.log('Tareas disponibles actualizadas');
        });

        window.addEventListener('tareaCreadaEnTableros', async (event) => {
            console.log('ADMIN: Tarea creada desde Tableros', event.detail);
            await this.loadAvailableTasks(); // Recargar tareas disponibles
            console.log('Tareas disponibles actualizadas');
        });

        window.addEventListener('tareaEliminadaDesdeTareas', async (event) => {
            console.log('ADMIN: Tarea eliminada desde Tareas', event.detail);
            await this.loadAvailableTasks(); // Recargar tareas disponibles
            console.log('Tareas disponibles actualizadas');
        });

        window.addEventListener('tareaEliminadaEnTableros', async (event) => {
            console.log('ADMIN: Tarea eliminada desde Tableros', event.detail);
            await this.loadAvailableTasks(); // Recargar tareas disponibles
            console.log('Tareas disponibles actualizadas');
        });

        this.init();
    }

    init() {
        // Verificar si el usuario es admin
        if (!this.currentUser?.is_admin) {
            console.warn('Usuario no es administrador');
            this.showNoAccessMessage();
            return;
        }

        this.setupEventListeners();
        this.loadAvailableTasks();
    }

    showNoAccessMessage() {
        const adminSection = document.getElementById('admin');
        if (adminSection) {
            adminSection.innerHTML = `
                <h2 class="titulo-seccion">Usuarios - Admin</h2>
                <div style="text-align: center; padding: 50px; color: #999;">
                    <p style="font-size: 18px;">No tienes permisos de administrador</p>
                    <p>Solo los administradores pueden acceder a esta sección</p>
                </div>
            `;
        }
    }

    setupEventListeners() {
        // Botón para añadir usuario (fila "Añadir")
        const filaAgregar = document.querySelector('.fila-agregar[data-action="añadir-usuario"]');
        if (filaAgregar) {
            filaAgregar.addEventListener('click', (e) => {
                if (!e.target.closest('.btn-editar-admin') && !e.target.closest('.btn-eliminar-admin')) {
                    this.showAddUserForm();
                }
            });
        }

        // Formulario añadir usuario
        const formAddUser = document.getElementById('form-usuario-admin');
        if (formAddUser) {
            formAddUser.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleAddUser(e);
            });

            const btnCancelar = formAddUser.querySelector('.cancelar');
            if (btnCancelar) {
                btnCancelar.addEventListener('click', () => {
                    window.mostrarSeccion('admin');
                });
            }
        }

        // Formulario editar usuario
        const formEditUser = document.getElementById('form-editar-usuario-admin');
        if (formEditUser) {
            formEditUser.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleEditUser(e);
            });

            const btnCancelar = formEditUser.querySelector('.cancelar');
            if (btnCancelar) {
                btnCancelar.addEventListener('click', () => {
                    window.mostrarSeccion('admin');
                });
            }
        }

        // Delegación de eventos para botones
        const tablaAdmin = document.querySelector('.tabla-admin tbody');
        if (tablaAdmin) {
            tablaAdmin.addEventListener('click', (e) => {
                const btnEditar = e.target.closest('.btn-editar-admin');
                const btnEliminar = e.target.closest('.btn-eliminar-admin');

                if (btnEditar) {
                    e.preventDefault();
                    e.stopPropagation();
                    const fila = btnEditar.closest('tr');
                    const userId = fila.getAttribute('data-user-id');
                    if (userId) {
                        this.showEditUserForm(userId);
                    }
                }

                if (btnEliminar) {
                    e.preventDefault();
                    e.stopPropagation();
                    const fila = btnEliminar.closest('tr');
                    const userId = fila.getAttribute('data-user-id');
                    const userName = fila.querySelector('td:first-child')?.textContent;
                    if (userId) {
                        this.confirmDeleteUser(userId, userName);
                    }
                }
            });
        }

        // Observer para detectar cuando se muestra la sección admin
        const adminSection = document.getElementById('admin');
        if (adminSection) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (adminSection.classList.contains('activa')) {
                            console.log('Sección admin activada - Cargando usuarios');
                            this.loadUsers();
                        }
                    }
                });
            });

            observer.observe(adminSection, { attributes: true });
        }
    }

    async loadUsers() {
        try {
            console.log('Cargando usuarios admin...');
            const response = await fetch(`${this.baseUrl}/list_users_admin.php`);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const data = await response.json();
            console.log('Usuarios recibidos:', data);

            if (data.ok) {
                this.users = data.users;
                this.renderUsersTable();
            } else {
                throw new Error(data.message || 'Error al cargar usuarios');
            }
        } catch (error) {
            console.error('Error loading users:', error);
            this.showToast('Error al cargar usuarios: ' + error.message, 'error');
        }
    }

    async loadAvailableTasks() {
        try {
            console.log('Cargando tareas disponibles desde:', this.baseUrl);
            
            let response = await fetch(`${this.baseUrl}/get_available_tasks.php`);
            
            if (!response.ok) {
                console.log('Primer intento falló, intentando ruta alternativa...');
                
                if (window.location.hostname.includes('localhost')) {
                    const alternativeUrl = '/PROYECTO_GESTOR_TAREAS/assets/app/endpoints/get_available_tasks.php';
                    console.log('Intentando con URL alternativa:', alternativeUrl);
                    response = await fetch(alternativeUrl);
                }
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
            }

            const data = await response.json();
            console.log('Tareas disponibles:', data);

            if (data.ok) {
                this.availableTasks = data.tasks;
                console.log(`${this.availableTasks.length} tareas cargadas`);
            } else {
                throw new Error(data.message || 'Error en la respuesta del servidor');
            }
        } catch (error) {
            console.error('Error loading tasks:', error);
            this.showToast('No se pudieron cargar las tareas disponibles. Verifica la consola.', 'warning');
            
            // Mostrar mensaje de error en los formularios
            const errorMessage = `Error: ${error.message}. URL intentada: ${this.baseUrl}/get_available_tasks.php`;
            console.error(errorMessage);
        }
    }

    renderUsersTable() {
        const tbody = document.querySelector('.tabla-admin tbody');
        if (!tbody) return;

        const filaAgregar = tbody.querySelector('.fila-agregar');
        tbody.innerHTML = '';

        this.users.forEach(user => {
            const fila = document.createElement('tr');
            fila.setAttribute('data-user-id', user.id);

            fila.innerHTML = `
                <td>${this.escapeHtml(user.name)}</td>
                <td>${this.escapeHtml(user.email)}</td>
                <td>${user.assigned_count || 0}</td>
                <td>${this.escapeHtml(user.notes || '')}</td>
                <td class="acciones-celda">
                    <button class="btn-editar-admin">
                        <img src="../../assets/img/editar.png" alt="Editar">
                    </button>
                    <button class="btn-eliminar-admin">
                        <img src="../../assets/img/eliminar.png" alt="Eliminar">
                    </button>
                </td>
            `;

            tbody.appendChild(fila);
        });

        if (filaAgregar) {
            tbody.appendChild(filaAgregar);
        }

        console.log(`${this.users.length} usuarios renderizados en la tabla admin`);
    }

    showAddUserForm() {
        const form = document.getElementById('form-usuario-admin');
        if (form) {
            form.reset();
        }

        // Renderizar checkboxes
        this.renderTaskCheckboxes('form-usuario-admin');

        window.mostrarSeccion('formulario-admin');
    }

    showEditUserForm(userId) {
        const user = this.users.find(u => u.id == userId);
        if (!user) {
            console.error('Usuario no encontrado:', userId);
            return;
        }

        this.editingUserId = userId;

        document.getElementById('edit-nombre-completo').value = user.name;
        document.getElementById('edit-correo-electronico').value = user.email;
        document.getElementById('edit-notas').value = user.notes || '';

        // Cargar tareas del usuario
        this.loadUserTasks(userId);

        window.mostrarSeccion('editar-usuario-admin');
    }

    async loadUserTasks(userId) {
        try {
            console.log('Cargando tareas del usuario:', userId);
            const response = await fetch(`${this.baseUrl}/get_user_tasks_admin.php?user_id=${userId}`);
            const data = await response.json();

            if (data.ok) {
                const taskIds = data.tasks.map(t => t.id);
                console.log('Tareas del usuario:', taskIds);
                this.renderTaskCheckboxes('form-editar-usuario-admin', taskIds);
            } else {
                console.warn('No se pudieron cargar las tareas del usuario');
                this.renderTaskCheckboxes('form-editar-usuario-admin', []);
            }
        } catch (error) {
            console.error('Error loading user tasks:', error);
            this.renderTaskCheckboxes('form-editar-usuario-admin', []);
        }
    }

    renderTaskCheckboxes(formId, selectedTaskIds = []) {
        const form = document.getElementById(formId);
        if (!form) {
            console.error('Formulario no encontrado:', formId);
            return;
        }

        const checkboxContainer = form.querySelector('.tasks-checkbox-container');
        if (!checkboxContainer) {
            console.error('Contenedor de checkboxes no encontrado en', formId);
            return;
        }

        // Si no hay tareas disponibles
        if (!this.availableTasks || this.availableTasks.length === 0) {
            checkboxContainer.innerHTML = `
                <div style="
                    text-align: center; 
                    padding: 20px; 
                    color: #666;
                    background: #f0f0f0;
                    border-radius: 8px;
                ">
                    <p style="margin: 0;">No hay tareas disponibles para asignar</p>
                </div>
            `;
            return;
        }

        console.log(`Renderizando ${this.availableTasks.length} tareas en ${formId}, seleccionadas: ${selectedTaskIds.length}`);

        // Limpiar contenedor
        checkboxContainer.innerHTML = '';

        // Crear contenedor principal con scroll
        const scrollContainer = document.createElement('div');
        scrollContainer.style.cssText = `
            max-height: 300px;
            overflow-y: auto;
            border: 2px solid #2897FF;
            border-radius: 8px;
            padding: 15px;
            background: #ffffff;
        `;

        // Crear grid de checkboxes
        const gridContainer = document.createElement('div');
        gridContainer.style.cssText = `
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 12px;
        `;

        // Renderizar cada tarea
        this.availableTasks.forEach(task => {
            const isChecked = selectedTaskIds.includes(task.id);
            
            const checkboxWrapper = document.createElement('div');
            checkboxWrapper.style.cssText = `
                display: flex;
                align-items: center;
                padding: 10px;
                background: ${isChecked ? '#e3f2fd' : '#2897FF'};
                border: 2px solid ${isChecked ? '#007bff' : '#2897FF'};
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
            `;
            
            checkboxWrapper.innerHTML = `
                <input 
                    type="checkbox" 
                    name="task_ids[]" 
                    value="${task.id}" 
                    ${isChecked ? 'checked' : ''}
                    style="
                        width: 18px;
                        height: 18px;
                        cursor: pointer;
                        margin-right: 10px;
                        accent-color: #007bff;
                        flex-shrink: 0;
                    ">
                <span style="
                    color: ${isChecked ? '#000' : '#fff'};
                    font-size: 14px;
                    font-weight: ${isChecked ? '600' : '400'};
                    line-height: 1.3;
                    word-break: break-word;
                " title="${this.escapeHtml(task.title)}">
                    ${this.escapeHtml(task.title)}
                </span>
            `;
            
            // Agregar evento de hover
            checkboxWrapper.addEventListener('mouseenter', function() {
                if (!this.querySelector('input').checked) {
                    this.style.background = '#1e7dd6';
                }
            });
            
            checkboxWrapper.addEventListener('mouseleave', function() {
                const checkbox = this.querySelector('input');
                if (!checkbox.checked) {
                    this.style.background = '#2897FF';
                }
            });
            
            // Hacer que el div completo sea clickeable
            checkboxWrapper.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const checkbox = this.querySelector('input');
                    checkbox.checked = !checkbox.checked;
                    
                    const span = this.querySelector('span');
                    
                    // Actualizar estilos
                    if (checkbox.checked) {
                        this.style.background = '#e3f2fd';
                        this.style.borderColor = '#007bff';
                        span.style.fontWeight = '600';
                        span.style.color = '#000';
                    } else {
                        this.style.background = '#2897FF';
                        this.style.borderColor = '#2897FF';
                        span.style.fontWeight = '400';
                        span.style.color = '#fff';
                    }
                    
                    updateCounter();
                }
            });
            
            // Evento para el checkbox
            const checkbox = checkboxWrapper.querySelector('input');
            checkbox.addEventListener('change', function() {
                const span = checkboxWrapper.querySelector('span');
                
                if (this.checked) {
                    checkboxWrapper.style.background = '#e3f2fd';
                    checkboxWrapper.style.borderColor = '#007bff';
                    span.style.fontWeight = '600';
                    span.style.color = '#000';
                } else {
                    checkboxWrapper.style.background = '#2897FF';
                    checkboxWrapper.style.borderColor = '#2897FF';
                    span.style.fontWeight = '400';
                    span.style.color = '#fff';
                }
                updateCounter();
            });
            
            gridContainer.appendChild(checkboxWrapper);
        });

        scrollContainer.appendChild(gridContainer);
        checkboxContainer.appendChild(scrollContainer);

        // Crear contador
        const counter = document.createElement('div');
        counter.className = 'tasks-counter';
        counter.style.cssText = `
            margin-top: 12px;
            text-align: center;
            padding: 8px;
            background: #e3f2fd;
            border-radius: 6px;
            color: #2897FF;
            font-weight: 600;
            font-size: 14px;
        `;
        counter.textContent = `${selectedTaskIds.length} de ${this.availableTasks.length} tareas seleccionadas`;
        
        checkboxContainer.appendChild(counter);

        // Función para actualizar contador
        const updateCounter = () => {
            const selectedCount = checkboxContainer.querySelectorAll('input[type="checkbox"]:checked').length;
            counter.textContent = `${selectedCount} de ${this.availableTasks.length} tareas seleccionadas`;
        };

        console.log(`${this.availableTasks.length} checkboxes renderizados. ${selectedTaskIds.length} seleccionados inicialmente`);
    }

    async handleAddUser(e) {
        const form = e.target;
        
        const name = form.querySelector('input[name="name"]').value.trim();
        const email = form.querySelector('input[name="email"]').value.trim();
        const notes = form.querySelector('textarea[name="notes"]').value.trim();
        
        const selectedTasks = Array.from(form.querySelectorAll('input[name="task_ids[]"]:checked'))
            .map(cb => parseInt(cb.value));

        console.log('Datos a enviar:', { name, email, notes, task_ids: selectedTasks });

        if (!name || !email) {
            window.configurarAlerta(
                'Error',
                'Por favor completa nombre y correo electrónico',
                'alerta',
                {
                    soloAceptar: true,
                    onConfirmar: () => {
                        window.mostrarSeccion('formulario-admin');
                    }
                }
            );
            return;
        }

        window.configurarAlerta(
            'Añadir Usuario',
            `¿Estás seguro de que deseas añadir a <strong>${this.escapeHtml(name)}</strong>?`,
            'alerta',
            {
                textoConfirmar: 'Añadir',
                onConfirmar: async () => {
                    await this.createUser(name, email, notes, selectedTasks);
                },
                onCancelar: () => {
                    console.log('Cancelado - manteniendo en formulario');
                    window.mostrarSeccion('formulario-admin');
                }
            }
        );
    }

    async createUser(name, email, notes, task_ids) {
        try {
            this.showToast('Creando usuario...', 'info');

            const response = await fetch(`${this.baseUrl}/create_user_admin.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ name, email, notes, task_ids })
            });

            const data = await response.json();

            if (data.ok) {
                this.showToast('Usuario creado exitosamente', 'success');
                
                // Recargar usuarios inmediatamente
                await this.loadUsers();
                
                // DISPARAR EVENTOS PARA ACTUALIZAR TODOS LOS MÓDULOS
                window.dispatchEvent(new CustomEvent('usuarioCreadoDesdeAdmin', {
                    detail: {
                        userId: data.user.id,
                        userName: data.user.name,
                        taskIds: task_ids,
                        timestamp: new Date().getTime()
                    }
                }));
                
                // Si se asignaron tareas, disparar evento de asignación
                if (task_ids && task_ids.length > 0) {
                    window.dispatchEvent(new CustomEvent('tareasAsignadasDesdeAdmin', {
                        detail: {
                            userId: data.user.id,
                            taskIds: task_ids,
                            action: 'assign',
                            timestamp: new Date().getTime()
                        }
                    }));
                }
                
                console.log('Eventos disparados: usuarioCreadoDesdeAdmin, tareasAsignadasDesdeAdmin');
                
                window.configurarAlerta(
                    'Usuario Creado',
                    `Usuario creado exitosamente.<br><br>
                    <strong>Contraseña temporal:</strong> ${data.temporary_password}<br><br>
                    <small>Por favor, comunica esta contraseña al usuario para que pueda iniciar sesión.</small>`,
                    'exito',
                    {
                        soloAceptar: true,
                        onConfirmar: () => {
                            window.mostrarSeccion('admin');
                        }
                    }
                );
            } else {
                throw new Error(data.message || 'Error al crear usuario');
            }
        } catch (error) {
            console.error('Error creating user:', error);
            this.showToast('Error al crear usuario: ' + error.message, 'error');
            
            window.configurarAlerta(
                'Error',
                'No se pudo crear el usuario: ' + error.message,
                'alerta',
                {
                    soloAceptar: true,
                    onConfirmar: () => {
                        window.mostrarSeccion('formulario-admin');
                    }
                }
            );
        }
    }

    async handleEditUser(e) {
        e.preventDefault();
        
        const form = e.target;
        const name = document.getElementById('edit-nombre-completo').value.trim();
        const email = document.getElementById('edit-correo-electronico').value.trim();
        const notes = document.getElementById('edit-notas').value.trim();
        
        const selectedTasks = Array.from(form.querySelectorAll('input[name="task_ids[]"]:checked'))
            .map(cb => parseInt(cb.value));

        console.log('ADMIN: Iniciando actualización de usuario');
        console.log('Tareas seleccionadas:', selectedTasks);
        console.log('Usuario ID:', this.editingUserId);
        console.log('Nombre:', name);

        if (!name || !email) {
            window.configurarAlerta(
                'Error',
                'Por favor completa nombre y correo electrónico',
                'alerta',
                {
                    soloAceptar: true,
                    onConfirmar: () => {
                        window.mostrarSeccion('editar-usuario-admin');
                    }
                }
            );
            return;
        }

        try {
            this.showToast('Actualizando usuario...', 'info');

            const response = await fetch(`${this.baseUrl}/update_user_admin.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: this.editingUserId,
                    name,
                    email,
                    notes,
                    task_ids: selectedTasks
                })
            });

            const data = await response.json();
            console.log('ADMIN: Respuesta del servidor:', data);

            if (data.ok) {
                this.showToast('Usuario actualizado exitosamente', 'success');
                
                // Recargar usuarios en admin
                await this.loadUsers();
                
                // DISPARAR EVENTOS CON MÁS DETALLES
                console.log('ADMIN: Disparando eventos de actualización...');
                
                // EVENTO 1: Para tareas asignadas
                const tareaEvent = new CustomEvent('tareasAsignadasDesdeAdmin', {
                    detail: {
                        userId: this.editingUserId,
                        taskIds: selectedTasks,
                        assignedCount: selectedTasks.length,
                        userName: name,
                        action: 'update',
                        timestamp: new Date().getTime(),
                        source: 'admin'
                    }
                });
                window.dispatchEvent(tareaEvent);
                console.log('Evento "tareasAsignadasDesdeAdmin" disparado');
                
                // EVENTO 2: Para usuarios
                const usuarioEvent = new CustomEvent('usuarioActualizadoDesdeAdmin', {
                    detail: {
                        userId: this.editingUserId,
                        userName: name,
                        taskIds: selectedTasks,
                        assignedCount: data.user?.assigned_count || selectedTasks.length,
                        timestamp: new Date().getTime(),
                        action: 'update',
                        source: 'admin'
                    }
                });
                window.dispatchEvent(usuarioEvent);
                console.log('Evento "usuarioActualizadoDesdeAdmin" disparado');
                
                // EVENTO 3: Para conteo
                const conteoEvent = new CustomEvent('actualizarConteoTareas', {
                    detail: {
                        userId: this.editingUserId,
                        taskIds: selectedTasks,
                        assignedCount: selectedTasks.length,
                        timestamp: new Date().getTime(),
                        source: 'admin'
                    }
                });
                window.dispatchEvent(conteoEvent);
                console.log('Evento "actualizarConteoTareas" disparado');
                
                // Si es el usuario actual
                const currentUser = window.CURRENT_USER;
                if (currentUser && String(currentUser.id) === String(this.editingUserId)) {
                    console.log('ADMIN: Usuario actual editado');
                    
                    window.CURRENT_USER.name = data.user?.name || name;
                    window.CURRENT_USER.email = data.user?.email || email;
                    window.CURRENT_USER.assigned_count = data.user?.assigned_count || selectedTasks.length;
                    
                    window.dispatchEvent(new CustomEvent('usuarioActualizadoEnSesion', {
                        detail: {
                            userId: this.editingUserId,
                            nombre: name,
                            email: email,
                            avatar_url: data.user?.avatar_url,
                            assigned_count: data.user?.assigned_count || selectedTasks.length,
                            timestamp: new Date().getTime()
                        }
                    }));
                }
                
                console.log('ADMIN: Todos los eventos disparados');
                
                window.configurarAlerta(
                    'Éxito',
                    'Los cambios se guardaron correctamente',
                    'exito',
                    {
                        soloAceptar: true,
                        onConfirmar: () => {
                            window.mostrarSeccion('admin');
                        }
                    }
                );
            } else {
                throw new Error(data.message || 'Error al actualizar usuario');
            }
        } catch (error) {
            console.error('ADMIN: Error updating user:', error);
            this.showToast('Error al actualizar usuario: ' + error.message, 'error');
            
            window.configurarAlerta(
                'Error',
                'No se pudo actualizar el usuario: ' + error.message,
                'alerta',
                {
                    soloAceptar: true,
                    onConfirmar: () => {
                        window.mostrarSeccion('editar-usuario-admin');
                    }
                }
            );
        }
    }

    confirmDeleteUser(userId, userName) {
        if (userId == this.currentUser.id) {
            window.configurarAlerta(
                'Error',
                'No puedes eliminar tu propia cuenta',
                'alerta',
                {
                    soloAceptar: true,
                    onConfirmar: () => {
                        window.mostrarSeccion('admin');
                    }
                }
            );
            return;
        }

        window.configurarAlerta(
            'Eliminar Usuario',
            `¿Eliminar a <strong>${this.escapeHtml(userName)}</strong>?<br>Esta acción no se puede deshacer.`,
            'alerta',
            {
                textoConfirmar: 'Eliminar',
                onConfirmar: async () => {
                    await this.deleteUser(userId);
                },
                onCancelar: () => {
                    console.log('Cancelado - manteniendo en admin');
                    window.mostrarSeccion('admin');
                }
            }
        );
    }

    async deleteUser(userId) {
        try {
            this.showToast('Eliminando usuario...', 'info');

            const response = await fetch(`${this.baseUrl}/delete_user_admin.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: userId })
            });

            const data = await response.json();

            if (data.ok) {
                this.showToast('Usuario eliminado exitosamente', 'success');
                
                // Recargar usuarios inmediatamente
                await this.loadUsers();
                
                // Disparar eventos para actualizar todos los módulos
                window.dispatchEvent(new CustomEvent('usuarioEliminadoDesdeAdmin', {
                    detail: {
                        userId: userId,
                        timestamp: new Date().getTime()
                    }
                }));
                
                // También disparar evento para actualizar tareas
                window.dispatchEvent(new CustomEvent('tareasAsignadasDesdeAdmin', {
                    detail: {
                        userId: userId,
                        taskIds: [],
                        action: 'delete',
                        timestamp: new Date().getTime()
                    }
                }));
                
                console.log('Eventos disparados: usuarioEliminadoDesdeAdmin, tareasAsignadasDesdeAdmin');
                
                window.configurarAlerta(
                    'Éxito',
                    'Usuario eliminado correctamente',
                    'exito',
                    {
                        soloAceptar: true,
                        onConfirmar: () => {
                            window.mostrarSeccion('admin');
                        }
                    }
                );
            } else {
                throw new Error(data.message || 'Error al eliminar usuario');
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            this.showToast('Error al eliminar usuario: ' + error.message, 'error');
            
            window.configurarAlerta(
                'Error',
                'No se pudo eliminar el usuario: ' + error.message,
                'alerta',
                {
                    soloAceptar: true,
                    onConfirmar: () => {
                        window.mostrarSeccion('admin');
                    }
                }
            );
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showToast(message, type = 'info') {
        if (window.showToast) {
            window.showToast(message, type);
        } else {
            console.log(`Toast (${type}): ${message}`);
        }
    }

    
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('Inicializando AdminManager...');
    window.adminManager = new AdminManager();
});