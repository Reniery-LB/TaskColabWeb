<?php include __DIR__ . '/../../assets/app/header.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestor de Tareas</title>
  <link rel="icon" type="image/png" href="../../assets/img/logo.png">
  <link rel="apple-touch-icon" href="../../assets/img/logo.png">
  <link rel="stylesheet" href="../../assets/styles/pantallas.css?v=20260525-1">
</head>
<body>
  <!-- CONTENIDO PRINCIPAL -->
  <main class="main-content">
    <!-- INICIO -->
    <section id="inicio" class="seccion activa">
      <?php
      $userName = $_SESSION['user']['name'] ?? 'Invitado';
      ?>
      <div class="inicio-hero-copy">
        <span class="inicio-eyebrow">Panel de productividad</span>
        <h2>Bienvenido a <span class="task-blue">Task</span>Colab</h2>
        <p>Organiza tu trabajo, revisa pendientes y avanza con tu equipo desde un solo espacio, <?php echo htmlspecialchars($userName, ENT_QUOTES); ?>.</p>
        <div class="inicio-actions">
          <button type="button" class="inicio-action primary" data-go-section="proyectos">Crear proyecto</button>
          <button type="button" class="inicio-action primary" data-go-section="tableros">Ver tablero</button>
          <button type="button" class="inicio-action secondary" data-go-section="formulario-tarea">Nueva tarea</button>
        </div>
      </div>

      <div class="inicio-resumen" aria-label="Resumen del tablero">
        <article class="inicio-stat">
          <span class="inicio-stat-label">Pendientes</span>
          <strong id="inicio-pending-count">0</strong>
        </article>
        <article class="inicio-stat">
          <span class="inicio-stat-label">En proceso</span>
          <strong id="inicio-progress-count">0</strong>
        </article>
        <article class="inicio-stat">
          <span class="inicio-stat-label">Completadas</span>
          <strong id="inicio-done-count">0</strong>
        </article>
      </div>
    </section>

    <!-- PROYECTOS -->
    <section id="proyectos" class="seccion">
      <div class="section-heading">
        <div>
          <span class="section-kicker">Organización</span>
          <h2 class="titulo">Proyectos</h2>
        </div>
        <div class="section-heading-actions">
          <button type="button" class="project-header-action" id="open-archived-projects" data-go-section="proyectos-archivados">Proyectos Archivados</button>
        </div>
        <p>Centraliza tareas, tableros y próximos avances por proyecto.</p>
      </div>

      <div class="projects-layout">
        <div class="project-create-panel">
          <h3>Nuevo proyecto</h3>
          <form id="form-proyecto">
            <label for="project-name">Nombre</label>
            <input id="project-name" name="name" type="text" placeholder="Ej: Rediseño TaskColab Web" required autocomplete="off">

            <label for="project-description">Descripción</label>
            <textarea id="project-description" name="description" placeholder="Objetivo, alcance o entregables principales"></textarea>

            <div class="project-form-grid">
              <div>
                <label for="project-due-date">Fecha objetivo</label>
                <div class="date-input-wrap">
                  <input id="project-due-date" name="due_date" type="date" lang="es-MX" inputmode="none" min="<?php echo date('Y-m-d'); ?>">
                  <button type="button" class="date-picker-button" aria-label="Abrir calendario" data-date-target="project-due-date">
                    <img src="../../assets/img/icono-calendario.png" alt="">
                  </button>
                </div>
              </div>
              <div>
                <label for="project-color">Color</label>
                <input id="project-color" name="color" type="color" value="#1B5CFF">
              </div>
            </div>

            <button type="submit" class="crear">Crear proyecto</button>
          </form>
        </div>

        <div class="project-list-panel">
          <div class="project-list-header">
            <h3>Espacios activos</h3>
            <span id="projects-count">0 proyectos</span>
          </div>

          <div class="project-control-panel" id="project-control-panel">
            <div class="project-control-heading">
              <div>
                <span class="section-kicker">Proyecto activo</span>
                <h3 id="project-detail-name">Proyecto general</h3>
              </div>
              <span class="project-detail-status" id="project-detail-status">Activo</span>
            </div>
            <p id="project-detail-description">Selecciona un proyecto para revisar su avance y editar su información.</p>

            <div class="project-detail-kpis">
              <div>
                <span>Total</span>
                <strong id="project-detail-total">0</strong>
              </div>
              <div>
                <span>En proceso</span>
                <strong id="project-detail-progress">0</strong>
              </div>
              <div>
                <span>Completadas</span>
                <strong id="project-detail-done">0</strong>
              </div>
              <div>
                <span>Avance</span>
                <strong id="project-detail-percent">0%</strong>
              </div>
            </div>

            <form id="project-edit-form" class="project-edit-form">
              <div class="project-form-grid">
                <div>
                  <label for="edit-project-name">Nombre</label>
                  <input id="edit-project-name" name="name" type="text" required autocomplete="off">
                </div>
                <div>
                  <label for="edit-project-status">Estado</label>
                  <select id="edit-project-status" name="status">
                    <option value="active">Activo</option>
                    <option value="paused">Pausado</option>
                  </select>
                </div>
              </div>
              <label for="edit-project-description">Descripción</label>
              <textarea id="edit-project-description" name="description"></textarea>
              <div class="project-form-grid">
                <div>
                  <label for="edit-project-due-date">Fecha objetivo</label>
                  <div class="date-input-wrap">
                    <input id="edit-project-due-date" name="due_date" type="date" lang="es-MX" inputmode="none" min="<?php echo date('Y-m-d'); ?>">
                    <button type="button" class="date-picker-button" aria-label="Abrir calendario" data-date-target="edit-project-due-date">
                      <img src="../../assets/img/icono-calendario.png" alt="">
                    </button>
                  </div>
                </div>
                <div>
                  <label for="edit-project-color">Color</label>
                  <input id="edit-project-color" name="color" type="color" value="#1B5CFF">
                </div>
              </div>
              <div class="project-control-actions">
                <button type="submit" class="crear">Guardar cambios</button>
                <button type="button" class="project-archive-btn" id="project-archive-btn">Archivar</button>
              </div>
            </form>
          </div>

          <div id="projects-list" class="projects-grid">
            <div class="project-empty">Cargando proyectos...</div>
          </div>
        </div>
      </div>
    </section>

    <!-- PROYECTOS ARCHIVADOS -->
    <section id="proyectos-archivados" class="seccion">
      <div class="section-heading">
        <div>
          <span class="section-kicker">Organización</span>
          <h2 class="titulo">Proyectos Archivados</h2>
        </div>
        <div class="section-heading-actions">
          <button type="button" class="project-header-action" data-go-section="proyectos">Volver a Proyectos</button>
        </div>
        <p>Consulta los espacios archivados y restaura los que vuelvan a estar en uso.</p>
      </div>

      <div class="project-list-panel archived-projects-panel">
        <div class="project-list-header">
          <h3>Archivo de proyectos</h3>
          <span id="archived-projects-count">0 proyectos</span>
        </div>
        <div id="archived-projects-list" class="projects-grid">
          <div class="project-empty">Cargando proyectos archivados...</div>
        </div>
      </div>
    </section>

    <!-- Sección de tableros -->
    <section id="tableros" class="seccion">
      <div class="section-heading">
        <div>
          <span class="section-kicker">Flujo Kanban</span>
          <h2 class="titulo-tableros">Mis tableros</h2>
        </div>
        <p id="board-summary">Cargando tareas del tablero...</p>
      </div>
      <div class="active-project-strip" id="active-project-strip">
        <span>Proyecto activo</span>
        <strong id="active-project-name">Proyecto general</strong>
      </div>
      <div class="contenedor-tableros">
        <div class="columna columna-pending">
          <div class="titulo-columna">
            <div>
              <h3>Pendiente <span class="column-count" data-count-column="pending">0</span></h3>
            </div>
            <button class="add-card" data-seccion="pendiente" aria-label="Crear tarjeta pendiente">+</button>
          </div>
          <div class="tarjetas"></div>
        </div>
        <div class="columna columna-progress">
          <div class="titulo-columna">
            <div>
              <h3>En proceso <span class="column-count" data-count-column="in_progress">0</span></h3>
            </div>
            <button class="add-card" data-seccion="proceso" aria-label="Crear tarjeta en proceso">+</button>
          </div>
          <div class="tarjetas"></div>
        </div>
        <div class="columna columna-done">
          <div class="titulo-columna">
            <div>
              <h3>Completado <span class="column-count" data-count-column="done">0</span></h3>
            </div>
            <button class="add-card" data-seccion="completado" aria-label="Crear tarjeta completada">+</button>
          </div>
          <div class="tarjetas"></div>
        </div>
      </div>
    </section>

    <!-- FORMULARIO AÑADIR TARJETA -->
    <section id="formulario-tarjeta" class="seccion">
      <h2 class="titulo">Mis Tableros</h2>

      <div class="form-tarjeta">
        <h3 id="titulo-form-tarjeta">Añadir Tarjeta - Pendiente</h3>

        <form id="form-tarjeta">
          <label for="titulo-tarjeta">Título de la tarea</label>
          <input type="text" 
                id="titulo-tarjeta" 
                name="titulo-tarjeta"
                placeholder="Ej: Diseñar pantalla de login" 
                required
                autocomplete="off">

          <label for="descripcion-tarjeta">Descripción detallada</label>
          <textarea id="descripcion-tarjeta" 
                    name="descripcion-tarjeta"
                    placeholder="Describe con detalle la tarea a realizar..."></textarea>

          <label for="asignar-tarjeta">Asignar a:</label>
          <select id="asignar-tarjeta" name="asignar-tarjeta">
            <option value="">Sin asignar</option>
            <!-- Se cargarán dinámicamente -->
          </select>

          <label for="prioridad-tarjeta">Prioridad:</label>
          <select id="prioridad-tarjeta" name="prioridad-tarjeta">
            <option value="Media prioridad" selected>Media prioridad</option>
            <option value="Alta prioridad">Alta prioridad</option>
            <option value="Baja prioridad">Baja prioridad</option>
          </select>

          <label for="fecha-tarjeta">Fecha límite:</label>
          <div class="date-input-wrap">
            <input class="date"
                  type="date"
                  id="fecha-tarjeta"
                  name="fecha-tarjeta"
                  lang="es-MX"
                  inputmode="none"
                  min="<?php echo date('Y-m-d'); ?>">
            <button type="button" class="date-picker-button" aria-label="Abrir calendario" data-date-target="fecha-tarjeta">
              <img src="../../assets/img/icono-calendario.png" alt="">
            </button>
          </div>

          <div class="botones">
            <button type="button" class="cancelar">Cancelar</button>
            <button type="submit" class="crear">Crear tarjeta</button>
          </div>
        </form>
      </div>
    </section>

    <!-- Sección de confirmación de eliminación -->
    <section id="eliminarTarjeta" class="seccion">
      <div class="alerta-eliminar">
        <h3 id="tituloAlerta">Eliminar Tarjeta</h3>

        <img id="iconoAlerta" src="../../assets/img/alerta.png" alt="Alerta">

        <p id="textoAlerta">
          ¿Estás seguro de que deseas eliminar esta tarjeta?<br>
          <strong>Esta acción no se puede deshacer.</strong>
        </p>

        <div class="botones-alerta">
          <button id="cancelarEliminar">Cancelar</button>
          <button id="confirmarEliminar">Eliminar</button>
        </div>
      </div>

      
    </section>

    <!-- TAREAS -->
    <section id="tareas" class="seccion">
      <h2 class="titulo-tareas">Tareas</h2>
      <div class="encabezado-tareas">
        <button id="btn-borrar-tareas" class="btn-basura" style="display: none;">
          <img src="../../assets/img/basura.png" alt="Eliminar tareas" />
        </button>

        <button class="btn-azul">Añadir tarea</button>
      </div>

      <div class="tabla-contenedor">
        <table class="tabla-tareas">
          <thead>
            <tr>
              <th> <img src="../../assets/img/seleccion.png" class="icono-completado" alt="Completado"></th>
              <th>Tarea</th>
              <th>Tablero</th>
              <th>Estado</th>
              <th>Fecha límite</th>
              <th><img src="../../assets/img/icono-usuario.png" class="icono-completado" alt="Completado"></th>
            </tr>
          </thead>
          <tbody>
            <!-- LAS FILAS SE CARGARÁN DINÁMICAMENTE CON JAVASCRIPT -->
            <tr>
              <td colspan="6" style="text-align:center; color:#666;">
                Cargando tareas...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- FORMULARIO AÑADIR TAREA -->
    <section id="formulario-tarea" class="seccion">
      <h2 class="titulo">Tareas</h2>

      <div class="form-tarea">
        <h3>Añadir Tarea</h3>

        <form>
          <!-- TÍTULO -->
          <label for="titulo-tarea">Título de la tarea</label>
          <input 
            type="text" 
            id="titulo-tarea"
            name="titulo-tarea"
            placeholder="Ej: Diseñar pantalla de login"
            style="color: white;"
            required
          >

          <!-- DESCRIPCIÓN -->
          <label for="descripcion-tarea">Descripción detallada</label>
          <textarea 
            id="descripcion-tarea"
            name="descripcion-tarea"
            placeholder="Describe con detalle la tarea a realizar..."
            style="color: white;"
          ></textarea>

          <!-- ASIGNAR A -->
          <div class="fila">
            <label for="asignar-a">Asignar a:</label>
            <select id="asignar-a" name="asignar-a" style="color: white;">
              <option value="">Seleccionar usuario</option>
              <!-- Los usuarios ACTIVOS se cargarán dinámicamente -->
            </select>
          </div>

          <!-- ESTADO - VALORES EN INGLÉS -->
          <div class="fila">
              <label for="estado">Estado:</label>
              <select id="estado" name="estado" style="color: white;">
                  <option value="pending">Pendiente</option>
                  <option value="in_progress">En proceso</option>
                  <option value="completed">Completado</option>
              </select>
          </div>

          <!-- PRIORIDAD - VALORES EN INGLÉS -->
          <div class="fila">
              <label for="prioridad">Prioridad:</label>
              <select id="prioridad" name="prioridad" style="color: white;">
                  <option value="low">Baja</option>
                  <option value="medium" selected>Media</option>
                  <option value="high">Alta</option>
              </select>
          </div>

          <!-- FECHA -->
          <div class="fila">
            <label for="fecha">Fecha límite:</label>
            <div class="date-input-wrap">
              <input type="date" id="fecha" name="fecha" lang="es-MX" inputmode="none" min="<?php echo date('Y-m-d'); ?>">
              <button type="button" class="date-picker-button" aria-label="Abrir calendario" data-date-target="fecha">
                <img src="../../assets/img/icono-calendario.png" alt="">
              </button>
            </div>
          </div>

          <!-- BOTONES -->
          <div class="botones">
            <button type="button" class="cancelar">Cancelar</button>
            <button type="submit" class="crear">Añadir tarea</button>
          </div>
        </form>
      </div>
    </section>

    <!-- REPORTES -->
    <div id="reportes" class="seccion">
      <div class="reports-hero">
        <div>
          <span class="section-kicker">Analítica operativa</span>
          <h2 class="titulo-reportes">Reportes</h2>
          <p class="descripcion-reportes">Mide avance, carga del equipo y riesgos próximos con una vista ejecutiva.</p>
        </div>
        <button id="btn-exportar-pdf" class="btn-exportar" type="button">Exportar PDF</button>
      </div>

      <div class="report-kpi-grid">
        <article class="report-kpi-card" data-action="total_tareas">
          <span>Total</span>
          <strong id="report-total-tasks">0</strong>
          <small>Tareas activas</small>
        </article>
        <article class="report-kpi-card accent-green" data-action="completado">
          <span>Completadas</span>
          <strong id="report-completed-tasks">0</strong>
          <small id="report-productivity-label">0% productividad</small>
        </article>
        <article class="report-kpi-card accent-red" data-action="atrasadas">
          <span>Atrasadas</span>
          <strong id="report-overdue-tasks">0</strong>
          <small>Requieren atención</small>
        </article>
        <article class="report-kpi-card accent-amber" data-action="proximas">
          <span>Próximas</span>
          <strong id="report-soon-tasks">0</strong>
          <small>Vencen en 7 días</small>
        </article>
        <article class="report-kpi-card accent-blue" data-action="usuarios">
          <span>Usuarios activos</span>
          <strong id="report-active-users">0</strong>
          <small>Con tareas asignadas</small>
        </article>
      </div>

      <div class="reports-grid">
        <article class="report-panel report-panel-donut">
          <div class="report-panel-head">
            <div>
              <span class="section-kicker">Estado</span>
              <h3>Distribución de tareas</h3>
            </div>
          </div>
          <div class="chart-box">
            <canvas id="report-status-chart" aria-label="Distribución por estado"></canvas>
            <div id="report-status-fallback" class="report-chart-fallback"></div>
          </div>
          <div id="report-status-legend" class="report-legend"></div>
        </article>

        <article class="report-panel">
          <div class="report-panel-head">
            <div>
              <span class="section-kicker">Equipo</span>
              <h3>Tareas por usuario</h3>
            </div>
          </div>
          <div class="chart-box chart-box-bars">
            <canvas id="report-users-chart" aria-label="Tareas por usuario"></canvas>
          </div>
          <div id="report-users-list" class="report-bars-list"></div>
        </article>

        <article class="report-panel report-panel-wide">
          <div class="report-panel-head">
            <div>
              <span class="section-kicker">Ritmo</span>
              <h3>Completadas por semana</h3>
            </div>
          </div>
          <div class="chart-box chart-box-wide">
            <canvas id="report-weekly-chart" aria-label="Tareas completadas por semana"></canvas>
          </div>
          <div id="report-weekly-list" class="report-bars-list"></div>
        </article>

        <article class="report-panel report-panel-wide">
          <div class="report-panel-head">
            <div>
              <span class="section-kicker">Carga</span>
              <h3>Prioridad por vencimiento</h3>
            </div>
          </div>
          <div id="report-priority-heatmap" class="report-heatmap"></div>
        </article>

        <article class="report-panel report-panel-wide report-alerts-panel">
          <div class="report-panel-head">
            <div>
              <span class="section-kicker">Riesgos</span>
              <h3>Alertas de tareas</h3>
            </div>
          </div>
          <div id="report-alerts-table" class="report-alerts-table"></div>
        </article>
      </div>
    </div>

    <!-- CHAT -->
    <section id="chat" class="seccion">
      <div class="section-heading">
        <div>
          <span class="section-kicker">Colaboración</span>
          <h2 class="titulo-seccion">Chat</h2>
        </div>
        <span id="chat-status" class="chat-status">Cargando...</span>
      </div>

      <div class="chat-layout">
        <aside class="chat-sidebar-panel">
          <div class="chat-direct-form">
            <select id="chat-user-select" aria-label="Usuario para chat privado">
              <option value="">Chat privado con...</option>
            </select>
            <button type="button" id="chat-start-direct">Abrir</button>
          </div>

          <div id="chat-conversations-list" class="chat-conversations-list">
            <div class="chat-empty-list">Cargando conversaciones...</div>
          </div>
        </aside>

        <div class="chat-panel">
          <header class="chat-panel-header">
            <div>
              <h3 id="chat-active-title">Chat</h3>
              <p id="chat-active-meta">Selecciona una conversación</p>
            </div>
            <button type="button" id="chat-delete-active" class="chat-delete-active" hidden>Eliminar chat</button>
          </header>

          <div id="chat-empty-state" class="chat-empty-state">
            <strong>Sin conversación activa</strong>
          </div>

          <div id="chat-messages" class="chat-messages"></div>

          <form id="chat-form" class="chat-form" style="display: none;">
            <textarea id="chat-message-input" rows="2" maxlength="4000" placeholder="Escribe un mensaje..." required></textarea>
            <button id="chat-send" type="submit">Enviar</button>
          </form>
        </div>
      </div>
    </section>

    <!-- SECCIÓN USUARIOS -->
    <section id="usuarios" class="seccion">
        <h2 class="titulo-seccion">Usuarios</h2>

        <div class="tabla-contenedor">
            <table class="tabla-tareas">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo electrónico</th>
                        <th>Tareas asignadas</th>
                        <th>Notas</th>
                    </tr>
                </thead>

                <tbody class="tabla-usuarios">
                    <tr data-id="0">
                        <td>Usuario Prueba</td>
                        <td>usuario@gmail.com</td>
                        <td>5</td>
                        <td>Responsable de tareas urgentes</td>
                    </tr>

                    <tr data-id="1">
                        <td>María López</td>
                        <td>maria.lopez@example.com</td>
                        <td>3</td>
                        <td>Apoya en tareas de diseño</td>
                    </tr>

                    <tr data-id="2">
                        <td>Carlos Martínez</td>
                        <td>carlos.martinez@example.com</td>
                        <td>7</td>
                        <td>Encargado del seguimiento semanal</td>
                    </tr>

                    <tr data-id="3">
                        <td>Ana Torres</td>
                        <td>ana.torres@example.com</td>
                        <td>2</td>
                        <td>Nueva integrante del equipo</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- SECCIÓN DETALLES DE USUARIO -->
    <section id="detalleUsuario" class="seccion">
        <h2 class="titulo-seccion">Detalles de usuarios</h2>

        <div class="detalle-caja">
            <div class="detalle-titulo">Detalles</div>

            <div class="detalle-contenido">
                <div class="fila-detalle">
                    <span class="campo">Nombre completo:</span>
                    <span id="detNombre" class="valor"></span>
                </div>

                <div class="fila-detalle">
                    <span class="campo">Correo electrónico:</span>
                    <span id="detCorreo" class="valor"></span>
                </div>

                <div class="fila-detalle">
                    <span class="campo">Tareas asignadas:</span>
                    <span id="detTareas" class="valor"></span>
                </div>

                <div class="fila-detalle">
                    <span class="campo">Estado:</span>
                    <span id="detEstado" class="valor">Activo</span>
                </div>

                <div class="fila-detalle">
                    <span class="campo">Última actualización:</span>
                    <span id="detFecha" class="valor">00/00/0000</span>
                </div>
            </div>

            <div class="detalle-botones">
                <button id="btnVolverUsuario" class="btn-volver">↩</button>
                <button id="btnEditarUsuario" class="btn-editar">Editar</button>
                <!-- Botón de eliminar REMOVIDO - usar perfil para eliminar cuenta -->
            </div>
        </div>
    </section>

    <!-- SECCIÓN EDITAR USUARIO -->
    <section id="editarUsuario" class="seccion">
        <h2 class="titulo-seccion">Editar</h2>

        <div class="detalle-caja">
            <div class="detalle-titulo">Editar</div>

            <div class="detalle-contenido">
                <div class="fila-detalle">
                    <span class="campo">Nombre completo:</span>
                    <input id="editNombre" class="input-editar" type="text" required>
                </div>

                <div class="fila-detalle">
                    <span class="campo">Correo electrónico:</span>
                    <input id="editCorreo" class="input-editar" type="email" required>
                </div>

                <!-- TAREAS ASIGNADAS: READONLY -->
                <div class="fila-detalle">
                    <span class="campo">Tareas asignadas:</span>
                    <input id="editTareas" class="input-editar" type="number" readonly 
                          style="background-color: #f0f0f0; cursor: not-allowed; color: #666;">
                </div>

                <!-- ESTADO: SOLO LECTURA -->
                <div class="fila-detalle">
                    <span class="campo">Estado:</span>
                    <select id="editEstado" class="input-editar" disabled 
                          style="background-color: #f0f0f0; cursor: not-allowed; color: #666;">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <!-- ÚLTIMA ACTUALIZACIÓN: SOLO LECTURA CON FECHA ACTUAL -->
                <div class="fila-detalle">
                    <span class="campo">Última actualización:</span>
                    <input id="editFecha" class="input-editar" type="text" readonly
                          style="background-color: #f0f0f0; cursor: not-allowed; color: #666;">
                </div>
            </div>

            <div class="detalle-botones">
                <button id="btnCancelarEditar" class="btn-volver">↩</button>
                <button id="btnAceptarEditar" class="btn-editar">Aceptar</button>
            </div>
        </div>
    </section>

    <!-- PERFIL - VISTA NORMAL -->
    <section id="perfil" class="seccion">
      <h2 class="titulo-seccion">Perfil</h2>

      <div class="contenedor-perfil-unico">
        <div class="titulo-seccion-perfil">Datos del usuario</div>
        <div class="linea-separadora"></div>
        
        <div class="contenido-perfil-unico">
          <!-- Foto de perfil -->
          <div class="foto-perfil-container">
            <div class="foto-perfil" id="avatarPerfil">
              <!-- La inicial o imagen se cargará aquí dinámicamente -->
              <?php echo htmlspecialchars($initial, ENT_QUOTES); ?>
            </div>
            <button id="btnSubirAvatar" class="link-importar-foto">Importar foto</button>
            <input type="file" id="inputAvatar" accept="image/*" style="display: none;">
          </div>

          <!-- Información del usuario -->
          <div class="info-usuario">
            <div class="campo-contenedor">
              <label class="campo-perfil-label">Nombre completo</label>
              <div class="input-y-boton">
                <input class="input-perfil nombre-usuario" type="text" value="<?php echo htmlspecialchars($userName, ENT_QUOTES); ?>" readonly>
                <button class="btn-cambiar" data-campo="nombre">Cambiar nombre</button>
              </div>
            </div>

            <div class="campo-contenedor">
              <label class="campo-perfil-label">Correo electrónico</label>
              <div class="input-y-boton">
                <input class="input-perfil correo-usuario" type="email" value="<?php echo htmlspecialchars($_SESSION['user']['email'] ?? '', ENT_QUOTES); ?>" readonly>
                <button class="btn-cambiar" data-campo="correo">Cambiar correo</button>
              </div>
            </div>

            <div class="campo-contenedor">
              <label class="campo-perfil-label">Contraseña</label>
              <div class="input-y-boton">
                <input class="input-perfil" type="password" value="••••••••" readonly>
                <button class="btn-cambiar" data-campo="contrasena">Cambiar contraseña</button>
              </div>
            </div>
          </div>

          <div class="linea-separadora"></div>

          <!-- Actividad del usuario -->
          <div class="seccion-actividad">
            <h3 class="subtitulo-perfil">Actividad del usuario</h3>
            <table id="tablaActividad" class="tabla-actividad">
              <thead>
                <tr>
                  <th>Tarea</th>
                  <th>Estado</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="3" style="text-align: center;">Cargando actividades...</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="linea-separadora"></div>

          <!-- Eliminación de perfil -->
          <div class="seccion-eliminar">
            <h3 class="subtitulo-perfil">Eliminación de perfil</h3>
            <p class="advertencia-eliminar">
              Elimina permanentemente la cuenta y quita el acceso a todos los espacios de trabajo.
            </p>
            <button id="btnEliminarCuenta" class="link-eliminar-cuenta">Eliminar mi cuenta</button>
          </div>
        </div>
      </div>
    </section>

    <!-- EDITAR NOMBRE -->
    <section id="editar-nombre" class="seccion">
      <h2 class="titulo-seccion">Perfil</h2>
      <div class="contenedor-perfil-unico">
        <div class="titulo-seccion-perfil">Editar nombre</div>
        <div class="contenido-perfil-unico">
          <form class="form-edicion-perfil">
            <label class="label-edicion">Nuevo nombre completo</label>
            <input type="text" class="input-edicion" placeholder="Tu nombre actual" required>
            
            <p class="confirmacion-edicion">¿Confirmas los cambios realizados?</p>
            
            <div class="botones-edicion">
              <button type="button" class="btn-cancelar-edicion">Cancelar</button>
              <button type="submit" class="btn-confirmar-edicion">Confirmar</button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- EDITAR CORREO -->
    <section id="editar-correo" class="seccion">
      <h2 class="titulo-seccion">Perfil</h2>
      <div class="contenedor-perfil-unico">
        <div class="titulo-seccion-perfil">Editar correo</div>
        <div class="contenido-perfil-unico">
          <form class="form-edicion-perfil">
            <label class="label-edicion">Nuevo correo electrónico</label>
            <input type="email" id="nuevo-correo" class="input-edicion" placeholder="Tu correo actual" required>
            
            <label class="label-edicion">Confirmar correo electrónico</label>
            <input type="email" id="confirmar-correo" class="input-edicion" placeholder="Confirma tu correo" required>
            
            <p class="confirmacion-edicion">¿Confirmas los cambios realizados?</p>
            
            <div class="botones-edicion">
              <button type="button" class="btn-cancelar-edicion">Cancelar</button>
              <button type="submit" class="btn-confirmar-edicion">Confirmar</button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- EDITAR CONTRASEÑA -->
    <section id="editar-contrasena" class="seccion">
      <h2 class="titulo-seccion">Perfil</h2>
      <div class="contenedor-perfil-unico">
        <div class="titulo-seccion-perfil">Editar contraseña</div>
        <div class="contenido-perfil-unico">
          <form class="form-edicion-perfil">
            <!-- AGREGAR ESTE CAMPO -->
            <label class="label-edicion">Contraseña actual</label>
            <input type="password" id="currentPassword" class="input-edicion" placeholder="Ingresa tu contraseña actual" required>
            
            <label class="label-edicion">Nueva contraseña</label>
            <input type="password" id="newPassword" class="input-edicion" placeholder="Escribe tu nueva contraseña..." required minlength="8">
            
            <label class="label-edicion">Confirmar contraseña</label>
            <input type="password" id="confirmPassword" class="input-edicion" placeholder="Confirma tu nueva contraseña..." required minlength="8">
            
            <p class="confirmacion-edicion">¿Confirmas los cambios realizados?</p>
            
            <div class="botones-edicion">
              <button type="button" class="btn-cancelar-edicion">Cancelar</button>
              <button type="submit" class="btn-confirmar-edicion">Confirmar</button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- ALERTA CAMBIAR FOTO -->
    <section id="alerta-cambiar-foto" class="seccion">
      <div class="alerta-eliminar">
        <h3>Cambiar foto de perfil</h3>
        <img src="../../assets/img/descarga-icono.png" alt="Icono imagen" class="icono-imagen-alerta">
        <p>Selecciona una imagen para cambiar su foto de perfil</p>
        <div class="botones-alerta">
          <button id="cancelarCambiarFoto" class="btn-cancelar-foto">Cancelar</button>
          <button id="subirCambiarFoto" class="btn-subir-foto">Subir</button>
        </div>
      </div>
    </section>

    <!-- ALERTA CONFIRMACIÓN (modal) -->
    <div class="confirmation-modal" id="confirmationModal" style="display: none;">
      <div class="modal-content">
        <h3>Importando foto</h3>
        <p>¿Confirma actualizar su foto de perfil?</p>
        <img src="../../assets/img/perfil.png">
        <div class="modal-actions">
          <button class="btn-cancel" id="cancelBtn">Cancelar</button>
          <button class="btn-confirm" id="confirmBtn">Confirmar</button>
        </div>
      </div>
    </div>

    <!-- ADMIN - USUARIOS ADMINISTRADORES -->
    <section id="admin" class="seccion">
      <h2 class="titulo-seccion">Usuarios - Admin</h2>

      <div class="tabla-contenedor">
        <table class="tabla-tareas tabla-admin">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Correo electrónico</th>
              <th>Tareas asignadas</th>
              <th>Notas</th>
              <th class="acciones-header">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <!-- Usuarios existentes -->
            <tr>
              <td>Usuario Prueba</td>
              <td>usuario@gmail.com</td>
              <td>8</td>
              <td>No trabaja</td>
              <td class="acciones-celda">
                <button class="btn-editar-admin">
                  <img src="../../assets/img/editar.png" alt="Editar">
                </button>
                <button class="btn-eliminar-admin">
                  <img src="../../assets/img/eliminar.png" alt="Eliminar">
                </button>
              </td>
            </tr>
            <tr>
              <td>Renéry Lucero</td>
              <td>renl_23@alu.uabcs.mx</td>
              <td>2</td>
              <td>Tareas bien hechas</td>
              <td class="acciones-celda">
                <button class="btn-editar-admin">
                  <img src="../../assets/img/editar.png" alt="Editar">
                </button>
                <button class="btn-eliminar-admin">
                  <img src="../../assets/img/eliminar.png" alt="Eliminar">
                </button>
              </td>
            </tr>
            <tr>
              <td>Keyra Yarleky</td>
              <td>keyra_23@alu.uabcs.mx</td>
              <td>'D'</td>
              <td>Picha un agnachile</td>
              <td class="acciones-celda">
                <button class="btn-editar-admin">
                  <img src="../../assets/img/editar.png" alt="Editar">
                </button>
                <button class="btn-eliminar-admin">
                  <img src="../../assets/img/eliminar.png" alt="Eliminar">
                </button>
              </td>
            </tr>
            
            <!-- SOLO UNA fila para agregar usuario -->
            <tr class="fila-agregar" data-action="añadir-usuario">
              <td>Añadir</td>
              <td>Ejemplo@correo.com</td>
              <td>Añadir</td>
              <td>Añadir</td>
              <td class="acciones-celda">
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- FORMULARIO AÑADIR USUARIO ADMIN -->
    <section id="formulario-admin" class="seccion">
      <h2 class="titulo-seccion">Usuarios - Admin</h2>

      <div class="form-tarjeta form-admin">
        <h3>Añadir usuario</h3>

        <form id="form-usuario-admin">
          <label>Nombre completo</label>
          <input type="text" name="name" placeholder="Jonathan Giovanni Soto Muñoz" required>

          <label>Correo electrónico</label>
          <input type="email" name="email" placeholder="jsoto@uabcs.mx" required>

          <label style="margin-bottom: 8px;">Tareas asignadas</label>
          <div class="tasks-checkbox-container"></div>

          <label>Notas</label>
          <textarea name="notes" placeholder="Se la rifaron con el diseño"></textarea>

          <div class="botones">
            <button type="button" class="cancelar">Cancelar</button>
            <button type="submit" class="crear">Añadir usuario</button>
          </div>
        </form>
      </div>
    </section>

    <!-- FORMULARIO EDITAR USUARIO ADMIN -->
    <section id="editar-usuario-admin" class="seccion">
      <h2 class="titulo-seccion">Usuarios - Admin</h2>

      <div class="form-tarjeta form-admin">
        <h3>Editar usuario</h3>

        <form id="form-editar-usuario-admin">
          <label>Nombre completo</label>
          <input type="text" id="edit-nombre-completo" name="name" placeholder="Nombre completo" required>

          <label>Correo electrónico</label>
          <input type="email" id="edit-correo-electronico" name="email" placeholder="correo@ejemplo.com" required>

          <label style="margin-bottom: 8px;">Tareas asignadas</label>
          <div class="tasks-checkbox-container"></div>

          <label>Notas</label>
          <textarea id="edit-notas" name="notes" placeholder="Notas del usuario"></textarea>

          <div class="botones">
            <button type="button" class="cancelar">Cancelar</button>
            <button type="submit" class="crear">Editar usuario</button>
          </div>
        </form>
      </div>
    </section>

    <section id="cerrarSesion" class="seccion">
      <div class="alerta-eliminar">
        <h3>Cerrar Sesión</h3>
        <img src="../../assets/img/alerta.png" alt="Alerta de cierre">
        <p>Por favor confirme que desea cerrar sesión.</p>
        <div class="botones-alerta">
          <button id="cancelarCerrarSesion">Cancelar</button>
          <button id="confirmarCerrarSesion">Confirmar</button>
        </div>
      </div>
    </section>
  </main>

  <!-- FOOTER -->
  <footer class="footer">
    <p>© 2025 Task<span class="task-black">Colab</span> - Todos los derechos reservados.</p>
  </footer>
  
  <script>
      // Configuración de API_BASE
      const apiBase = '<?php echo '/PROYECTO_GESTOR_TAREAS/assets/app/endpoints'; ?>';
      console.log('API_BASE configurado:', apiBase);
      
      // Avatar URL
      <?php 
      $avatarUrl = $_SESSION['user']['avatar_url'] ?? null;
      if ($avatarUrl): 
      ?>
      const avatarUrl = '<?php echo $avatarUrl; ?>';
      console.log('Avatar URL:', avatarUrl);
      <?php endif; ?>
      
      window.API_BASE = apiBase;
  </script>

  <script src="../../assets/javascript/menu.js?v=20260520-1" defer></script>
  <script src="../../assets/javascript/admin.js" defer></script>
  <script src="../../assets/javascript/users.js?v=20260516-3" defer></script>
  <script src="../../assets/javascript/projects.js?v=20260525-1" defer></script>
  <script src="../../assets/javascript/tasks.js?v=20260520-1" defer></script>
  <script src="../../assets/javascript/boards.js?v=20260520-1" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
  <script src="../../assets/javascript/reports.js?v=20260520-1" defer></script>
  <script src="../../assets/javascript/chat.js" defer></script>
  <script src="../../assets/javascript/profile.js?v=20260516-3" defer></script>
</body>
</html>
