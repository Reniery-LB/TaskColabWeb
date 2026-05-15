<?php include __DIR__ . '/../../assets/app/header.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestor de Tareas</title>
  <link rel="stylesheet" href="../../assets/styles/pantallas.css">
</head>
<body>
  <!-- CONTENIDO PRINCIPAL -->
  <main class="main-content">
    <!-- INICIO -->
    <section id="inicio" class="seccion activa">
      <h2>Bienvenido a <span class="task-blue">Task</span>Colab</h2>
      <?php
      $userName = $_SESSION['user']['name'] ?? 'Invitado';
      ?>
      <p>¡Bienvenido a tu espacio de trabajo, <?php echo htmlspecialchars($userName, ENT_QUOTES); ?>!</p>
    </section>

    <!-- Sección de tableros -->
    <section id="tableros" class="seccion">
      <h2 class="titulo-tableros">Mis tableros</h2>
      <div class="contenedor-tableros">
        <div class="columna">
          <div class="titulo-columna">
            <h3>Pendiente</h3>
            <button class="add-card" data-seccion="pendiente">+</button>
          </div>
          <div class="tarjetas"></div>
        </div>
        <div class="columna">
          <div class="titulo-columna">
            <h3>En proceso</h3>
            <button class="add-card" data-seccion="proceso">+</button>
          </div>
          <div class="tarjetas"></div>
        </div>
        <div class="columna">
          <div class="titulo-columna">
            <h3>Completado</h3>
            <button class="add-card" data-seccion="completado">+</button>
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
          <input class="date" 
                type="date" 
                id="fecha-tarjeta" 
                name="fecha-tarjeta">

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
              <th>Fecha</th>
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
            <input type="date" id="fecha" name="fecha" style="color: white;">
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
      <h2 class="titulo-reportes">Reportes</h2>
      <div class="descripcion-exportar">
          <p class="descripcion-reportes">Ver métricas y estadísticas generales del sistema.</p>
          <button id="btn-exportar-pdf" class="btn-exportar">Exportar PDF</button>
      </div>

      <div class="tarjetas-metricas">
        <div class="tarjeta-metrica" data-action="total_tareas">
          <h3>Total de tareas</h3>
          <p>0</p>
        </div>
        <div class="tarjeta-metrica" data-action="pendiente">
          <h3>Pendiente</h3>
          <p>0</p>
        </div>
        <div class="tarjeta-metrica" data-action="en_proceso">
          <h3>En proceso</h3>
          <p>0</p>
        </div>
        <div class="tarjeta-metrica" data-action="completado">
          <h3>Completado</h3>
          <p>0</p>
        </div>
      </div>

      <div class="contenedor-barras">
        <div class="recuadro">
          <h3>Progreso por tablero</h3>
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
        </div>

        <div class="recuadro">
          <h3>Usuarios activos</h3>
          <div class="barra">
            <span>Zahir Fernando</span>
            <div class="barra-contenido">
              <div class="barra-progreso" style="width: 0%"></div>
            </div>
            <span class="porcentaje">0%</span>
          </div>
          <div class="barra">
            <span>Reniery Lucero</span>
            <div class="barra-contenido">
              <div class="barra-progreso" style="width: 0%"></div>
            </div>
            <span class="porcentaje">0%</span>
          </div>
          <div class="barra">
            <span>Keyra Yariely</span>
            <div class="barra-contenido">
              <div class="barra-progreso" style="width: 0%"></div>
            </div>
            <span class="porcentaje">0%</span>
          </div>
        </div>
      </div>

      <div class="recuadro tareas-atrasadas">
        <h3>Tareas atrasadas</h3>
        <div class="contenido-atrasadas">
          <p>Cargando tareas atrasadas...</p>
        </div>
      </div>
    </div>

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
                        <td>Zahir Fernando</td>
                        <td>zdiaz_23@alu.uabcs.mx</td>
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
            <input type="password" id="newPassword" class="input-edicion" placeholder="Escribe tu nueva contraseña..." required>
            
            <label class="label-edicion">Confirmar contraseña</label>
            <input type="password" id="confirmPassword" class="input-edicion" placeholder="Confirma tu nueva contraseña..." required>
            
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
              <td>Zahir Fernando</td>
              <td>zd@z.23@alu.uabcs.mx</td>
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

  <script src="../../assets/javascript/menu.js" defer></script>
  <script src="../../assets/javascript/admin.js" defer></script>
  <script src="../../assets/javascript/users.js" defer></script>
  <script src="../../assets/javascript/tasks.js" defer></script>
  <script src="../../assets/javascript/boards.js" defer></script>
  <script src="../../assets/javascript/reports.js" defer></script>
  <script src="../../assets/javascript/profile.js" defer></script>
</body>
</html>