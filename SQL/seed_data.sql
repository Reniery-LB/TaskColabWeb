-- ============================================================
--  TASKCOLAB  –  Datos de Prueba (seed_data.sql)
--  Importar DESPUÉS de haber creado el esquema completo.
--  Orden: roles → users → projects → boards → tasks → ...
-- ============================================================
USE taskcolab;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------
-- ROLES
-- ----------------------------------------------------------------
INSERT IGNORE INTO roles (id, name, descripcion) VALUES
  (1, 'admin',  'Acceso completo al sistema'),
  (2, 'member', 'Colaborador estándar'),
  (3, 'viewer', 'Solo lectura');

-- ----------------------------------------------------------------
-- USUARIOS  (contraseñas hasheadas con password_hash() de PHP)
--   admin@gmail.com  → Admin1234
--   usuario@gmail.com → Usuario1234
--   + 8 usuarios extra con bcrypt genérico de ejemplo
-- ----------------------------------------------------------------
INSERT IGNORE INTO users
  (id, name, email, password_hash, is_admin, notes, created_at, last_login, is_active)
VALUES
  (1,  'Administrador TaskColab',
       'admin@gmail.com',
       '$2y$10$leCQCcMRRGtttQAcpB6FyODO3olX8J/CmYBEDn9vlL10NfNU6RGd6', -- Admin1234
       1, 'Cuenta principal del sistema.',
       '2026-01-10 08:00:00', '2026-05-28 09:00:00', 1),

  (2,  'Usuario Prueba',
       'usuario@gmail.com',
       '$2y$10$ikq5irh/E/c1o7IwJsplK.7hLbhofUvnNuZecAnDcAB2qREjW86kq', -- Usuario1234
       0, 'Cuenta de prueba estándar.',
       '2026-01-11 09:00:00', '2026-05-28 10:00:00', 1),

  (3,  'Keyra Grijalva',
       'keyra@taskcolab.mx',
       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm',
       0, 'Diseñadora Frontend. Experta en CSS y UX.',
       '2026-01-12 10:00:00', '2026-05-27 14:30:00', 1),

  (4,  'Reniery Lucero',
       'reniery@taskcolab.mx',
       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm',
       0, 'Backend Developer. Especialista en PHP y MySQL.',
       '2026-01-12 10:30:00', '2026-05-28 08:45:00', 1),

  (5,  'Miguel Ángel Torres',
       'miguel@taskcolab.mx',
       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm',
       0, 'QA Tester. Encargado de pruebas funcionales y regresión.',
       '2026-01-15 11:00:00', '2026-05-27 17:00:00', 1),

  (6,  'Sofía Ramírez',
       'sofia@taskcolab.mx',
       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm',
       0, 'Project Manager. Coordina equipos y entregables.',
       '2026-01-16 09:00:00', '2026-05-26 16:00:00', 1),

  (7,  'Carlos Mendoza',
       'carlos@taskcolab.mx',
       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm',
       0, 'DevOps. Administra servidores y pipelines de despliegue.',
       '2026-01-20 08:00:00', '2026-05-25 10:00:00', 1),

  (8,  'Valentina Cruz',
       'valentina@taskcolab.mx',
       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm',
       0, 'Diseñadora UI/UX. Prototipado en Figma.',
       '2026-02-01 09:00:00', '2026-05-24 12:00:00', 1),

  (9,  'Jorge Ibáñez',
       'jorge@taskcolab.mx',
       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm',
       0, 'Desarrollador mobile. Android / iOS.',
       '2026-02-05 10:00:00', '2026-05-20 09:00:00', 1),

  (10, 'Daniela Flores',
       'daniela@taskcolab.mx',
       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm',
       0, 'Analista de datos. Reportes y métricas de negocio.',
       '2026-02-10 11:00:00', '2026-05-22 15:00:00', 1);

-- ----------------------------------------------------------------
-- PROYECTOS
-- ----------------------------------------------------------------
INSERT IGNORE INTO projects
  (id, name, description, owner_id, status, color, start_date, due_date, created_at)
VALUES
  (1, 'Rediseño TaskColab Web',
     'Modernización total de la interfaz pública y el panel de gestión. Incluye nuevas secciones de Proyectos y Chat.',
     1, 'active', '#1B5CFF', '2026-01-15', '2026-06-30', '2026-01-15 08:00:00'),

  (2, 'App Móvil TaskColab',
     'Desarrollo de la aplicación nativa para Android e iOS con sincronización en tiempo real.',
     1, 'active', '#7C3AED', '2026-02-01', '2026-08-31', '2026-02-01 09:00:00'),

  (3, 'Integración de Reportes PDF',
     'Mejorar el módulo de reportes con exportación avanzada, filtros por proyecto y gráficas dinámicas.',
     6, 'active', '#059669', '2026-03-01', '2026-05-31', '2026-03-01 10:00:00'),

  (4, 'Módulo de Notificaciones',
     'Sistema de notificaciones en tiempo real por correo y push. Integración con eventos de tablero y chat.',
     4, 'paused', '#D97706', '2026-03-15', '2026-07-15', '2026-03-15 09:00:00'),

  (5, 'API REST v2',
     'Refactorización de los endpoints PHP a una API RESTful con autenticación JWT y documentación Swagger.',
     4, 'active', '#DC2626', '2026-04-01', '2026-09-30', '2026-04-01 08:30:00'),

  (6, 'Onboarding de Nuevos Clientes',
     'Flujo de bienvenida, tutoriales interactivos y correos automáticos para usuarios recién registrados.',
     6, 'active', '#0891B2', '2026-04-15', '2026-06-15', '2026-04-15 10:00:00'),

  (7, 'Migración a Servidor de Producción',
     'Mover el entorno de XAMPP a InfinityFree y luego a VPS propio con SSL y backups automáticos.',
     7, 'archived', '#6B7280', '2026-01-01', '2026-04-01', '2026-01-01 08:00:00');

-- ----------------------------------------------------------------
-- MIEMBROS DE PROYECTO
-- ----------------------------------------------------------------
INSERT IGNORE INTO project_members (project_id, user_id, role_in_project, joined_at) VALUES
  (1, 1, 'owner',  '2026-01-15 08:00:00'),
  (1, 3, 'member', '2026-01-15 08:05:00'),
  (1, 4, 'member', '2026-01-15 08:05:00'),
  (1, 8, 'member', '2026-01-16 09:00:00'),
  (1, 2, 'member', '2026-01-17 10:00:00'),

  (2, 1, 'owner',  '2026-02-01 09:00:00'),
  (2, 9, 'member', '2026-02-01 09:05:00'),
  (2, 4, 'member', '2026-02-02 10:00:00'),
  (2, 5, 'member', '2026-02-03 11:00:00'),

  (3, 6, 'owner',  '2026-03-01 10:00:00'),
  (3, 1, 'member', '2026-03-01 10:05:00'),
  (3, 10, 'member','2026-03-02 09:00:00'),
  (3, 4, 'member', '2026-03-02 09:30:00'),

  (4, 4, 'owner',  '2026-03-15 09:00:00'),
  (4, 1, 'member', '2026-03-15 09:05:00'),
  (4, 7, 'member', '2026-03-16 10:00:00'),

  (5, 4, 'owner',  '2026-04-01 08:30:00'),
  (5, 1, 'member', '2026-04-01 08:35:00'),
  (5, 7, 'member', '2026-04-02 09:00:00'),
  (5, 2, 'member', '2026-04-03 10:00:00'),

  (6, 6, 'owner',  '2026-04-15 10:00:00'),
  (6, 1, 'member', '2026-04-15 10:05:00'),
  (6, 3, 'member', '2026-04-16 09:00:00'),
  (6, 8, 'member', '2026-04-16 09:30:00'),

  (7, 7, 'owner',  '2026-01-01 08:00:00'),
  (7, 1, 'member', '2026-01-01 08:05:00');

-- ----------------------------------------------------------------
-- TABLEROS  (vinculados a proyectos via project_id)
-- ----------------------------------------------------------------
INSERT IGNORE INTO boards
  (id, project_id, owner_id, title, description, visibility, color, created_at)
VALUES
  (1, 1, 1, 'Sprint 1 – Diseño UI', 'Wireframes, paleta de colores y componentes base del rediseño.', 'private', '#1B5CFF', '2026-01-16 09:00:00'),
  (2, 1, 1, 'Sprint 2 – Módulo Proyectos', 'Implementación del CRUD de proyectos y panel de KPIs.', 'private', '#1B5CFF', '2026-02-01 09:00:00'),
  (3, 1, 4, 'Sprint 3 – Chat', 'Desarrollo del sistema de mensajería por proyecto y directo.', 'private', '#1B5CFF', '2026-03-01 09:00:00'),
  (4, 2, 1, 'Android MVP', 'Primera versión funcional de la app Android.', 'private', '#7C3AED', '2026-02-05 10:00:00'),
  (5, 2, 9, 'iOS MVP', 'Primera versión funcional de la app iOS.', 'private', '#7C3AED', '2026-02-10 11:00:00'),
  (6, 3, 6, 'Reportes v2', 'Rediseño del módulo de reportes con PDF avanzado.', 'private', '#059669', '2026-03-05 08:00:00'),
  (7, 5, 4, 'API REST – Diseño', 'Definición de endpoints, contratos y autenticación JWT.', 'private', '#DC2626', '2026-04-05 09:00:00'),
  (8, 5, 4, 'API REST – Desarrollo', 'Codificación y pruebas unitarias de los endpoints.', 'private', '#DC2626', '2026-04-20 09:00:00'),
  (9, 6, 6, 'Onboarding – Flujo', 'Diseño del flujo de bienvenida e interacción con el usuario.', 'private', '#0891B2', '2026-04-16 10:00:00'),
  (10, NULL, 1, 'Tablero General',  'Tablero sin proyecto para tareas sueltas del equipo.', 'private', '#374151', '2026-01-10 08:00:00');

-- ----------------------------------------------------------------
-- MIEMBROS DE TABLERO
-- ----------------------------------------------------------------
INSERT IGNORE INTO board_members (board_id, user_id, role_in_board, joined_at) VALUES
  (1, 1, 'owner',  '2026-01-16 09:00:00'),
  (1, 3, 'member', '2026-01-16 09:05:00'),
  (1, 8, 'member', '2026-01-17 10:00:00'),
  (2, 1, 'owner',  '2026-02-01 09:00:00'),
  (2, 4, 'member', '2026-02-01 09:05:00'),
  (2, 2, 'member', '2026-02-02 10:00:00'),
  (3, 1, 'owner',  '2026-03-01 09:00:00'),
  (3, 4, 'member', '2026-03-01 09:05:00'),
  (4, 1, 'owner',  '2026-02-05 10:00:00'),
  (4, 9, 'member', '2026-02-05 10:05:00'),
  (5, 9, 'owner',  '2026-02-10 11:00:00'),
  (6, 6, 'owner',  '2026-03-05 08:00:00'),
  (6, 10,'member', '2026-03-06 09:00:00'),
  (7, 4, 'owner',  '2026-04-05 09:00:00'),
  (7, 7, 'member', '2026-04-05 09:05:00'),
  (8, 4, 'owner',  '2026-04-20 09:00:00'),
  (9, 6, 'owner',  '2026-04-16 10:00:00'),
  (10,1, 'owner',  '2026-01-10 08:00:00'),
  (10,2, 'member', '2026-01-11 09:00:00');

-- ----------------------------------------------------------------
-- TAGS
-- ----------------------------------------------------------------
INSERT IGNORE INTO tags (id, name, color) VALUES
  (1, 'Backend',    '#1B5CFF'),
  (2, 'Frontend',   '#7C3AED'),
  (3, 'Diseño',     '#EC4899'),
  (4, 'QA',         '#059669'),
  (5, 'DevOps',     '#D97706'),
  (6, 'Urgente',    '#DC2626'),
  (7, 'Mejora',     '#0891B2'),
  (8, 'Bug',        '#EF4444'),
  (9, 'Chat',       '#8B5CF6'),
  (10,'Proyecto',   '#10B981');

-- ----------------------------------------------------------------
-- TASKS  (Board 1 – Sprint 1 Diseño)
-- ----------------------------------------------------------------
INSERT IGNORE INTO tasks
  (id, board_id, title, description, status, priority, due_date, created_by, created_at, position, column_created)
VALUES
  (1,  1, 'Definir paleta de colores',         'Seleccionar 5 colores principales y sus variantes para el sistema de diseño.', 'done',        'high',   '2026-01-25', 1, '2026-01-16 09:00:00', 1, 'pending'),
  (2,  1, 'Diseñar navbar responsivo',          'Crear el menú de navegación adaptable para móvil, tablet y escritorio.',        'done',        'high',   '2026-01-28', 3, '2026-01-17 10:00:00', 2, 'pending'),
  (3,  1, 'Wireframes de pantalla principal',   'Bocetos de alta fidelidad de la vista landing page e inicio de sesión.',        'done',        'medium', '2026-01-30', 8, '2026-01-18 11:00:00', 3, 'pending'),
  (4,  1, 'Componente de tarjeta de tarea',     'Diseñar y codificar el componente reutilizable de tarjeta Kanban.',             'in_progress', 'medium', '2026-02-05', 3, '2026-01-20 09:00:00', 4, 'in_progress'),
  (5,  1, 'Iconografía del sistema',            'Definir librería de iconos SVG y png para toda la app.',                        'pending',     'low',    '2026-02-10', 8, '2026-01-22 14:00:00', 5, 'pending'),

-- TASKS  (Board 2 – Sprint 2 Módulo Proyectos)
  (6,  2, 'Crear tabla projects en BD',         'Diseñar y migrar la tabla projects con sus relaciones y campos necesarios.',    'done',        'high',   '2026-02-10', 4, '2026-02-01 09:00:00', 1, 'pending'),
  (7,  2, 'Endpoint GET /projects',             'API que retorna los proyectos activos del usuario autenticado.',                 'done',        'high',   '2026-02-12', 4, '2026-02-02 10:00:00', 2, 'pending'),
  (8,  2, 'Endpoint POST /projects',            'Crear proyecto con nombre, descripción, color y fecha objetivo.',               'done',        'high',   '2026-02-14', 4, '2026-02-03 11:00:00', 3, 'pending'),
  (9,  2, 'Panel de KPIs del proyecto',         'Mostrar total, en proceso, completadas y porcentaje de avance en tiempo real.', 'done',        'high',   '2026-02-20', 4, '2026-02-05 09:00:00', 4, 'pending'),
  (10, 2, 'Archivar y restaurar proyectos',     'Lógica de archivado con sección de proyectos archivados y botón de restaurar.',  'in_progress', 'medium', '2026-03-01', 4, '2026-02-10 10:00:00', 5, 'in_progress'),
  (11, 2, 'Vincular tableros a proyectos',      'Agregar project_id a la tabla boards y filtrar tableros por proyecto activo.',   'in_progress', 'medium', '2026-03-05', 2, '2026-02-12 11:00:00', 6, 'in_progress'),
  (12, 2, 'UI de selección de proyecto activo', 'Strip visual en la sección de tableros que muestra el proyecto activo actual.', 'pending',     'low',    '2026-03-10', 3, '2026-02-15 09:00:00', 7, 'pending'),

-- TASKS  (Board 3 – Sprint 3 Chat)
  (13, 3, 'Crear tablas del chat en BD',        'Diseñar conversations, messages, conversation_members y message_reads.',        'done',        'high',   '2026-03-10', 4, '2026-03-01 09:00:00', 1, 'pending'),
  (14, 3, 'Endpoint GET /conversations',        'Listar conversaciones del usuario con último mensaje y contador de no leídos.',  'done',        'high',   '2026-03-12', 4, '2026-03-02 10:00:00', 2, 'pending'),
  (15, 3, 'Endpoint POST /messages',            'Enviar mensaje a una conversación existente.',                                  'done',        'high',   '2026-03-14', 4, '2026-03-03 11:00:00', 3, 'pending'),
  (16, 3, 'Panel de chat en sidebar',           'UI del panel lateral de chat con lista de conversaciones y vista de mensajes.',  'done',        'high',   '2026-03-20', 3, '2026-03-05 09:00:00', 4, 'pending'),
  (17, 3, 'Chat de proyecto automático',        'Crear conversación de tipo project al crear un proyecto nuevo.',                 'in_progress', 'medium', '2026-04-01', 4, '2026-03-10 10:00:00', 5, 'in_progress'),
  (18, 3, 'Chat directo entre usuarios',        'Abrir conversación directa desde la lista de usuarios o el perfil.',            'in_progress', 'medium', '2026-04-05', 2, '2026-03-12 11:00:00', 6, 'in_progress'),
  (19, 3, 'Notificaciones de mensajes',         'Mostrar badge con contador de mensajes no leídos en el botón de Chats.',        'pending',     'low',    '2026-04-15', 4, '2026-03-15 09:00:00', 7, 'pending'),
  (20, 3, 'Scroll automático en mensajes',      'Hacer scroll al último mensaje al abrir una conversación o recibir uno nuevo.', 'pending',     'low',    '2026-04-20', 3, '2026-03-18 10:00:00', 8, 'pending'),

-- TASKS  (Board 4 – Android MVP)
  (21, 4, 'Configurar proyecto Android Studio', 'Inicializar repo, Gradle, dependencias y estructura de paquetes.',              'done',        'high',   '2026-02-10', 9, '2026-02-05 10:00:00', 1, 'pending'),
  (22, 4, 'Pantalla de login',                  'Formulario de autenticación con validaciones y conexión a la API.',             'done',        'high',   '2026-02-20', 9, '2026-02-07 11:00:00', 2, 'pending'),
  (23, 4, 'Módulo de tableros Kanban',          'Vista de tablero con columnas deslizables y tarjetas de tarea.',                'in_progress', 'high',   '2026-03-15', 9, '2026-02-12 09:00:00', 3, 'in_progress'),
  (24, 4, 'Push notifications',                 'Integrar Firebase Cloud Messaging para notificaciones de nuevas tareas.',        'pending',     'medium', '2026-04-30', 9, '2026-02-20 10:00:00', 4, 'pending'),

-- TASKS  (Board 6 – Reportes v2)
  (25, 6, 'Diseño del PDF con dompdf',          'Maquetar el template PDF con logo, gráficas y tablas de estadísticas.',         'done',        'high',   '2026-03-15', 6, '2026-03-05 08:00:00', 1, 'pending'),
  (26, 6, 'Filtros por proyecto en reportes',   'Agregar selector de proyecto al dashboard de reportes y filtrar datos.',        'done',        'high',   '2026-03-20', 10,'2026-03-07 09:00:00', 2, 'pending'),
  (27, 6, 'Gráfica de barras por usuario',      'Implementar gráfica Chart.js con tareas completadas por usuario.',              'in_progress', 'medium', '2026-04-10', 10,'2026-03-12 10:00:00', 3, 'in_progress'),
  (28, 6, 'Exportar reporte filtrado a PDF',    'Que el PDF respete los filtros activos de proyecto, fecha y usuario.',          'pending',     'medium', '2026-05-01', 6, '2026-03-20 11:00:00', 4, 'pending'),

-- TASKS  (Board 7 – API REST Diseño)
  (29, 7, 'Documentar endpoints actuales',      'Mapear todos los endpoints PHP existentes con sus parámetros y respuestas.',    'done',        'high',   '2026-04-10', 4, '2026-04-05 09:00:00', 1, 'pending'),
  (30, 7, 'Definir autenticación JWT',          'Elegir librería PHP de JWT, definir payload y duración del token.',             'done',        'high',   '2026-04-15', 4, '2026-04-07 10:00:00', 2, 'pending'),
  (31, 7, 'Diseñar respuestas JSON estándar',   'Crear estructura { ok, data, error } para todas las respuestas de la API.',     'in_progress', 'medium', '2026-04-25', 7, '2026-04-10 11:00:00', 3, 'in_progress'),

-- TASKS  (Board 10 – Tablero General)
  (32, 10,'Revisar ortografía del README',      'Corregir errores tipográficos y actualizar la documentación del proyecto.',     'done',        'low',    '2026-05-10', 1, '2026-05-01 08:00:00', 1, 'pending'),
  (33, 10,'Actualizar credenciales de demo',    'Cambiar las credenciales de prueba y documentarlas en el README.',             'done',        'low',    '2026-05-15', 1, '2026-05-02 09:00:00', 2, 'pending'),
  (34, 10,'Revisar rutas de imágenes rotas',    'Verificar que todos los assets del frontend carguen correctamente.',            'in_progress', 'medium', '2026-05-30', 2, '2026-05-10 10:00:00', 3, 'in_progress'),
  (35, 10,'Optimizar consultas SQL lentas',     'Agregar índices faltantes y revisar las queries N+1 en los reportes.',          'pending',     'high',   '2026-06-15', 4, '2026-05-15 11:00:00', 4, 'pending'),
  (36, 10,'Agregar favicon a todas las vistas', 'Asegurarse de que el favicon de TaskColab aparece en todas las pestañas.',      'done',        'low',    '2026-05-20', 3, '2026-05-12 09:00:00', 5, 'pending'),
  (37, 10,'Pruebas de carga del servidor',      'Ejecutar Apache Bench contra el servidor de producción y revisar tiempos.',     'pending',     'medium', '2026-06-30', 7, '2026-05-18 10:00:00', 6, 'pending');

-- ----------------------------------------------------------------
-- ASIGNACIONES DE TAREAS
-- ----------------------------------------------------------------
INSERT IGNORE INTO task_assignments (task_id, user_id, assigned_at) VALUES
  (1, 3, '2026-01-16 09:05:00'), (1, 8, '2026-01-16 09:10:00'),
  (2, 3, '2026-01-17 10:05:00'),
  (3, 8, '2026-01-18 11:05:00'),
  (4, 3, '2026-01-20 09:05:00'), (4, 2, '2026-01-20 09:10:00'),
  (5, 8, '2026-01-22 14:05:00'),
  (6, 4, '2026-02-01 09:05:00'),
  (7, 4, '2026-02-02 10:05:00'),
  (8, 4, '2026-02-03 11:05:00'),
  (9, 4, '2026-02-05 09:05:00'), (9, 2, '2026-02-05 09:10:00'),
  (10,4, '2026-02-10 10:05:00'),
  (11,4, '2026-02-12 11:05:00'), (11,2, '2026-02-12 11:10:00'),
  (12,3, '2026-02-15 09:05:00'),
  (13,4, '2026-03-01 09:05:00'),
  (14,4, '2026-03-02 10:05:00'),
  (15,4, '2026-03-03 11:05:00'),
  (16,3, '2026-03-05 09:05:00'),
  (17,4, '2026-03-10 10:05:00'), (17,2, '2026-03-10 10:10:00'),
  (18,2, '2026-03-12 11:05:00'),
  (19,4, '2026-03-15 09:05:00'),
  (20,3, '2026-03-18 10:05:00'),
  (21,9, '2026-02-05 10:05:00'),
  (22,9, '2026-02-07 11:05:00'),
  (23,9, '2026-02-12 09:05:00'),
  (24,9, '2026-02-20 10:05:00'),
  (25,6, '2026-03-05 08:05:00'),
  (26,10,'2026-03-07 09:05:00'),
  (27,10,'2026-03-12 10:05:00'),
  (28,6, '2026-03-20 11:05:00'),
  (29,4, '2026-04-05 09:05:00'),
  (30,4, '2026-04-07 10:05:00'),
  (31,7, '2026-04-10 11:05:00'),
  (32,1, '2026-05-01 08:05:00'),
  (33,1, '2026-05-02 09:05:00'),
  (34,2, '2026-05-10 10:05:00'),
  (35,4, '2026-05-15 11:05:00'),
  (36,3, '2026-05-12 09:05:00'),
  (37,7, '2026-05-18 10:05:00');

-- ----------------------------------------------------------------
-- TASK TAGS
-- ----------------------------------------------------------------
INSERT IGNORE INTO task_tags (task_id, tag_id) VALUES
  (1,3),(2,2),(3,3),(4,2),(5,3),
  (6,1),(7,1),(8,1),(9,10),(10,10),(11,10),(12,2),
  (13,1),(13,9),(14,1),(15,1),(16,2),(16,9),(17,9),(18,9),(19,9),(20,9),
  (21,5),(22,1),(23,2),(24,5),
  (25,1),(26,7),(27,2),(28,7),
  (29,1),(30,1),(31,1),
  (32,7),(33,7),(34,8),(35,1),(36,2),(37,5);

-- ----------------------------------------------------------------
-- COMENTARIOS EN TAREAS
-- ----------------------------------------------------------------
INSERT IGNORE INTO comments (task_id, user_id, content, created_at) VALUES
  (1,  3,  'Propuse azul #1B5CFF como primario. Está alineado con el logo actual.', '2026-01-18 10:00:00'),
  (1,  1,  'Aprobado. Seguimos con esa paleta para todo el sistema.', '2026-01-18 11:00:00'),
  (2,  3,  'El menú colapsa correctamente en móvil. Pendiente revisar en Safari iOS.', '2026-01-25 14:00:00'),
  (4,  2,  'Revisar el hover state en la tarjeta. En dark mode no se ve bien el borde.', '2026-01-28 09:00:00'),
  (4,  3,  'Ajustado. El borde ahora usa `border-color: var(--card-border)`.', '2026-01-28 10:00:00'),
  (9,  4,  'Los KPIs cargan bien en proyectos con más de 50 tareas. Sin lag visible.', '2026-02-18 11:00:00'),
  (10, 4,  'La lógica de archivar está lista. Falta agregar confirmación en el frontend.', '2026-02-25 09:00:00'),
  (10, 2,  'El modal de confirmación está en progreso. Lo termino mañana.', '2026-02-26 10:00:00'),
  (13, 4,  'Las tablas del chat están creadas y con foreign keys correctas.', '2026-03-03 09:00:00'),
  (15, 4,  'El endpoint valida que el usuario sea miembro de la conversación antes de permitir envío.', '2026-03-12 10:00:00'),
  (16, 3,  'Diseño del panel listo en Figma. Empiezo a codificar en HTML/CSS mañana.', '2026-03-18 11:00:00'),
  (17, 4,  'La conversación de proyecto se crea en el mismo POST que el proyecto. Sin llamada extra.', '2026-03-28 09:00:00'),
  (17, 2,  'Perfecto. Lo integro en el JS de proyectos esta semana.', '2026-03-29 10:00:00'),
  (23, 9,  'El RecyclerView de tarjetas está funcionando con datos estáticos. Falta conectar la API.', '2026-03-10 11:00:00'),
  (27, 10, 'Los datos de la gráfica están listos. Falta estilizar los colores de las barras.', '2026-04-05 09:00:00'),
  (31, 7,  'Estructura { ok, data, error } definida. Actualizando todos los endpoints gradualmente.', '2026-04-22 10:00:00'),
  (35, 4,  'Detecté un N+1 en el endpoint de reportes. La consulta de usuarios activos hace una query por usuario.', '2026-05-16 09:00:00'),
  (35, 1,  'Agregar un JOIN al query principal para traer todo en una sola consulta.', '2026-05-16 10:00:00');

-- ----------------------------------------------------------------
-- CONVERSACIONES (CHAT)
-- ----------------------------------------------------------------
INSERT IGNORE INTO conversations
  (id, type, title, project_id, task_id, created_by, created_at, last_message_at, is_active)
VALUES
  -- Chats de proyecto
  (1, 'project', 'Rediseño TaskColab Web',    1, NULL, 1, '2026-01-15 08:10:00', '2026-05-28 09:30:00', 1),
  (2, 'project', 'App Móvil TaskColab',        2, NULL, 1, '2026-02-01 09:10:00', '2026-05-27 15:00:00', 1),
  (3, 'project', 'Integración de Reportes',   3, NULL, 6, '2026-03-01 10:10:00', '2026-05-26 11:00:00', 1),
  (4, 'project', 'API REST v2',                5, NULL, 4, '2026-04-01 08:40:00', '2026-05-25 14:00:00', 1),
  (5, 'project', 'Onboarding de Clientes',    6, NULL, 6, '2026-04-15 10:10:00', '2026-05-24 10:00:00', 1),
  -- Chats directos
  (6, 'direct', NULL, NULL, NULL, 1, '2026-02-01 10:00:00', '2026-05-28 08:00:00', 1),
  (7, 'direct', NULL, NULL, NULL, 4, '2026-03-01 11:00:00', '2026-05-27 09:00:00', 1),
  (8, 'direct', NULL, NULL, NULL, 3, '2026-04-01 09:00:00', '2026-05-26 16:00:00', 1);

-- ----------------------------------------------------------------
-- MIEMBROS DE CONVERSACIÓN
-- ----------------------------------------------------------------
INSERT IGNORE INTO conversation_members
  (conversation_id, user_id, role_in_conversation, joined_at)
VALUES
  -- Proyecto 1
  (1, 1, 'owner',  '2026-01-15 08:10:00'),
  (1, 3, 'member', '2026-01-15 08:15:00'),
  (1, 4, 'member', '2026-01-15 08:15:00'),
  (1, 8, 'member', '2026-01-16 09:00:00'),
  (1, 2, 'member', '2026-01-17 10:00:00'),
  -- Proyecto 2
  (2, 1, 'owner',  '2026-02-01 09:10:00'),
  (2, 9, 'member', '2026-02-01 09:15:00'),
  (2, 4, 'member', '2026-02-02 10:00:00'),
  -- Proyecto 3
  (3, 6, 'owner',  '2026-03-01 10:10:00'),
  (3, 1, 'member', '2026-03-01 10:15:00'),
  (3,10, 'member', '2026-03-02 09:00:00'),
  -- Proyecto 5
  (4, 4, 'owner',  '2026-04-01 08:40:00'),
  (4, 1, 'member', '2026-04-01 08:45:00'),
  (4, 7, 'member', '2026-04-02 09:00:00'),
  -- Proyecto 6
  (5, 6, 'owner',  '2026-04-15 10:10:00'),
  (5, 1, 'member', '2026-04-15 10:15:00'),
  (5, 3, 'member', '2026-04-16 09:00:00'),
  -- Directo 1 (admin ↔ usuario)
  (6, 1, 'member', '2026-02-01 10:00:00'),
  (6, 2, 'member', '2026-02-01 10:00:00'),
  -- Directo 2 (reniery ↔ admin)
  (7, 4, 'member', '2026-03-01 11:00:00'),
  (7, 1, 'member', '2026-03-01 11:00:00'),
  -- Directo 3 (keyra ↔ valentina)
  (8, 3, 'member', '2026-04-01 09:00:00'),
  (8, 8, 'member', '2026-04-01 09:00:00');

-- ----------------------------------------------------------------
-- MENSAJES
-- ----------------------------------------------------------------
INSERT IGNORE INTO messages (id, conversation_id, user_id, body, created_at) VALUES
  -- Chat Proyecto 1 – Rediseño
  (1,  1, 1, '¡Hola equipo! Arrancamos el sprint de rediseño hoy. Revisen el Figma para los wireframes.', '2026-01-15 08:15:00'),
  (2,  1, 3, 'Ya vi los wireframes. Propongo usar Inter como tipografía principal, es muy limpia.', '2026-01-15 09:00:00'),
  (3,  1, 8, 'De acuerdo con Inter. También podemos usar Poppins para los títulos.', '2026-01-15 09:30:00'),
  (4,  1, 4, 'Los colores están listos en variables CSS. Pueden importar el archivo `tokens.css`.', '2026-01-20 10:00:00'),
  (5,  1, 2, '¿Quién se encarga del componente de tarjeta Kanban? Necesito integrarlo esta semana.', '2026-01-28 11:00:00'),
  (6,  1, 3, 'Yo lo tengo en progreso. Lo termino hoy.', '2026-01-28 11:30:00'),
  (7,  1, 1, 'Excelente trabajo en el Sprint 1. Pasamos al módulo de Proyectos desde mañana.', '2026-02-01 08:00:00'),
  (8,  1, 4, 'Las tablas de proyectos ya están en producción. Los endpoints GET y POST funcionan.', '2026-02-15 10:00:00'),
  (9,  1, 2, 'Integré el formulario de creación de proyecto en el frontend. Listo para revisión.', '2026-02-25 14:00:00'),
  (10, 1, 1, 'Revisado y aprobado. Ahora a conectar el chat al proyecto. @Reniery coordina.', '2026-03-01 08:00:00'),
  (11, 1, 4, 'Las tablas del chat están listas. Empezamos la UI del panel esta semana.', '2026-03-05 09:00:00'),
  (12, 1, 3, 'El panel de chat en el sidebar ya está diseñado. Subí el prototipo a Figma.', '2026-03-12 10:00:00'),
  (13, 1, 1, 'Gran avance equipo. El chat de proyecto y directo ya funcionan. Cierra el Sprint 3.', '2026-05-01 08:00:00'),
  (14, 1, 2, '¿Queda pendiente el badge de mensajes no leídos?', '2026-05-01 09:00:00'),
  (15, 1, 4, 'Sí, lo tengo en el backlog. Lo resuelvo antes del 15 de mayo.', '2026-05-01 09:30:00'),

  -- Chat Proyecto 2 – App Móvil
  (16, 2, 1, 'Arranquemos con el MVP de Android. Jorge lidera el módulo de tableros.', '2026-02-01 09:15:00'),
  (17, 2, 9, 'Entendido. Tengo el proyecto configurado en Android Studio.', '2026-02-05 10:10:00'),
  (18, 2, 9, 'La pantalla de login ya conecta con la API. Las credenciales de demo funcionan.', '2026-02-22 14:00:00'),
  (19, 2, 4, 'Bien. Asegúrate de cachear el token JWT de forma segura.', '2026-02-22 15:00:00'),
  (20, 2, 9, 'El Kanban con RecyclerView está en progreso. Los datos estáticos ya se muestran.', '2026-03-12 11:00:00'),

  -- Chat Proyecto 3 – Reportes
  (21, 3, 6, 'El template PDF está listo. Incluye el logo y la tabla de tareas por usuario.', '2026-03-15 08:10:00'),
  (22, 3,10, 'Agregué los filtros por proyecto. Los datos se filtran correctamente en el dashboard.', '2026-03-22 09:00:00'),
  (23, 3, 6, 'Falta que el filtro también aplique al PDF exportado. @Daniela puedes revisar?', '2026-04-01 10:00:00'),
  (24, 3,10, 'Sí, lo integro esta semana.', '2026-04-01 10:30:00'),

  -- Chat Proyecto 5 – API REST
  (25, 4, 4, 'Documenté los 23 endpoints actuales. Están en el README de la rama api-v2.', '2026-04-07 09:10:00'),
  (26, 4, 7, 'JWT configurado con firebase/php-jwt. El token expira en 24h.', '2026-04-16 10:00:00'),
  (27, 4, 4, 'Perfecto. Ahora a migrar los endpoints a la nueva estructura de respuesta.', '2026-04-18 11:00:00'),

  -- Chat Directo 1 (admin ↔ usuario)
  (28, 6, 1, 'Hola, ¿pudiste revisar las tareas del tablero general?', '2026-05-28 08:00:00'),
  (29, 6, 2, 'Sí, las que están en progreso las termino hoy.', '2026-05-28 08:05:00'),
  (30, 6, 1, 'Perfecto, avísame cuando estén listas.', '2026-05-28 08:10:00'),

  -- Chat Directo 2 (reniery ↔ admin)
  (31, 7, 4, 'Jefe, las consultas SQL del reporte están optimizadas. Bajó de 2s a 300ms.', '2026-05-27 09:00:00'),
  (32, 7, 1, 'Excelente trabajo. Lo documenta en el commit por favor.', '2026-05-27 09:15:00'),

  -- Chat Directo 3 (keyra ↔ valentina)
  (33, 8, 3, 'Valentina, ¿puedes revisar el prototipo del onboarding en Figma?', '2026-05-26 16:00:00'),
  (34, 8, 8, 'Ya lo vi. Sugiero agrandar los botones de CTA en el paso 2.', '2026-05-26 16:30:00'),
  (35, 8, 3, 'Hecho. Actualicé el Figma.', '2026-05-26 17:00:00');

-- ----------------------------------------------------------------
-- ACTIVIDAD DE LOGS
-- ----------------------------------------------------------------
INSERT IGNORE INTO activity_logs (user_id, entity_type, entity_id, action, details, created_at) VALUES
  (1, 'project', 1, 'create', '{"name":"Rediseño TaskColab Web"}', '2026-01-15 08:00:00'),
  (4, 'task',    6, 'create', '{"title":"Crear tabla projects en BD"}', '2026-02-01 09:00:00'),
  (4, 'task',    6, 'update', '{"status":"done"}', '2026-02-10 12:00:00'),
  (3, 'task',    1, 'update', '{"status":"done"}', '2026-01-25 16:00:00'),
  (1, 'project', 7, 'archive','{"name":"Migración a Servidor"}', '2026-04-02 10:00:00'),
  (4, 'board',   3, 'create', '{"title":"Sprint 3 – Chat"}', '2026-03-01 09:00:00'),
  (6, 'project', 3, 'create', '{"name":"Integración de Reportes PDF"}', '2026-03-01 10:00:00'),
  (9, 'task',   22, 'update', '{"status":"done"}', '2026-02-22 13:00:00'),
  (2, 'task',   11, 'comment','{"comment":"Integré el formulario de proyecto en el frontend."}', '2026-02-25 14:00:00'),
  (1, 'user',    5, 'login',  '{}', '2026-05-27 17:00:00');

-- ----------------------------------------------------------------
-- NOTIFICACIONES
-- ----------------------------------------------------------------
INSERT IGNORE INTO notifications (user_id, title, body, is_read, created_at) VALUES
  (2, 'Nueva tarea asignada',       'Se te asignó "Vincular tableros a proyectos" en el tablero Sprint 2.', 0, '2026-02-12 11:10:00'),
  (3, 'Comentario en tu tarea',     'Reniery comentó en "Componente de tarjeta de tarea".', 0, '2026-01-28 09:05:00'),
  (4, 'Proyecto archivado',         'El proyecto "Migración a Servidor" fue archivado por el administrador.', 1, '2026-04-02 10:05:00'),
  (9, 'Nueva tarea asignada',       'Se te asignó "Módulo de tableros Kanban" en la app Android.', 1, '2026-02-12 09:10:00'),
  (10,'Nueva tarea asignada',       'Se te asignó "Gráfica de barras por usuario" en Reportes v2.', 0, '2026-03-12 10:10:00'),
  (1, 'Tarea completada',           'Keyra marcó "Definir paleta de colores" como completada.', 1, '2026-01-25 16:05:00'),
  (1, 'Nuevo mensaje en el chat',   'Reniery envió un mensaje en el chat de Rediseño TaskColab Web.', 1, '2026-03-05 09:05:00'),
  (2, 'Nuevo mensaje directo',      'El administrador te envió un mensaje.', 0, '2026-05-28 08:00:05'),
  (4, 'Pull Request listo',         'La rama api-v2 está lista para revisión. Revisa los endpoints documentados.', 0, '2026-04-07 09:15:00'),
  (6, 'Comentario en reporte',      'Daniela comentó en "Exportar reporte filtrado a PDF".', 0, '2026-03-21 10:00:00');

-- ============================================================
-- DATOS EXTRA DE DEMO PARA PRODUCCIÓN / PRESENTACIÓN
-- IDs altos para evitar choques si ya existen datos locales.
-- ============================================================

-- ----------------------------------------------------------------
-- USUARIOS EXTRA
-- Usuarios de relleno para poblar perfiles, asignaciones, reportes y chats.
-- Credenciales oficiales de demo: admin@gmail.com / usuario@gmail.com.
-- ----------------------------------------------------------------
INSERT IGNORE INTO users
  (id, name, email, password_hash, is_admin, notes, created_at, last_login, is_active)
VALUES
  (101, 'Ana Beltrán',       'ana.beltran@taskcolab.mx',       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Scrum Master. Facilita ceremonias, seguimiento y desbloqueos.', '2026-03-01 08:30:00', '2026-05-28 13:10:00', 1),
  (102, 'Luis Ortega',       'luis.ortega@taskcolab.mx',       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Backend PHP. Responsable de integraciones, APIs y seguridad.', '2026-03-02 09:00:00', '2026-05-28 12:40:00', 1),
  (103, 'Mariana Soto',      'mariana.soto@taskcolab.mx',      '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Diseñadora UX. Investigación, prototipos y pruebas con usuarios.', '2026-03-03 09:15:00', '2026-05-27 18:20:00', 1),
  (104, 'Héctor Vargas',     'hector.vargas@taskcolab.mx',     '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'QA Automation. Pruebas E2E, regresión y evidencias.', '2026-03-04 10:00:00', '2026-05-28 11:55:00', 1),
  (105, 'Camila Reyes',      'camila.reyes@taskcolab.mx',      '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Frontend. Componentes, accesibilidad y responsive design.', '2026-03-05 11:20:00', '2026-05-28 10:30:00', 1),
  (106, 'Roberto Núñez',     'roberto.nunez@taskcolab.mx',     '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'DevOps. Backups, monitoreo, DNS y despliegues.', '2026-03-06 08:45:00', '2026-05-27 20:05:00', 1),
  (107, 'Patricia Molina',   'patricia.molina@taskcolab.mx',   '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Product Owner. Define prioridades y valida entregables.', '2026-03-07 09:30:00', '2026-05-28 09:50:00', 1),
  (108, 'Iván Salcedo',      'ivan.salcedo@taskcolab.mx',      '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Analista de datos. Dashboards, KPIs y reportes ejecutivos.', '2026-03-08 10:15:00', '2026-05-26 17:45:00', 1),
  (109, 'Fernanda Paredes',  'fernanda.paredes@taskcolab.mx',  '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Soporte. Documentación, atención al cliente y base de conocimiento.', '2026-03-09 12:00:00', '2026-05-25 16:20:00', 1),
  (110, 'Diego Castañeda',   'diego.castaneda@taskcolab.mx',   '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Mobile developer. Sincronización, cache offline y consumo de API.', '2026-03-10 08:10:00', '2026-05-28 08:30:00', 1);

-- ----------------------------------------------------------------
-- PROYECTOS EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO projects
  (id, name, description, owner_id, status, color, start_date, due_date, created_at)
VALUES
  (101, 'Portal de Clientes TaskColab', 'Portal web para clientes con acceso a avances, entregables, documentos y conversaciones por proyecto.', 107, 'active', '#2563EB', '2026-05-01', '2026-08-15', '2026-05-01 09:00:00'),
  (102, 'Centro de Ayuda y Documentación', 'Base de conocimiento con tutoriales, preguntas frecuentes, guías de instalación y material para soporte.', 109, 'active', '#0EA5E9', '2026-05-03', '2026-07-30', '2026-05-03 10:00:00'),
  (103, 'Automatización de QA', 'Implementación de pruebas automatizadas, evidencias, matriz de regresión y pipeline de validación.', 104, 'active', '#16A34A', '2026-05-05', '2026-09-10', '2026-05-05 08:30:00'),
  (104, 'Dashboard Ejecutivo', 'Panel para dirección con métricas de proyectos, productividad, cumplimiento de fechas y carga por usuario.', 108, 'active', '#9333EA', '2026-05-07', '2026-08-01', '2026-05-07 09:30:00'),
  (105, 'Optimización de Rendimiento', 'Mejoras de consultas SQL, cache, compresión de assets y tiempos de respuesta del panel principal.', 106, 'active', '#EA580C', '2026-05-10', '2026-07-20', '2026-05-10 11:00:00'),
  (106, 'Integración con App Móvil', 'Conectar la app móvil de TaskColab con endpoints web, sesiones, proyectos, tareas y chat.', 110, 'active', '#7C3AED', '2026-05-12', '2026-09-01', '2026-05-12 09:00:00'),
  (107, 'Campaña de Lanzamiento', 'Preparar landing, contenido comercial, recursos visuales, correos y checklist de salida a producción.', 103, 'paused', '#DB2777', '2026-05-15', '2026-07-01', '2026-05-15 10:20:00'),
  (108, 'Auditoría de Seguridad', 'Revisión de contraseñas, sesiones, permisos por rol, endpoints sensibles y hardening para producción.', 102, 'active', '#DC2626', '2026-05-18', '2026-08-20', '2026-05-18 08:45:00');

-- ----------------------------------------------------------------
-- MIEMBROS DE PROYECTOS EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO project_members (project_id, user_id, role_in_project, joined_at) VALUES
  (101,107,'owner','2026-05-01 09:00:00'), (101,105,'member','2026-05-01 09:10:00'), (101,102,'member','2026-05-01 09:15:00'), (101,109,'member','2026-05-02 10:00:00'), (101,1,'member','2026-05-02 10:10:00'),
  (102,109,'owner','2026-05-03 10:00:00'), (102,103,'member','2026-05-03 10:15:00'), (102,105,'member','2026-05-04 09:00:00'), (102,2,'member','2026-05-04 09:20:00'),
  (103,104,'owner','2026-05-05 08:30:00'), (103,102,'member','2026-05-05 08:45:00'), (103,106,'member','2026-05-05 09:00:00'), (103,4,'member','2026-05-06 11:00:00'),
  (104,108,'owner','2026-05-07 09:30:00'), (104,6,'member','2026-05-07 09:45:00'), (104,1,'member','2026-05-07 10:00:00'), (104,107,'member','2026-05-08 09:00:00'),
  (105,106,'owner','2026-05-10 11:00:00'), (105,102,'member','2026-05-10 11:10:00'), (105,4,'member','2026-05-10 11:20:00'), (105,7,'member','2026-05-11 08:30:00'),
  (106,110,'owner','2026-05-12 09:00:00'), (106,9,'member','2026-05-12 09:10:00'), (106,102,'member','2026-05-12 09:20:00'), (106,4,'member','2026-05-13 09:00:00'), (106,1,'member','2026-05-13 09:20:00'),
  (107,103,'owner','2026-05-15 10:20:00'), (107,107,'member','2026-05-15 10:30:00'), (107,109,'member','2026-05-15 10:40:00'), (107,8,'member','2026-05-16 09:00:00'),
  (108,102,'owner','2026-05-18 08:45:00'), (108,106,'member','2026-05-18 09:00:00'), (108,1,'member','2026-05-18 09:15:00'), (108,104,'member','2026-05-18 09:30:00');

-- ----------------------------------------------------------------
-- TABLEROS EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO boards
  (id, project_id, owner_id, title, description, visibility, color, created_at)
VALUES
  (101,101,107,'Portal - UX y Frontend','Flujos, pantallas y componentes del portal de clientes.', 'private', '#2563EB', '2026-05-01 09:30:00'),
  (102,101,102,'Portal - Backend','Endpoints, permisos, documentos y acceso por cliente.', 'private', '#2563EB', '2026-05-02 09:00:00'),
  (103,102,109,'Contenido de Ayuda','Artículos, tutoriales, FAQs y guías rápidas.', 'private', '#0EA5E9', '2026-05-03 10:30:00'),
  (104,102,105,'Diseño del Help Center','Interfaz, navegación, buscador y categoría de artículos.', 'private', '#0EA5E9', '2026-05-04 09:30:00'),
  (105,103,104,'Suite de Pruebas E2E','Casos críticos, evidencias y pruebas automatizadas.', 'private', '#16A34A', '2026-05-05 09:30:00'),
  (106,103,106,'Pipeline QA','Integración de pruebas en despliegues y validación continua.', 'private', '#16A34A', '2026-05-06 09:30:00'),
  (107,104,108,'KPIs Ejecutivos','Métricas, tarjetas y filtros para dirección.', 'private', '#9333EA', '2026-05-07 10:30:00'),
  (108,104,108,'Reportes por Equipo','Productividad, carga de trabajo y vencimientos por usuario.', 'private', '#9333EA', '2026-05-08 10:00:00'),
  (109,105,106,'Performance Backend','Consultas, índices, cache y perfiles de rendimiento.', 'private', '#EA580C', '2026-05-10 12:00:00'),
  (110,105,105,'Performance Frontend','Peso de assets, imágenes, render y responsive.', 'private', '#EA580C', '2026-05-11 09:00:00'),
  (111,106,110,'API para App Móvil','Endpoints consumidos por Android/iOS y manejo de sesiones.', 'private', '#7C3AED', '2026-05-12 10:00:00'),
  (112,106,110,'Sincronización Offline','Cola local, reintentos, conflictos y lectura de cambios.', 'private', '#7C3AED', '2026-05-13 10:00:00'),
  (113,107,103,'Lanzamiento - Contenido','Copys, visuales, correos, posts y piezas de producto.', 'private', '#DB2777', '2026-05-15 11:00:00'),
  (114,108,102,'Seguridad - Revisión','Checklist de endpoints, permisos, sesiones y configuración.', 'private', '#DC2626', '2026-05-18 10:00:00');

-- ----------------------------------------------------------------
-- MIEMBROS DE TABLEROS EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO board_members (board_id, user_id, role_in_board, joined_at) VALUES
  (101,107,'owner','2026-05-01 09:30:00'), (101,105,'member','2026-05-01 09:35:00'), (101,103,'member','2026-05-01 09:40:00'),
  (102,102,'owner','2026-05-02 09:00:00'), (102,105,'member','2026-05-02 09:05:00'), (102,109,'member','2026-05-02 09:10:00'),
  (103,109,'owner','2026-05-03 10:30:00'), (103,103,'member','2026-05-03 10:35:00'), (103,2,'member','2026-05-03 10:40:00'),
  (104,105,'owner','2026-05-04 09:30:00'), (104,103,'member','2026-05-04 09:35:00'), (104,109,'member','2026-05-04 09:40:00'),
  (105,104,'owner','2026-05-05 09:30:00'), (105,102,'member','2026-05-05 09:35:00'), (105,4,'member','2026-05-05 09:40:00'),
  (106,106,'owner','2026-05-06 09:30:00'), (106,104,'member','2026-05-06 09:35:00'), (106,7,'member','2026-05-06 09:40:00'),
  (107,108,'owner','2026-05-07 10:30:00'), (107,6,'member','2026-05-07 10:35:00'), (107,1,'member','2026-05-07 10:40:00'),
  (108,108,'owner','2026-05-08 10:00:00'), (108,107,'member','2026-05-08 10:05:00'), (108,10,'member','2026-05-08 10:10:00'),
  (109,106,'owner','2026-05-10 12:00:00'), (109,102,'member','2026-05-10 12:05:00'), (109,4,'member','2026-05-10 12:10:00'),
  (110,105,'owner','2026-05-11 09:00:00'), (110,3,'member','2026-05-11 09:05:00'), (110,8,'member','2026-05-11 09:10:00'),
  (111,110,'owner','2026-05-12 10:00:00'), (111,9,'member','2026-05-12 10:05:00'), (111,102,'member','2026-05-12 10:10:00'),
  (112,110,'owner','2026-05-13 10:00:00'), (112,106,'member','2026-05-13 10:05:00'), (112,4,'member','2026-05-13 10:10:00'),
  (113,103,'owner','2026-05-15 11:00:00'), (113,107,'member','2026-05-15 11:05:00'), (113,109,'member','2026-05-15 11:10:00'),
  (114,102,'owner','2026-05-18 10:00:00'), (114,106,'member','2026-05-18 10:05:00'), (114,104,'member','2026-05-18 10:10:00');

-- ----------------------------------------------------------------
-- TAGS EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO tags (id, name, color) VALUES
  (101, 'UX Research', '#DB2777'),
  (102, 'Documentación', '#0EA5E9'),
  (103, 'Mobile', '#7C3AED'),
  (104, 'Seguridad', '#DC2626'),
  (105, 'Performance', '#EA580C'),
  (106, 'Soporte', '#14B8A6'),
  (107, 'Producción', '#111827'),
  (108, 'Analytics', '#9333EA');

-- ----------------------------------------------------------------
-- TAREAS EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO tasks
  (id, board_id, title, description, status, priority, due_date, created_by, created_at, position, column_created)
VALUES
  (101,101,'Mapa de navegación del portal','Definir secciones: resumen, entregables, mensajes, documentos y reportes.', 'done','high','2026-05-08',103,'2026-05-01 10:00:00',1,'pending'),
  (102,101,'Diseñar vista de avance del cliente','Pantalla con porcentaje, hitos, tareas recientes y próximos vencimientos.', 'in_progress','high','2026-05-18',105,'2026-05-02 09:00:00',2,'in_progress'),
  (103,101,'Prototipo responsive del portal','Adaptar vistas a móvil, tablet y desktop antes de pasar a HTML.', 'pending','medium','2026-05-25',105,'2026-05-04 11:00:00',3,'pending'),
  (104,102,'Endpoint de entregables por cliente','Retornar documentos y tareas visibles según membresía del proyecto.', 'done','high','2026-05-15',102,'2026-05-02 10:00:00',1,'pending'),
  (105,102,'Permisos de lectura por proyecto','Validar que un cliente solo vea los proyectos autorizados.', 'in_progress','high','2026-05-22',102,'2026-05-05 10:30:00',2,'in_progress'),
  (106,102,'Historial de cambios para clientes','Mostrar eventos relevantes sin exponer datos internos del equipo.', 'pending','medium','2026-06-01',109,'2026-05-08 09:30:00',3,'pending'),
  (107,103,'Redactar guía de primeros pasos','Artículo de bienvenida con login, creación de proyecto y uso del tablero.', 'done','medium','2026-05-12',109,'2026-05-03 11:00:00',1,'pending'),
  (108,103,'Crear FAQ de permisos y roles','Preguntas frecuentes sobre administradores, miembros e invitados.', 'in_progress','medium','2026-05-20',109,'2026-05-06 10:00:00',2,'in_progress'),
  (109,103,'Guía de despliegue en Hostinger','Documentar pasos para subir repo, configurar BD, SSL y variables.', 'pending','high','2026-05-29',2,'2026-05-10 12:00:00',3,'pending'),
  (110,104,'Diseñar buscador de artículos','Componente de búsqueda por título, categoría y palabras clave.', 'done','medium','2026-05-14',105,'2026-05-04 10:00:00',1,'pending'),
  (111,104,'Categorías visuales del help center','Cards para Cuenta, Proyectos, Tareas, Chat, Reportes y Móvil.', 'in_progress','low','2026-05-24',103,'2026-05-08 10:20:00',2,'in_progress'),
  (112,105,'Caso E2E login y logout','Automatizar inicio de sesión, navegación y cierre de sesión con alerta.', 'done','high','2026-05-10',104,'2026-05-05 10:00:00',1,'pending'),
  (113,105,'Caso E2E creación de proyecto','Crear proyecto, validar duplicado, editar color y archivar.', 'in_progress','high','2026-05-18',104,'2026-05-07 10:00:00',2,'in_progress'),
  (114,105,'Caso E2E chat directo','Abrir chat privado, enviar mensaje y validar aparición en historial.', 'pending','medium','2026-05-28',104,'2026-05-09 11:00:00',3,'pending'),
  (115,106,'Pipeline de pruebas nocturnas','Ejecutar suite completa cada noche y guardar evidencias por build.', 'in_progress','medium','2026-06-05',106,'2026-05-06 11:00:00',1,'in_progress'),
  (116,106,'Reporte de regresión automático','Enviar resumen de pruebas fallidas al administrador y QA lead.', 'pending','medium','2026-06-12',106,'2026-05-08 11:00:00',2,'pending'),
  (117,107,'Definir KPIs ejecutivos','Seleccionar métricas: tareas vencidas, avance promedio y productividad semanal.', 'done','high','2026-05-13',108,'2026-05-07 11:00:00',1,'pending'),
  (118,107,'Tarjetas de resumen por proyecto','Cards con estado, responsables, avance, vencimientos y prioridad.', 'in_progress','high','2026-05-24',108,'2026-05-09 10:00:00',2,'in_progress'),
  (119,107,'Filtro por rango de fechas','Permitir revisar datos por semana, mes, trimestre o rango personalizado.', 'pending','medium','2026-06-03',10,'2026-05-12 09:00:00',3,'pending'),
  (120,108,'Ranking de carga por usuario','Vista comparativa de tareas activas, completadas y vencidas por miembro.', 'in_progress','medium','2026-06-01',108,'2026-05-08 12:00:00',1,'in_progress'),
  (121,108,'Exportar dashboard ejecutivo','Generar PDF con filtros y gráficos del panel ejecutivo.', 'pending','high','2026-06-10',6,'2026-05-10 12:00:00',2,'pending'),
  (122,109,'Perfilado de consultas de proyectos','Medir tiempos de list_projects, dashboard stats y tareas por usuario.', 'done','high','2026-05-16',106,'2026-05-10 13:00:00',1,'pending'),
  (123,109,'Agregar índices faltantes','Crear índices para project_id, due_date, user_id y conversation_id.', 'in_progress','high','2026-05-25',102,'2026-05-12 10:00:00',2,'in_progress'),
  (124,109,'Cache de estadísticas generales','Guardar totales calculados para reducir consultas repetidas.', 'pending','medium','2026-06-05',4,'2026-05-14 10:00:00',3,'pending'),
  (125,110,'Optimizar imágenes públicas','Comprimir logos, gifs y assets de landing sin perder calidad visual.', 'done','medium','2026-05-17',105,'2026-05-11 10:00:00',1,'pending'),
  (126,110,'Revisar CLS del navbar','Evitar saltos visuales en desktop y móvil al cargar estilos.', 'in_progress','medium','2026-05-26',105,'2026-05-13 09:00:00',2,'in_progress'),
  (127,110,'Versionar CSS para despliegue','Agregar query string de versión y reglas anti-cache.', 'done','low','2026-05-28',3,'2026-05-27 09:00:00',3,'pending'),
  (128,111,'Endpoint móvil de login','Responder usuario, rol y token de sesión para la app móvil.', 'in_progress','high','2026-05-28',110,'2026-05-12 11:00:00',1,'in_progress'),
  (129,111,'Endpoint móvil de proyectos','Listar proyectos activos y archivados compatibles con app móvil.', 'pending','high','2026-06-04',102,'2026-05-14 11:00:00',2,'pending'),
  (130,111,'Endpoint móvil de mensajes','Enviar y listar mensajes con paginación para conversaciones móviles.', 'pending','medium','2026-06-12',4,'2026-05-16 10:00:00',3,'pending'),
  (131,112,'Diseñar cola offline','Guardar acciones locales y sincronizarlas cuando vuelva internet.', 'in_progress','high','2026-06-15',110,'2026-05-13 11:00:00',1,'in_progress'),
  (132,112,'Resolver conflictos de edición','Definir reglas cuando dos usuarios modifican la misma tarea offline.', 'pending','high','2026-06-25',110,'2026-05-15 12:00:00',2,'pending'),
  (133,113,'Copy de landing para lanzamiento','Texto de hero, beneficios, módulos y llamado a registro.', 'done','medium','2026-05-20',103,'2026-05-15 12:00:00',1,'pending'),
  (134,113,'Checklist de redes sociales','Piezas para LinkedIn, Facebook, Instagram y correo de anuncio.', 'in_progress','medium','2026-05-30',109,'2026-05-17 10:00:00',2,'in_progress'),
  (135,113,'Video corto de demostración','Guion y capturas del flujo: proyecto, tarea, chat y reporte.', 'pending','low','2026-06-10',8,'2026-05-20 09:00:00',3,'pending'),
  (136,114,'Revisar exposición de errores PHP','Ocultar errores internos y registrar logs en servidor.', 'done','high','2026-05-21',102,'2026-05-18 11:00:00',1,'pending'),
  (137,114,'Auditar endpoints sin sesión','Confirmar que endpoints privados validen usuario autenticado.', 'in_progress','high','2026-05-31',102,'2026-05-20 10:00:00',2,'in_progress'),
  (138,114,'Checklist HTTPS y cookies','Forzar HTTPS, revisar flags secure/httponly y CORS para móvil.', 'pending','high','2026-06-08',106,'2026-05-22 09:00:00',3,'pending');

-- ----------------------------------------------------------------
-- ASIGNACIONES EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO task_assignments (task_id, user_id, assigned_at) VALUES
  (101,103,'2026-05-01 10:05:00'), (101,107,'2026-05-01 10:10:00'), (102,105,'2026-05-02 09:05:00'), (102,103,'2026-05-02 09:10:00'), (103,105,'2026-05-04 11:05:00'),
  (104,102,'2026-05-02 10:05:00'), (105,102,'2026-05-05 10:35:00'), (105,1,'2026-05-05 10:40:00'), (106,109,'2026-05-08 09:35:00'),
  (107,109,'2026-05-03 11:05:00'), (108,109,'2026-05-06 10:05:00'), (108,2,'2026-05-06 10:10:00'), (109,2,'2026-05-10 12:05:00'), (109,109,'2026-05-10 12:10:00'),
  (110,105,'2026-05-04 10:05:00'), (111,103,'2026-05-08 10:25:00'), (112,104,'2026-05-05 10:05:00'), (113,104,'2026-05-07 10:05:00'), (114,104,'2026-05-09 11:05:00'),
  (115,106,'2026-05-06 11:05:00'), (115,104,'2026-05-06 11:10:00'), (116,106,'2026-05-08 11:05:00'), (117,108,'2026-05-07 11:05:00'), (118,108,'2026-05-09 10:05:00'),
  (119,10,'2026-05-12 09:05:00'), (120,108,'2026-05-08 12:05:00'), (120,107,'2026-05-08 12:10:00'), (121,6,'2026-05-10 12:05:00'),
  (122,106,'2026-05-10 13:05:00'), (123,102,'2026-05-12 10:05:00'), (123,4,'2026-05-12 10:10:00'), (124,4,'2026-05-14 10:05:00'),
  (125,105,'2026-05-11 10:05:00'), (126,105,'2026-05-13 09:05:00'), (127,3,'2026-05-27 09:05:00'),
  (128,110,'2026-05-12 11:05:00'), (128,102,'2026-05-12 11:10:00'), (129,102,'2026-05-14 11:05:00'), (130,4,'2026-05-16 10:05:00'), (130,110,'2026-05-16 10:10:00'),
  (131,110,'2026-05-13 11:05:00'), (132,110,'2026-05-15 12:05:00'), (133,103,'2026-05-15 12:05:00'), (134,109,'2026-05-17 10:05:00'), (135,8,'2026-05-20 09:05:00'),
  (136,102,'2026-05-18 11:05:00'), (137,102,'2026-05-20 10:05:00'), (137,104,'2026-05-20 10:10:00'), (138,106,'2026-05-22 09:05:00');

-- ----------------------------------------------------------------
-- TAGS DE TAREAS EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO task_tags (task_id, tag_id) VALUES
  (101,101),(101,10),(102,2),(102,101),(103,2),(104,1),(105,104),(106,102),
  (107,102),(108,102),(109,102),(109,107),(110,2),(111,101),(112,4),(113,4),(113,10),(114,4),(114,9),
  (115,5),(115,4),(116,4),(117,108),(118,108),(119,108),(120,108),(121,7),
  (122,105),(123,105),(123,1),(124,105),(125,105),(126,105),(127,107),
  (128,103),(128,1),(129,103),(130,103),(130,9),(131,103),(132,103),
  (133,102),(134,106),(135,106),(136,104),(137,104),(138,104),(138,107);

-- ----------------------------------------------------------------
-- COMENTARIOS EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO comments (task_id, user_id, content, created_at) VALUES
  (101,107,'El cliente quiere ver entregables y mensajes recientes desde la primera pantalla.', '2026-05-02 09:10:00'),
  (102,103,'Propongo mostrar hitos arriba y actividad reciente debajo para evitar saturar la vista.', '2026-05-04 14:30:00'),
  (105,1,'Validar este permiso contra project_members antes de consultar documentos.', '2026-05-07 10:00:00'),
  (109,2,'Estoy documentando también el paso de .env en Hostinger para evitar errores de conexión.', '2026-05-12 16:00:00'),
  (113,104,'El test ya cubre crear, editar y archivar. Falta validar restauración.', '2026-05-10 12:00:00'),
  (118,108,'Las tarjetas ya muestran avance y tareas vencidas. Falta afinar colores por estado.', '2026-05-15 09:00:00'),
  (123,102,'Agregué índice compuesto en tareas por board_id/status para acelerar reportes.', '2026-05-16 11:00:00'),
  (126,105,'El navbar ya no brinca, pero revisaré ancho en 1366px y 1920px.', '2026-05-22 10:00:00'),
  (128,110,'La app móvil ya recibe respuesta JSON limpia. Falta persistir token.', '2026-05-21 13:00:00'),
  (137,104,'Detecté dos endpoints antiguos que todavía responden sin validar sesión.', '2026-05-24 12:00:00');

-- ----------------------------------------------------------------
-- ADJUNTOS EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO attachments (id, task_id, filename, url, uploaded_by, created_at) VALUES
  (101,101,'mapa-navegacion-portal.pdf','/assets/uploads/demo/mapa-navegacion-portal.pdf',103,'2026-05-02 09:00:00'),
  (102,102,'avance-cliente-wireframe.png','/assets/uploads/demo/avance-cliente-wireframe.png',105,'2026-05-05 10:00:00'),
  (103,109,'guia-hostinger-taskcolab.docx','/assets/uploads/demo/guia-hostinger-taskcolab.docx',2,'2026-05-14 12:00:00'),
  (104,113,'evidencia-proyecto-e2e.zip','/assets/uploads/demo/evidencia-proyecto-e2e.zip',104,'2026-05-12 16:00:00'),
  (105,117,'matriz-kpis-ejecutivos.xlsx','/assets/uploads/demo/matriz-kpis-ejecutivos.xlsx',108,'2026-05-13 10:30:00'),
  (106,136,'reporte-seguridad-inicial.pdf','/assets/uploads/demo/reporte-seguridad-inicial.pdf',102,'2026-05-21 11:00:00');

-- ----------------------------------------------------------------
-- CONVERSACIONES EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO conversations
  (id, type, title, project_id, task_id, created_by, created_at, last_message_at, is_active)
VALUES
  (101,'project','Portal de Clientes TaskColab',101,NULL,107,'2026-05-01 09:05:00','2026-05-28 13:10:00',1),
  (102,'project','Centro de Ayuda y Documentación',102,NULL,109,'2026-05-03 10:05:00','2026-05-28 12:30:00',1),
  (103,'project','Automatización de QA',103,NULL,104,'2026-05-05 08:35:00','2026-05-28 11:50:00',1),
  (104,'project','Dashboard Ejecutivo',104,NULL,108,'2026-05-07 09:35:00','2026-05-27 17:20:00',1),
  (105,'project','Optimización de Rendimiento',105,NULL,106,'2026-05-10 11:05:00','2026-05-28 10:45:00',1),
  (106,'project','Integración con App Móvil',106,NULL,110,'2026-05-12 09:05:00','2026-05-28 09:40:00',1),
  (107,'project','Campaña de Lanzamiento',107,NULL,103,'2026-05-15 10:25:00','2026-05-26 15:30:00',1),
  (108,'project','Auditoría de Seguridad',108,NULL,102,'2026-05-18 08:50:00','2026-05-28 08:55:00',1),
  (109,'task','Tarea: Endpoint móvil de login',NULL,128,110,'2026-05-20 09:00:00','2026-05-28 09:20:00',1),
  (110,'direct',NULL,NULL,NULL,107,'2026-05-22 10:00:00','2026-05-28 12:00:00',1),
  (111,'direct',NULL,NULL,NULL,104,'2026-05-23 11:00:00','2026-05-27 13:00:00',1);

-- ----------------------------------------------------------------
-- MIEMBROS DE CONVERSACIONES EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO conversation_members (conversation_id, user_id, role_in_conversation, joined_at) VALUES
  (101,107,'owner','2026-05-01 09:05:00'), (101,105,'member','2026-05-01 09:10:00'), (101,102,'member','2026-05-01 09:15:00'), (101,109,'member','2026-05-02 10:00:00'), (101,1,'member','2026-05-02 10:10:00'),
  (102,109,'owner','2026-05-03 10:05:00'), (102,103,'member','2026-05-03 10:10:00'), (102,105,'member','2026-05-04 09:00:00'), (102,2,'member','2026-05-04 09:20:00'),
  (103,104,'owner','2026-05-05 08:35:00'), (103,102,'member','2026-05-05 08:45:00'), (103,106,'member','2026-05-05 09:00:00'), (103,4,'member','2026-05-06 11:00:00'),
  (104,108,'owner','2026-05-07 09:35:00'), (104,6,'member','2026-05-07 09:45:00'), (104,1,'member','2026-05-07 10:00:00'), (104,107,'member','2026-05-08 09:00:00'),
  (105,106,'owner','2026-05-10 11:05:00'), (105,102,'member','2026-05-10 11:10:00'), (105,4,'member','2026-05-10 11:20:00'), (105,7,'member','2026-05-11 08:30:00'),
  (106,110,'owner','2026-05-12 09:05:00'), (106,9,'member','2026-05-12 09:10:00'), (106,102,'member','2026-05-12 09:20:00'), (106,4,'member','2026-05-13 09:00:00'), (106,1,'member','2026-05-13 09:20:00'),
  (107,103,'owner','2026-05-15 10:25:00'), (107,107,'member','2026-05-15 10:30:00'), (107,109,'member','2026-05-15 10:40:00'), (107,8,'member','2026-05-16 09:00:00'),
  (108,102,'owner','2026-05-18 08:50:00'), (108,106,'member','2026-05-18 09:00:00'), (108,1,'member','2026-05-18 09:15:00'), (108,104,'member','2026-05-18 09:30:00'),
  (109,110,'owner','2026-05-20 09:00:00'), (109,102,'member','2026-05-20 09:05:00'), (109,4,'member','2026-05-20 09:10:00'),
  (110,107,'member','2026-05-22 10:00:00'), (110,1,'member','2026-05-22 10:00:00'),
  (111,104,'member','2026-05-23 11:00:00'), (111,102,'member','2026-05-23 11:00:00');

-- ----------------------------------------------------------------
-- MENSAJES EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO messages (id, conversation_id, user_id, body, created_at) VALUES
  (101,101,107,'Equipo, el portal debe mostrar avance y próximos entregables sin que el cliente entre al tablero interno.', '2026-05-01 09:15:00'),
  (102,101,103,'Estoy armando el mapa de navegación. Propongo cinco secciones principales.', '2026-05-01 10:00:00'),
  (103,101,105,'Puedo tener prototipo responsive esta semana si cerramos el contenido hoy.', '2026-05-02 09:30:00'),
  (104,101,102,'Desde backend validaré permisos por project_members antes de entregar documentos.', '2026-05-02 10:00:00'),
  (105,101,1,'Me gusta. Cuiden que no se filtren notas internas del equipo.', '2026-05-03 11:00:00'),
  (106,102,109,'Ya está la primera guía de primeros pasos. Falta la guía de despliegue en Hostinger.', '2026-05-08 10:00:00'),
  (107,102,2,'Yo redacto el apartado de GitHub, base de datos y .env de producción.', '2026-05-10 12:30:00'),
  (108,102,103,'Agrego capturas del flujo de registro y creación de proyecto.', '2026-05-11 09:20:00'),
  (109,103,104,'La suite E2E ya cubre login, logout y creación de proyecto.', '2026-05-12 08:30:00'),
  (110,103,102,'Perfecto. Necesito que el caso de duplicados valide el mensaje del endpoint.', '2026-05-12 09:00:00'),
  (111,103,106,'Voy a configurar ejecución nocturna y guardar evidencia por fecha.', '2026-05-13 10:00:00'),
  (112,104,108,'Los KPIs principales serán avance, vencidas, completadas por semana y carga por usuario.', '2026-05-09 10:00:00'),
  (113,104,6,'Agrega un filtro por proyecto para que dirección pueda revisar iniciativas específicas.', '2026-05-09 10:30:00'),
  (114,104,107,'También necesitamos vista ejecutiva para proyectos pausados.', '2026-05-10 12:00:00'),
  (115,105,106,'El endpoint de proyectos ya responde más rápido después del ajuste de GROUP BY.', '2026-05-20 10:00:00'),
  (116,105,4,'Sí, el bug visual de duplicados venía por el join con tableros.', '2026-05-20 10:15:00'),
  (117,105,102,'Estoy agregando índices en task_assignments y conversations.', '2026-05-22 11:00:00'),
  (118,106,110,'Para móvil propongo usar token y no depender de cookies PHP.', '2026-05-20 09:10:00'),
  (119,106,102,'De acuerdo. Puedo crear endpoint dedicado de login móvil.', '2026-05-20 09:20:00'),
  (120,106,9,'En Android consumiré API_BASE_URL desde configuración por ambiente.', '2026-05-20 09:30:00'),
  (121,106,1,'Prioridad: login, proyectos, tareas y chat. Reportes puede ir después.', '2026-05-21 08:00:00'),
  (122,107,103,'El copy de landing ya tiene módulos: proyectos, chat, tareas, usuarios y reportes.', '2026-05-18 11:00:00'),
  (123,107,109,'Estoy preparando posts y correo de lanzamiento.', '2026-05-19 12:00:00'),
  (124,108,102,'Revisé errores PHP visibles. En producción deben quedar ocultos.', '2026-05-21 09:00:00'),
  (125,108,106,'También hay que forzar HTTPS y revisar flags de cookie.', '2026-05-21 10:00:00'),
  (126,108,104,'Haré pruebas de endpoints sin sesión para detectar fugas.', '2026-05-22 11:00:00'),
  (127,109,110,'El login móvil ya responde usuario y token de sesión en JSON.', '2026-05-25 09:00:00'),
  (128,109,102,'Sube ejemplo de respuesta para conectarlo desde Android.', '2026-05-25 09:20:00'),
  (129,109,4,'Cuando quede token, conectamos conversaciones y mensajes.', '2026-05-26 10:00:00'),
  (130,110,107,'¿Ya puedo revisar la demo del dashboard ejecutivo?', '2026-05-28 11:30:00'),
  (131,110,1,'Sí, está en el proyecto Dashboard Ejecutivo. Revisa filtros por fecha.', '2026-05-28 12:00:00'),
  (132,111,104,'Luis, encontré un endpoint antiguo que no valida sesión.', '2026-05-27 12:30:00'),
  (133,111,102,'Gracias, lo cierro hoy y agrego prueba de regresión.', '2026-05-27 13:00:00');

-- ----------------------------------------------------------------
-- LECTURAS DE MENSAJES EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO message_reads (message_id, user_id, read_at) VALUES
  (101,107,'2026-05-01 09:20:00'), (101,105,'2026-05-01 09:25:00'), (101,102,'2026-05-01 09:30:00'),
  (106,109,'2026-05-08 10:05:00'), (107,2,'2026-05-10 12:35:00'), (109,104,'2026-05-12 08:40:00'),
  (115,106,'2026-05-20 10:05:00'), (116,4,'2026-05-20 10:20:00'), (118,110,'2026-05-20 09:15:00'),
  (124,102,'2026-05-21 09:10:00'), (125,106,'2026-05-21 10:10:00'), (132,104,'2026-05-27 12:35:00');

-- ----------------------------------------------------------------
-- ACTIVIDAD EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO activity_logs (user_id, entity_type, entity_id, action, details, created_at) VALUES
  (107,'project',101,'create','{"name":"Portal de Clientes TaskColab"}','2026-05-01 09:00:00'),
  (109,'project',102,'create','{"name":"Centro de Ayuda y Documentación"}','2026-05-03 10:00:00'),
  (104,'project',103,'create','{"name":"Automatización de QA"}','2026-05-05 08:30:00'),
  (108,'project',104,'create','{"name":"Dashboard Ejecutivo"}','2026-05-07 09:30:00'),
  (106,'project',105,'create','{"name":"Optimización de Rendimiento"}','2026-05-10 11:00:00'),
  (110,'project',106,'create','{"name":"Integración con App Móvil"}','2026-05-12 09:00:00'),
  (102,'project',108,'create','{"name":"Auditoría de Seguridad"}','2026-05-18 08:45:00'),
  (103,'task',101,'update','{"status":"done"}','2026-05-08 16:00:00'),
  (104,'task',112,'update','{"status":"done"}','2026-05-10 15:00:00'),
  (106,'task',122,'update','{"status":"done"}','2026-05-16 12:00:00'),
  (3,'task',127,'update','{"status":"done"}','2026-05-28 09:00:00'),
  (102,'task',136,'update','{"status":"done"}','2026-05-21 12:00:00'),
  (110,'message',127,'create','{"conversation_id":109}','2026-05-25 09:00:00'),
  (1,'notification',101,'create','{"title":"Dashboard listo para revisión"}','2026-05-28 12:00:00');

-- ----------------------------------------------------------------
-- NOTIFICACIONES EXTRA
-- ----------------------------------------------------------------
INSERT IGNORE INTO notifications (user_id, title, body, is_read, created_at) VALUES
  (105,'Nueva tarea asignada','Se te asignó "Diseñar vista de avance del cliente" en Portal - UX y Frontend.',0,'2026-05-02 09:05:00'),
  (102,'Revisión de permisos','Debes validar acceso por proyecto en el Portal de Clientes.',0,'2026-05-05 10:40:00'),
  (109,'Artículo pendiente','Falta terminar la guía de despliegue en Hostinger.',0,'2026-05-10 12:10:00'),
  (104,'Prueba E2E en progreso','El caso de creación de proyecto quedó en revisión.',1,'2026-05-07 10:10:00'),
  (108,'Dashboard actualizado','Ya puedes revisar las tarjetas ejecutivas por proyecto.',0,'2026-05-15 09:10:00'),
  (106,'Consulta optimizada','El endpoint de proyectos redujo su tiempo de respuesta.',1,'2026-05-20 10:10:00'),
  (110,'Endpoint móvil listo','El login móvil ya devuelve JSON para consumir desde Android.',0,'2026-05-25 09:05:00'),
  (103,'Copy aprobado','El texto de landing fue aprobado para lanzamiento.',1,'2026-05-20 13:00:00'),
  (102,'Alerta de seguridad','Se detectó endpoint antiguo sin validación de sesión.',0,'2026-05-27 13:05:00'),
  (1,'Demo más completa','Se agregaron datos de demo para usuarios, proyectos, tareas, chat y reportes.',0,'2026-05-28 13:30:00');

SET FOREIGN_KEY_CHECKS = 1;
