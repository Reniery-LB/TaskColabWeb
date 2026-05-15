class ReportsManager {
    // assets/javascript/reports.js
    constructor() {
        this.baseUrl = window.API_BASE ? window.API_BASE.replace('/endpoints', '/endpointsReportes') : '/PROYECTO_GESTOR_TAREAS/assets/app/endpointsReportes';
        console.log('ReportsManager inicializado. Base URL:', this.baseUrl);
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadDashboardStats();
    }

    setupEventListeners() {
        console.log('🔧 Inicializando event listeners...');
        
        // BOTÓN EXPORTAR PDF
        const exportBtn = document.getElementById('btn-exportar-pdf');
        if (exportBtn) {
            console.log('Botón Exportar PDF encontrado');
            exportBtn.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Click en Exportar PDF');
                
                if (window.configurarAlerta) {
                    window.configurarAlerta(
                        'Exportar PDF',
                        '¿Estás seguro de que deseas exportar el reporte a PDF?',
                        'alerta',
                        {
                            textoConfirmar: 'Exportar',
                            onConfirmar: () => {
                                console.log('Usuario confirmó exportación');
                                this.exportToPDF();
                            },
                            onCancelar: () => {
                                console.log('Usuario canceló exportación');
                                this.showToast('Exportación cancelada', 'info');
                            }
                        }
                    );
                } else {
                    this.exportToPDF();
                }
            });
        } else {
            console.error('Botón Exportar PDF NO encontrado');
        }

        // NAVEGACIÓN DESDE ESTADÍSTICAS
        document.querySelectorAll('.tarjeta-metrica').forEach((card, index) => {
            card.addEventListener('click', () => {
                console.log(`Click en tarjeta métrica [${index}]`);
                this.navigateFromStats(index);
            });
            
            card.style.cursor = 'pointer';
            card.style.transition = 'all 0.3s ease';
            
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            });
        });
    }

    async loadDashboardStats() {
        try {
            console.log('Cargando estadísticas del dashboard...');
            const url = `${this.baseUrl}/get_dashboard_stats.php`;
            console.log('URL de petición:', url);
            
            const response = await fetch(url, {
                credentials: 'include'
            });
            
            console.log('Response status:', response.status, response.statusText);
            
            if (!response.ok) {
                console.error(`Error HTTP: ${response.status}`);
                this.showFallbackData();
                return;
            }
            
            const responseText = await response.text();
            
            // Verificar si es JSON válido
            if (!responseText.trim().startsWith('{') && !responseText.trim().startsWith('[')) {
                console.error('La respuesta no es JSON válido:', responseText.substring(0, 200));
                this.showFallbackData();
                return;
            }
            
            const result = JSON.parse(responseText);
            console.log('Datos recibidos correctamente');

            if (result.success) {
                this.updateGeneralStats(result.data.general_stats);
                this.updateBoardProgress(result.data.board_progress);
                this.updateActiveUsers(result.data.active_users);
                this.updateOverdueTasks(result.data.overdue_tasks);
                this.showToast('Estadísticas cargadas correctamente', 'success');
            } else {
                throw new Error(result.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
            this.showToast('Error al cargar estadísticas', 'error');
            this.showFallbackData();
        }
    }

    showFallbackData() {
        console.log('Mostrando datos de ejemplo como fallback');
        
        const exampleStats = {
            total_tareas: 0,
            pendiente: 0,
            en_proceso: 0,
            completado: 0
        };
        
        this.updateGeneralStats(exampleStats);
        
        const realProgress = [
            { status_display: 'Pendiente', percentage: 0 },
            { status_display: 'En Proceso', percentage: 0 },
            { status_display: 'Completado', percentage: 0 }
        ];
        
        this.updateBoardProgress(realProgress);
        this.updateActiveUsers([]);
        this.updateOverdueTasks([]);
    }

    updateGeneralStats(stats) {
        console.log('Actualizando estadísticas generales:', stats);
        
        const metricCards = document.querySelectorAll('.tarjeta-metrica p');
        if (metricCards.length >= 4) {
            metricCards[0].textContent = stats.total_tareas || 0;
            metricCards[1].textContent = stats.pendiente || 0;
            metricCards[2].textContent = stats.en_proceso || 0;
            metricCards[3].textContent = stats.completado || 0;
        }
    }

    updateBoardProgress(progress) {
        console.log('Actualizando progreso del tablero:', progress);
        const container = document.querySelector('.contenedor-barras .recuadro:first-child');
        if (!container) return;

        let barsHtml = '';
        
        if (progress && progress.length > 0) {
            progress.forEach(item => {
                const percentage = Math.round(item.percentage || 0);
                barsHtml += `
                    <div class="barra">
                        <span>${item.status_display || item.status}</span>
                        <div class="barra-contenido">
                            <div class="barra-progreso" style="width: ${percentage}%"></div>
                        </div>
                        <span class="porcentaje">${percentage}%</span>
                    </div>
                `;
            });
        } else {
            barsHtml = `
                <div class="barra">
                    <span>Pendiente</span>
                    <div class="barra-contenido">
                        <div class="barra-progreso" style="width: 0%"></div>
                    </div>
                    <span class="porcentaje">0%</span>
                </div>
                <div class="barra">
                    <span>En proceso</span>
                    <div class="barra-contenido">
                        <div class="barra-progreso" style="width: 0%"></div>
                    </div>
                    <span class="porcentaje">0%</span>
                </div>
                <div class="barra">
                    <span>Completado</span>
                    <div class="barra-contenido">
                        <div class="barra-progreso" style="width: 0%"></div>
                    </div>
                    <span class="porcentaje">0%</span>
                </div>
            `;
        }

        const title = container.querySelector('h3');
        container.innerHTML = '';
        if (title) container.appendChild(title);
        container.innerHTML += barsHtml;
    }

    updateActiveUsers(users) {
        console.log('Actualizando usuarios activos:', users);
        const container = document.querySelector('.contenedor-barras .recuadro:last-child');
        if (!container) return;

        let barsHtml = '';
        
        if (users && users.length > 0) {
            users.forEach(user => {
                const percentage = Math.round(user.porcentaje || 0);
                barsHtml += `
                    <div class="barra">
                        <span>${user.usuario}</span>
                        <div class="barra-contenido">
                            <div class="barra-progreso" style="width: ${percentage}%"></div>
                        </div>
                        <span class="porcentaje">${percentage}%</span>
                    </div>
                `;
            });
        } else {
            barsHtml = `
                <div class="barra">
                    <span>No hay usuarios activos</span>
                    <div class="barra-contenido">
                        <div class="barra-progreso" style="width: 0%"></div>
                    </div>
                    <span class="porcentaje">0%</span>
                </div>
            `;
        }

        const title = container.querySelector('h3');
        container.innerHTML = '';
        if (title) container.appendChild(title);
        container.innerHTML += barsHtml;
    }

    updateOverdueTasks(tasks) {
        console.log('Actualizando tareas atrasadas:', tasks);
        const container = document.querySelector('.contenido-atrasadas');
        if (!container) return;

        if (!tasks || tasks.length === 0) {
            container.innerHTML = `
                <p>¡En este momento no se encuentran tareas atrasadas!</p>
                <img src="/PROYECTO_GESTOR_TAREAS/assets/img/like.png" alt="Like" class="icono-like">
            `;
            return;
        }

        let tasksHtml = `
            <div class="lista-tareas-atrasadas">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">Tarea</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">Fecha Límite</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">Prioridad</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        // Eliminar duplicados por ID
        const uniqueTasks = [];
        const seenIds = new Set();
        
        tasks.forEach(task => {
            if (!seenIds.has(task.id)) {
                seenIds.add(task.id);
                uniqueTasks.push(task);
            }
        });

        uniqueTasks.forEach(task => {
            let dueDate;
            if (task.due_date_display) {
                dueDate = task.due_date_display;
            } else {
                const dateObj = new Date(task.due_date);
                dueDate = dateObj.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            }
            
            const priorityMap = {
                'high': 'Alta',
                'medium': 'Media', 
                'low': 'Baja'
            };
            const priorityText = task.priority_display || priorityMap[task.priority] || task.priority;
            const priorityClass = task.priority === 'high' ? 'style="color: #dc3545; font-weight: bold;"' : '';

            tasksHtml += `
                <tr style="border-bottom: 1px solid #dee2e6;">
                    <td style="padding: 10px;">${task.title}</td>
                    <td style="padding: 10px; color: #dc3545; font-weight: bold;">${dueDate}</td>
                    <td style="padding: 10px;" ${priorityClass}>${priorityText}</td>
                </tr>
            `;
        });

        tasksHtml += `
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = tasksHtml;
    }

    navigateFromStats(cardIndex) {
        const menuManager = window.menuManager;
        if (!menuManager) {
            console.error('MenuManager no encontrado');
            return;
        }

        switch(cardIndex) {
            case 0:
                menuManager.showSection('tareas');
                break;
            case 1:
            case 2:  
            case 3:
                menuManager.showSection('tableros');
                break;
        }
    }

    async exportToPDF() {
        try {
            this.showToast('Generando PDF...', 'info');
            console.log('Iniciando exportación PDF...');
            
            const url = `${this.baseUrl}/export_pdf.php`;
            console.log('URL del PDF:', url);
            
            // Abrir en nueva ventana
            window.open(url, '_blank');
            
            setTimeout(() => {
                this.showToast('PDF generado exitosamente', 'success');
            }, 1500);
            
        } catch (error) {
            console.error('Error exporting PDF:', error);
            this.showToast('Error al exportar PDF: ' + error.message, 'error');
        }
    }

    showToast(message, type = 'info') {
        if (window.showToast) {
            window.showToast(message, type);
        } else {
            console.log(`Toast (${type}): ${message}`);
            // Toast simple como fallback
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                background: ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#17a2b8'};
                color: white;
                border-radius: 4px;
                z-index: 10000;
                font-family: Arial, sans-serif;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentNode) {
                    document.body.removeChild(toast);
                }
            }, 3000);
        }
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Configurando ReportsManager');
    
    const reportesSection = document.getElementById('reportes');
    if (reportesSection && reportesSection.classList.contains('activa')) {
        window.reportsManager = new ReportsManager();
        console.log('ReportsManager inicializado en carga inicial');
    }
    
    // Observar cambios de sección
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const reportesSection = document.getElementById('reportes');
                if (reportesSection && reportesSection.classList.contains('activa')) {
                    if (!window.reportsManager) {
                        window.reportsManager = new ReportsManager();
                        console.log('ReportsManager inicializado por cambio de sección');
                    } else {
                        window.reportsManager.loadDashboardStats();
                    }
                }
            }
        });
    });
    
    if (reportesSection) {
        observer.observe(reportesSection, { attributes: true });
    }
});