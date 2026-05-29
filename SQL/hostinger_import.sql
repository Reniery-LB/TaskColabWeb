-- ============================================================
--  TASKCOLAB – Importación limpia para Hostinger
--  INSTRUCCIONES:
--  1. En phpMyAdmin, haz clic en tu BD (u978171169_taskcolab) en el menú izquierdo
--  2. Ve a la pestaña "Importar"
--  3. Sube este archivo
--  *** NO incluye CREATE DATABASE ni USE taskcolab ***
--  *** RECREA las tablas de TaskColab y borra datos anteriores de esas tablas ***
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS message_attachments;
DROP TABLE IF EXISTS message_reads;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversation_members;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS sync_events;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS task_tags;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS task_assignments;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS board_members;
DROP TABLE IF EXISTS boards;
DROP TABLE IF EXISTS project_members;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- ============================================================
-- ESQUEMA (taskcolab.sql)
-- ============================================================

-- Tabla roles 
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(32) NOT NULL UNIQUE,
  descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(254) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) DEFAULT 0,
  avatar_url TEXT,
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME NULL,
  is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla boards
CREATE TABLE IF NOT EXISTS boards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NULL,
  owner_id INT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  visibility VARCHAR(20) DEFAULT 'private',
  color VARCHAR(7),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Miembros del tablero
CREATE TABLE IF NOT EXISTS board_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  board_id INT NOT NULL,
  user_id INT NOT NULL,
  invited_by INT NULL,
  joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  role_in_board VARCHAR(32) DEFAULT 'member',
  UNIQUE KEY uq_board_user (board_id, user_id),
  FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tasks
CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  board_id INT NOT NULL,
  title VARCHAR(250) NOT NULL,
  description TEXT,
  status ENUM('pending','in_progress','done') NOT NULL DEFAULT 'pending',
  priority ENUM('high','medium','low') DEFAULT 'medium',
  due_date DATE NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  position INT DEFAULT 0,
  column_created ENUM('pending','in_progress','done') DEFAULT 'pending',
  is_active TINYINT(1) DEFAULT 1,
  FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_tasks_board (board_id),
  INDEX idx_tasks_status (status),
  INDEX idx_tasks_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asignaciones de tareas
CREATE TABLE IF NOT EXISTS task_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_task_user (task_id, user_id),
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_task_assign_task (task_id),
  INDEX idx_task_assign_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comentarios
CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NULL,
  content TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  edited_at DATETIME NULL,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_comments_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Adjuntos
CREATE TABLE IF NOT EXISTS attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  filename VARCHAR(255),
  url TEXT,
  uploaded_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_attachments_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tags
CREATE TABLE IF NOT EXISTS tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  color VARCHAR(7) DEFAULT '#E5E7EB'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS task_tags (
  task_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (task_id, tag_id),
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Actividad
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  entity_type VARCHAR(50),
  entity_id INT,
  action VARCHAR(80),
  details JSON NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_activity_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notificaciones
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200),
  body TEXT,
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices adicionales
CREATE INDEX idx_boards_owner_id ON boards(owner_id);
CREATE INDEX idx_task_assignments_user_id ON task_assignments(user_id);
CREATE INDEX idx_comments_task_id ON comments(task_id);

-- ============================================================
-- 2026_05_12 – Sesiones para app móvil
-- ============================================================

CREATE TABLE IF NOT EXISTS user_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  device_name VARCHAR(120) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  last_seen_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_sessions_user_id (user_id),
  INDEX idx_user_sessions_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sync_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(80) NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT NULL,
  board_id INT NULL,
  user_id INT NULL,
  payload JSON NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_sync_events_board_id (board_id),
  INDEX idx_sync_events_created_at (created_at),
  INDEX idx_sync_events_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2026_05_15 – Proyectos
-- ============================================================

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  description TEXT NULL,
  owner_id INT NULL,
  status ENUM('active','paused','archived') NOT NULL DEFAULT 'active',
  color VARCHAR(7) DEFAULT '#1B5CFF',
  start_date DATE NULL,
  due_date DATE NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_projects_owner (owner_id),
  INDEX idx_projects_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  role_in_project VARCHAR(32) DEFAULT 'member',
  joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_project_user (project_id, user_id),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_project_members_project (project_id),
  INDEX idx_project_members_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2026_05_16 – Chat
-- ============================================================

CREATE TABLE IF NOT EXISTS conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('direct','group','project','task') NOT NULL,
  title VARCHAR(180) NULL,
  project_id INT NULL,
  task_id INT NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_message_at DATETIME NULL,
  is_active TINYINT(1) DEFAULT 1,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_project_conversation (type, project_id),
  UNIQUE KEY uq_task_conversation (type, task_id),
  INDEX idx_conversations_type (type),
  INDEX idx_conversations_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversation_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  user_id INT NOT NULL,
  role_in_conversation VARCHAR(32) DEFAULT 'member',
  joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_read_message_id INT NULL,
  muted TINYINT(1) DEFAULT 0,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_conversation_user (conversation_id, user_id),
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_conversation_members_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  user_id INT NULL,
  body TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_messages_conversation (conversation_id, id),
  INDEX idx_messages_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_reads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  message_id INT NOT NULL,
  user_id INT NOT NULL,
  read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_message_user_read (message_id, user_id),
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_message_reads_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  message_id INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  url TEXT NOT NULL,
  uploaded_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_message_attachments_message (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA – Roles, Usuarios, Proyectos, Tableros, Tareas, etc.
-- ============================================================

-- ROLES
INSERT IGNORE INTO roles (id, name, descripcion) VALUES
  (1, 'admin',  'Acceso completo al sistema'),
  (2, 'member', 'Colaborador estándar'),
  (3, 'viewer', 'Solo lectura');

-- USUARIOS
INSERT IGNORE INTO users
  (id, name, email, password_hash, is_admin, notes, created_at, last_login, is_active)
VALUES
  (1,  'Administrador TaskColab',
       'admin@gmail.com',
       '$2y$10$leCQCcMRRGtttQAcpB6FyODO3olX8J/CmYBEDn9vlL10NfNU6RGd6',
       1, 'Cuenta principal del sistema.',
       '2026-01-10 08:00:00', '2026-05-28 09:00:00', 1),
  (2,  'Usuario Prueba',
       'usuario@gmail.com',
       '$2y$10$ikq5irh/E/c1o7IwJsplK.7hLbhofUvnNuZecAnDcAB2qREjW86kq',
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
       '2026-02-10 11:00:00', '2026-05-22 15:00:00', 1),
  (101,'Ana Beltrán',       'ana.beltran@taskcolab.mx',       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Scrum Master.',        '2026-03-01 08:30:00', '2026-05-28 13:10:00', 1),
  (102,'Luis Ortega',       'luis.ortega@taskcolab.mx',       '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Backend PHP.',         '2026-03-02 09:00:00', '2026-05-28 12:40:00', 1),
  (103,'Mariana Soto',      'mariana.soto@taskcolab.mx',      '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Diseñadora UX.',       '2026-03-03 09:15:00', '2026-05-27 18:20:00', 1),
  (104,'Héctor Vargas',     'hector.vargas@taskcolab.mx',     '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'QA Automation.',       '2026-03-04 10:00:00', '2026-05-28 11:55:00', 1),
  (105,'Camila Reyes',      'camila.reyes@taskcolab.mx',      '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Frontend.',            '2026-03-05 11:20:00', '2026-05-28 10:30:00', 1),
  (106,'Roberto Núñez',     'roberto.nunez@taskcolab.mx',     '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'DevOps.',              '2026-03-06 08:45:00', '2026-05-27 20:05:00', 1),
  (107,'Patricia Molina',   'patricia.molina@taskcolab.mx',   '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Product Owner.',       '2026-03-07 09:30:00', '2026-05-28 09:50:00', 1),
  (108,'Iván Salcedo',      'ivan.salcedo@taskcolab.mx',      '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Analista de datos.',   '2026-03-08 10:15:00', '2026-05-26 17:45:00', 1),
  (109,'Fernanda Paredes',  'fernanda.paredes@taskcolab.mx',  '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Soporte.',             '2026-03-09 12:00:00', '2026-05-25 16:20:00', 1),
  (110,'Diego Castañeda',   'diego.castaneda@taskcolab.mx',   '$2y$10$TKh8H1.PfuA2Pi/A3WCKUO6nKuGe5wSBBY7pOH2nOkV1pPFmH5Cqm', 0, 'Mobile developer.',    '2026-03-10 08:10:00', '2026-05-28 08:30:00', 1);

-- PROYECTOS
INSERT IGNORE INTO projects (id, name, description, owner_id, status, color, start_date, due_date, created_at) VALUES
  (1, 'Rediseño TaskColab Web', 'Modernización total de la interfaz pública y el panel de gestión.', 1, 'active', '#1B5CFF', '2026-01-15', '2026-06-30', '2026-01-15 08:00:00'),
  (2, 'App Móvil TaskColab', 'Desarrollo de la aplicación nativa para Android e iOS.', 1, 'active', '#7C3AED', '2026-02-01', '2026-08-31', '2026-02-01 09:00:00'),
  (3, 'Integración de Reportes PDF', 'Mejorar el módulo de reportes con exportación avanzada.', 6, 'active', '#059669', '2026-03-01', '2026-05-31', '2026-03-01 10:00:00'),
  (4, 'Módulo de Notificaciones', 'Sistema de notificaciones en tiempo real.', 4, 'paused', '#D97706', '2026-03-15', '2026-07-15', '2026-03-15 09:00:00'),
  (5, 'API REST v2', 'Refactorización de los endpoints PHP.', 4, 'active', '#DC2626', '2026-04-01', '2026-09-30', '2026-04-01 08:30:00'),
  (6, 'Onboarding de Nuevos Clientes', 'Flujo de bienvenida y tutoriales interactivos.', 6, 'active', '#0891B2', '2026-04-15', '2026-06-15', '2026-04-15 10:00:00'),
  (7, 'Migración a Servidor de Producción', 'Mover el entorno de XAMPP a hosting.', 7, 'archived', '#6B7280', '2026-01-01', '2026-04-01', '2026-01-01 08:00:00'),
  (101,'Portal de Clientes TaskColab',       'Portal web para clientes con acceso a avances y entregables.', 107,'active','#2563EB','2026-05-01','2026-08-15','2026-05-01 09:00:00'),
  (102,'Centro de Ayuda y Documentación',    'Base de conocimiento con tutoriales y FAQs.', 109,'active','#0EA5E9','2026-05-03','2026-07-30','2026-05-03 10:00:00'),
  (103,'Automatización de QA',               'Pruebas automatizadas y pipeline de validación.', 104,'active','#16A34A','2026-05-05','2026-09-10','2026-05-05 08:30:00'),
  (104,'Dashboard Ejecutivo',                'Panel para dirección con métricas de proyectos.', 108,'active','#9333EA','2026-05-07','2026-08-01','2026-05-07 09:30:00'),
  (105,'Optimización de Rendimiento',        'Mejoras de consultas SQL, cache y compresión.', 106,'active','#EA580C','2026-05-10','2026-07-20','2026-05-10 11:00:00'),
  (106,'Integración con App Móvil',          'Conectar la app móvil con endpoints web.', 110,'active','#7C3AED','2026-05-12','2026-09-01','2026-05-12 09:00:00'),
  (107,'Campaña de Lanzamiento',             'Landing, contenido comercial y checklist de salida.', 103,'paused','#DB2777','2026-05-15','2026-07-01','2026-05-15 10:20:00'),
  (108,'Auditoría de Seguridad',             'Revisión de contraseñas, sesiones y permisos.', 102,'active','#DC2626','2026-05-18','2026-08-20','2026-05-18 08:45:00');

-- MIEMBROS DE PROYECTO
INSERT IGNORE INTO project_members (project_id, user_id, role_in_project, joined_at) VALUES
  (1,1,'owner','2026-01-15 08:00:00'),(1,3,'member','2026-01-15 08:05:00'),(1,4,'member','2026-01-15 08:05:00'),(1,8,'member','2026-01-16 09:00:00'),(1,2,'member','2026-01-17 10:00:00'),
  (2,1,'owner','2026-02-01 09:00:00'),(2,9,'member','2026-02-01 09:05:00'),(2,4,'member','2026-02-02 10:00:00'),(2,5,'member','2026-02-03 11:00:00'),
  (3,6,'owner','2026-03-01 10:00:00'),(3,1,'member','2026-03-01 10:05:00'),(3,10,'member','2026-03-02 09:00:00'),(3,4,'member','2026-03-02 09:30:00'),
  (4,4,'owner','2026-03-15 09:00:00'),(4,1,'member','2026-03-15 09:05:00'),(4,7,'member','2026-03-16 10:00:00'),
  (5,4,'owner','2026-04-01 08:30:00'),(5,1,'member','2026-04-01 08:35:00'),(5,7,'member','2026-04-02 09:00:00'),(5,2,'member','2026-04-03 10:00:00'),
  (6,6,'owner','2026-04-15 10:00:00'),(6,1,'member','2026-04-15 10:05:00'),(6,3,'member','2026-04-16 09:00:00'),(6,8,'member','2026-04-16 09:30:00'),
  (7,7,'owner','2026-01-01 08:00:00'),(7,1,'member','2026-01-01 08:05:00'),
  (101,107,'owner','2026-05-01 09:00:00'),(101,105,'member','2026-05-01 09:10:00'),(101,102,'member','2026-05-01 09:15:00'),(101,109,'member','2026-05-02 10:00:00'),(101,1,'member','2026-05-02 10:10:00'),
  (102,109,'owner','2026-05-03 10:00:00'),(102,103,'member','2026-05-03 10:15:00'),(102,105,'member','2026-05-04 09:00:00'),(102,2,'member','2026-05-04 09:20:00'),
  (103,104,'owner','2026-05-05 08:30:00'),(103,102,'member','2026-05-05 08:45:00'),(103,106,'member','2026-05-05 09:00:00'),(103,4,'member','2026-05-06 11:00:00'),
  (104,108,'owner','2026-05-07 09:30:00'),(104,6,'member','2026-05-07 09:45:00'),(104,1,'member','2026-05-07 10:00:00'),(104,107,'member','2026-05-08 09:00:00'),
  (105,106,'owner','2026-05-10 11:00:00'),(105,102,'member','2026-05-10 11:10:00'),(105,4,'member','2026-05-10 11:20:00'),(105,7,'member','2026-05-11 08:30:00'),
  (106,110,'owner','2026-05-12 09:00:00'),(106,9,'member','2026-05-12 09:10:00'),(106,102,'member','2026-05-12 09:20:00'),(106,4,'member','2026-05-13 09:00:00'),(106,1,'member','2026-05-13 09:20:00'),
  (107,103,'owner','2026-05-15 10:20:00'),(107,107,'member','2026-05-15 10:30:00'),(107,109,'member','2026-05-15 10:40:00'),(107,8,'member','2026-05-16 09:00:00'),
  (108,102,'owner','2026-05-18 08:45:00'),(108,106,'member','2026-05-18 09:00:00'),(108,1,'member','2026-05-18 09:15:00'),(108,104,'member','2026-05-18 09:30:00');

-- TABLEROS
INSERT IGNORE INTO boards (id, project_id, owner_id, title, description, visibility, color, created_at) VALUES
  (1,1,1,'Sprint 1 – Diseño UI','Wireframes y componentes base.','private','#1B5CFF','2026-01-16 09:00:00'),
  (2,1,1,'Sprint 2 – Módulo Proyectos','Implementación del CRUD de proyectos.','private','#1B5CFF','2026-02-01 09:00:00'),
  (3,1,4,'Sprint 3 – Chat','Desarrollo del sistema de mensajería.','private','#1B5CFF','2026-03-01 09:00:00'),
  (4,2,1,'Android MVP','Primera versión funcional de la app Android.','private','#7C3AED','2026-02-05 10:00:00'),
  (5,2,9,'iOS MVP','Primera versión funcional de la app iOS.','private','#7C3AED','2026-02-10 11:00:00'),
  (6,3,6,'Reportes v2','Rediseño del módulo de reportes.','private','#059669','2026-03-05 08:00:00'),
  (7,5,4,'API REST – Diseño','Definición de endpoints y autenticación JWT.','private','#DC2626','2026-04-05 09:00:00'),
  (8,5,4,'API REST – Desarrollo','Codificación y pruebas unitarias.','private','#DC2626','2026-04-20 09:00:00'),
  (9,6,6,'Onboarding – Flujo','Diseño del flujo de bienvenida.','private','#0891B2','2026-04-16 10:00:00'),
  (10,NULL,1,'Tablero General','Tablero para tareas sueltas del equipo.','private','#374151','2026-01-10 08:00:00'),
  (101,101,107,'Portal - UX y Frontend','Flujos y componentes del portal.','private','#2563EB','2026-05-01 09:30:00'),
  (102,101,102,'Portal - Backend','Endpoints y permisos del portal.','private','#2563EB','2026-05-02 09:00:00'),
  (103,102,109,'Contenido de Ayuda','Artículos, FAQs y guías.','private','#0EA5E9','2026-05-03 10:30:00'),
  (104,102,105,'Diseño del Help Center','Interfaz y navegación del help center.','private','#0EA5E9','2026-05-04 09:30:00'),
  (105,103,104,'Suite de Pruebas E2E','Casos críticos y pruebas automatizadas.','private','#16A34A','2026-05-05 09:30:00'),
  (106,103,106,'Pipeline QA','Integración de pruebas en despliegues.','private','#16A34A','2026-05-06 09:30:00'),
  (107,104,108,'KPIs Ejecutivos','Métricas y filtros para dirección.','private','#9333EA','2026-05-07 10:30:00'),
  (108,104,108,'Reportes por Equipo','Productividad y carga de trabajo.','private','#9333EA','2026-05-08 10:00:00'),
  (109,105,106,'Performance Backend','Consultas, índices y cache.','private','#EA580C','2026-05-10 12:00:00'),
  (110,105,105,'Performance Frontend','Peso de assets y responsive.','private','#EA580C','2026-05-11 09:00:00'),
  (111,106,110,'API para App Móvil','Endpoints para Android/iOS.','private','#7C3AED','2026-05-12 10:00:00'),
  (112,106,110,'Sincronización Offline','Cola local y reintentos.','private','#7C3AED','2026-05-13 10:00:00'),
  (113,107,103,'Lanzamiento - Contenido','Copys, visuales y correos.','private','#DB2777','2026-05-15 11:00:00'),
  (114,108,102,'Seguridad - Revisión','Checklist de endpoints y permisos.','private','#DC2626','2026-05-18 10:00:00');

-- MIEMBROS DE TABLERO
INSERT IGNORE INTO board_members (board_id, user_id, role_in_board, joined_at) VALUES
  (1,1,'owner','2026-01-16 09:00:00'),(1,3,'member','2026-01-16 09:05:00'),(1,8,'member','2026-01-17 10:00:00'),
  (2,1,'owner','2026-02-01 09:00:00'),(2,4,'member','2026-02-01 09:05:00'),(2,2,'member','2026-02-02 10:00:00'),
  (3,1,'owner','2026-03-01 09:00:00'),(3,4,'member','2026-03-01 09:05:00'),
  (4,1,'owner','2026-02-05 10:00:00'),(4,9,'member','2026-02-05 10:05:00'),
  (5,9,'owner','2026-02-10 11:00:00'),
  (6,6,'owner','2026-03-05 08:00:00'),(6,10,'member','2026-03-06 09:00:00'),
  (7,4,'owner','2026-04-05 09:00:00'),(7,7,'member','2026-04-05 09:05:00'),
  (8,4,'owner','2026-04-20 09:00:00'),
  (9,6,'owner','2026-04-16 10:00:00'),
  (10,1,'owner','2026-01-10 08:00:00'),(10,2,'member','2026-01-11 09:00:00'),
  (101,107,'owner','2026-05-01 09:30:00'),(101,105,'member','2026-05-01 09:35:00'),(101,103,'member','2026-05-01 09:40:00'),
  (102,102,'owner','2026-05-02 09:00:00'),(102,105,'member','2026-05-02 09:05:00'),(102,109,'member','2026-05-02 09:10:00'),
  (103,109,'owner','2026-05-03 10:30:00'),(103,103,'member','2026-05-03 10:35:00'),(103,2,'member','2026-05-03 10:40:00'),
  (104,105,'owner','2026-05-04 09:30:00'),(104,103,'member','2026-05-04 09:35:00'),(104,109,'member','2026-05-04 09:40:00'),
  (105,104,'owner','2026-05-05 09:30:00'),(105,102,'member','2026-05-05 09:35:00'),(105,4,'member','2026-05-05 09:40:00'),
  (106,106,'owner','2026-05-06 09:30:00'),(106,104,'member','2026-05-06 09:35:00'),(106,7,'member','2026-05-06 09:40:00'),
  (107,108,'owner','2026-05-07 10:30:00'),(107,6,'member','2026-05-07 10:35:00'),(107,1,'member','2026-05-07 10:40:00'),
  (108,108,'owner','2026-05-08 10:00:00'),(108,107,'member','2026-05-08 10:05:00'),(108,10,'member','2026-05-08 10:10:00'),
  (109,106,'owner','2026-05-10 12:00:00'),(109,102,'member','2026-05-10 12:05:00'),(109,4,'member','2026-05-10 12:10:00'),
  (110,105,'owner','2026-05-11 09:00:00'),(110,3,'member','2026-05-11 09:05:00'),(110,8,'member','2026-05-11 09:10:00'),
  (111,110,'owner','2026-05-12 10:00:00'),(111,9,'member','2026-05-12 10:05:00'),(111,102,'member','2026-05-12 10:10:00'),
  (112,110,'owner','2026-05-13 10:00:00'),(112,106,'member','2026-05-13 10:05:00'),(112,4,'member','2026-05-13 10:10:00'),
  (113,103,'owner','2026-05-15 11:00:00'),(113,107,'member','2026-05-15 11:05:00'),(113,109,'member','2026-05-15 11:10:00'),
  (114,102,'owner','2026-05-18 10:00:00'),(114,106,'member','2026-05-18 10:05:00'),(114,104,'member','2026-05-18 10:10:00');

-- TAGS
INSERT IGNORE INTO tags (id, name, color) VALUES
  (1,'Backend','#1B5CFF'),(2,'Frontend','#7C3AED'),(3,'Diseño','#EC4899'),(4,'QA','#059669'),
  (5,'DevOps','#D97706'),(6,'Urgente','#DC2626'),(7,'Mejora','#0891B2'),(8,'Bug','#EF4444'),
  (9,'Chat','#8B5CF6'),(10,'Proyecto','#10B981'),
  (101,'UX Research','#DB2777'),(102,'Documentación','#0EA5E9'),(103,'Mobile','#7C3AED'),
  (104,'Seguridad','#DC2626'),(105,'Performance','#EA580C'),(106,'Soporte','#14B8A6'),
  (107,'Producción','#111827'),(108,'Analytics','#9333EA');

-- TAREAS (Base)
INSERT IGNORE INTO tasks (id, board_id, title, description, status, priority, due_date, created_by, created_at, position, column_created) VALUES
  (1,1,'Definir paleta de colores','Seleccionar 5 colores principales.','done','high','2026-01-25',1,'2026-01-16 09:00:00',1,'pending'),
  (2,1,'Diseñar navbar responsivo','Menú adaptable para móvil y desktop.','done','high','2026-01-28',3,'2026-01-17 10:00:00',2,'pending'),
  (3,1,'Wireframes de pantalla principal','Bocetos de alta fidelidad.','done','medium','2026-01-30',8,'2026-01-18 11:00:00',3,'pending'),
  (4,1,'Componente de tarjeta de tarea','Componente reutilizable Kanban.','in_progress','medium','2026-02-05',3,'2026-01-20 09:00:00',4,'in_progress'),
  (5,1,'Iconografía del sistema','Librería de iconos SVG.','pending','low','2026-02-10',8,'2026-01-22 14:00:00',5,'pending'),
  (6,2,'Crear tabla projects en BD','Diseñar y migrar la tabla projects.','done','high','2026-02-10',4,'2026-02-01 09:00:00',1,'pending'),
  (7,2,'Endpoint GET /projects','API de proyectos activos del usuario.','done','high','2026-02-12',4,'2026-02-02 10:00:00',2,'pending'),
  (8,2,'Endpoint POST /projects','Crear proyecto con datos básicos.','done','high','2026-02-14',4,'2026-02-03 11:00:00',3,'pending'),
  (9,2,'Panel de KPIs del proyecto','Total, en proceso, completadas y avance.','done','high','2026-02-20',4,'2026-02-05 09:00:00',4,'pending'),
  (10,2,'Archivar y restaurar proyectos','Lógica de archivado con botón de restaurar.','in_progress','medium','2026-03-01',4,'2026-02-10 10:00:00',5,'in_progress'),
  (11,2,'Vincular tableros a proyectos','Agregar project_id a boards.','in_progress','medium','2026-03-05',2,'2026-02-12 11:00:00',6,'in_progress'),
  (12,2,'UI de selección de proyecto activo','Strip visual de proyecto activo.','pending','low','2026-03-10',3,'2026-02-15 09:00:00',7,'pending'),
  (13,3,'Crear tablas del chat en BD','Diseñar conversations y messages.','done','high','2026-03-10',4,'2026-03-01 09:00:00',1,'pending'),
  (14,3,'Endpoint GET /conversations','Listar conversaciones del usuario.','done','high','2026-03-12',4,'2026-03-02 10:00:00',2,'pending'),
  (15,3,'Endpoint POST /messages','Enviar mensaje a conversación.','done','high','2026-03-14',4,'2026-03-03 11:00:00',3,'pending'),
  (16,3,'Panel de chat en sidebar','UI del panel lateral de chat.','done','high','2026-03-20',3,'2026-03-05 09:00:00',4,'pending'),
  (17,3,'Chat de proyecto automático','Crear conversación al crear proyecto.','in_progress','medium','2026-04-01',4,'2026-03-10 10:00:00',5,'in_progress'),
  (18,3,'Chat directo entre usuarios','Conversación directa entre usuarios.','in_progress','medium','2026-04-05',2,'2026-03-12 11:00:00',6,'in_progress'),
  (19,3,'Notificaciones de mensajes','Badge con mensajes no leídos.','pending','low','2026-04-15',4,'2026-03-15 09:00:00',7,'pending'),
  (20,3,'Scroll automático en mensajes','Scroll al último mensaje.','pending','low','2026-04-20',3,'2026-03-18 10:00:00',8,'pending'),
  (21,4,'Configurar proyecto Android Studio','Inicializar repo y dependencias.','done','high','2026-02-10',9,'2026-02-05 10:00:00',1,'pending'),
  (22,4,'Pantalla de login','Formulario con validaciones y API.','done','high','2026-02-20',9,'2026-02-07 11:00:00',2,'pending'),
  (23,4,'Módulo de tableros Kanban','Vista Kanban con RecyclerView.','in_progress','high','2026-03-15',9,'2026-02-12 09:00:00',3,'in_progress'),
  (24,4,'Push notifications','Firebase Cloud Messaging.','pending','medium','2026-04-30',9,'2026-02-20 10:00:00',4,'pending'),
  (25,6,'Diseño del PDF con dompdf','Template PDF con logo y gráficas.','done','high','2026-03-15',6,'2026-03-05 08:00:00',1,'pending'),
  (26,6,'Filtros por proyecto en reportes','Selector de proyecto en dashboard.','done','high','2026-03-20',10,'2026-03-07 09:00:00',2,'pending'),
  (27,6,'Gráfica de barras por usuario','Chart.js con tareas por usuario.','in_progress','medium','2026-04-10',10,'2026-03-12 10:00:00',3,'in_progress'),
  (28,6,'Exportar reporte filtrado a PDF','PDF respeta filtros activos.','pending','medium','2026-05-01',6,'2026-03-20 11:00:00',4,'pending'),
  (29,7,'Documentar endpoints actuales','Mapear endpoints PHP existentes.','done','high','2026-04-10',4,'2026-04-05 09:00:00',1,'pending'),
  (30,7,'Definir autenticación JWT','Librería PHP de JWT y payload.','done','high','2026-04-15',4,'2026-04-07 10:00:00',2,'pending'),
  (31,7,'Diseñar respuestas JSON estándar','Estructura { ok, data, error }.','in_progress','medium','2026-04-25',7,'2026-04-10 11:00:00',3,'in_progress'),
  (32,10,'Revisar ortografía del README','Corregir errores tipográficos.','done','low','2026-05-10',1,'2026-05-01 08:00:00',1,'pending'),
  (33,10,'Actualizar credenciales de demo','Cambiar credenciales de prueba.','done','low','2026-05-15',1,'2026-05-02 09:00:00',2,'pending'),
  (34,10,'Revisar rutas de imágenes rotas','Verificar assets del frontend.','in_progress','medium','2026-05-30',2,'2026-05-10 10:00:00',3,'in_progress'),
  (35,10,'Optimizar consultas SQL lentas','Índices y queries N+1 en reportes.','pending','high','2026-06-15',4,'2026-05-15 11:00:00',4,'pending'),
  (36,10,'Agregar favicon a todas las vistas','Favicon en todas las pestañas.','done','low','2026-05-20',3,'2026-05-12 09:00:00',5,'pending'),
  (37,10,'Pruebas de carga del servidor','Apache Bench en producción.','pending','medium','2026-06-30',7,'2026-05-18 10:00:00',6,'pending'),
  (101,101,'Mapa de navegación del portal','Definir secciones del portal.','done','high','2026-05-08',103,'2026-05-01 10:00:00',1,'pending'),
  (102,101,'Diseñar vista de avance del cliente','Pantalla con porcentaje e hitos.','in_progress','high','2026-05-18',105,'2026-05-02 09:00:00',2,'in_progress'),
  (103,101,'Prototipo responsive del portal','Adaptar vistas a móvil y desktop.','pending','medium','2026-05-25',105,'2026-05-04 11:00:00',3,'pending'),
  (104,102,'Endpoint de entregables por cliente','Documentos y tareas visibles.','done','high','2026-05-15',102,'2026-05-02 10:00:00',1,'pending'),
  (105,102,'Permisos de lectura por proyecto','Solo proyectos autorizados.','in_progress','high','2026-05-22',102,'2026-05-05 10:30:00',2,'in_progress'),
  (106,102,'Historial de cambios para clientes','Eventos sin datos internos.','pending','medium','2026-06-01',109,'2026-05-08 09:30:00',3,'pending'),
  (107,103,'Redactar guía de primeros pasos','Artículo de bienvenida.','done','medium','2026-05-12',109,'2026-05-03 11:00:00',1,'pending'),
  (108,103,'Crear FAQ de permisos y roles','Preguntas frecuentes.','in_progress','medium','2026-05-20',109,'2026-05-06 10:00:00',2,'in_progress'),
  (109,103,'Guía de despliegue en Hostinger','Pasos para subir repo y BD.','pending','high','2026-05-29',2,'2026-05-10 12:00:00',3,'pending'),
  (110,104,'Diseñar buscador de artículos','Búsqueda por título y categoría.','done','medium','2026-05-14',105,'2026-05-04 10:00:00',1,'pending'),
  (111,104,'Categorías visuales del help center','Cards para cada sección.','in_progress','low','2026-05-24',103,'2026-05-08 10:20:00',2,'in_progress'),
  (112,105,'Caso E2E login y logout','Automatizar login y logout.','done','high','2026-05-10',104,'2026-05-05 10:00:00',1,'pending'),
  (113,105,'Caso E2E creación de proyecto','Crear, editar y archivar proyecto.','in_progress','high','2026-05-18',104,'2026-05-07 10:00:00',2,'in_progress'),
  (114,105,'Caso E2E chat directo','Chat privado y validar historial.','pending','medium','2026-05-28',104,'2026-05-09 11:00:00',3,'pending'),
  (115,106,'Pipeline de pruebas nocturnas','Suite completa cada noche.','in_progress','medium','2026-06-05',106,'2026-05-06 11:00:00',1,'in_progress'),
  (116,106,'Reporte de regresión automático','Resumen de pruebas fallidas.','pending','medium','2026-06-12',106,'2026-05-08 11:00:00',2,'pending'),
  (117,107,'Definir KPIs ejecutivos','Métricas: vencidas y productividad.','done','high','2026-05-13',108,'2026-05-07 11:00:00',1,'pending'),
  (118,107,'Tarjetas de resumen por proyecto','Cards con estado y avance.','in_progress','high','2026-05-24',108,'2026-05-09 10:00:00',2,'in_progress'),
  (119,107,'Filtro por rango de fechas','Semana, mes o rango personalizado.','pending','medium','2026-06-03',10,'2026-05-12 09:00:00',3,'pending'),
  (120,108,'Ranking de carga por usuario','Comparativa de tareas activas.','in_progress','medium','2026-06-01',108,'2026-05-08 12:00:00',1,'in_progress'),
  (121,108,'Exportar dashboard a PDF','PDF con métricas del equipo.','pending','medium','2026-06-10',6,'2026-05-10 12:00:00',2,'pending'),
  (122,109,'Índices en tablas críticas','Índices para task_assignments.','done','high','2026-05-16',106,'2026-05-10 13:00:00',1,'pending'),
  (123,109,'Cache de respuestas frecuentes','Cache PHP para reportes lentos.','in_progress','medium','2026-05-28',102,'2026-05-12 10:00:00',2,'in_progress'),
  (124,109,'Perfilado de endpoints lentos','Detectar y optimizar los más lentos.','pending','medium','2026-06-05',4,'2026-05-14 10:00:00',3,'pending'),
  (125,110,'Optimizar imágenes públicas','Comprimir logos y assets.','done','medium','2026-05-17',105,'2026-05-11 10:00:00',1,'pending'),
  (126,110,'Revisar CLS del navbar','Evitar saltos visuales al cargar.','in_progress','medium','2026-05-26',105,'2026-05-13 09:00:00',2,'in_progress'),
  (127,110,'Versionar CSS para despliegue','Query string de versión anti-cache.','done','low','2026-05-28',3,'2026-05-27 09:00:00',3,'pending'),
  (128,111,'Endpoint móvil de login','Usuario, rol y token para app móvil.','in_progress','high','2026-05-28',110,'2026-05-12 11:00:00',1,'in_progress'),
  (129,111,'Endpoint móvil de proyectos','Proyectos activos para app móvil.','pending','high','2026-06-04',102,'2026-05-14 11:00:00',2,'pending'),
  (130,111,'Endpoint móvil de mensajes','Mensajes con paginación.','pending','medium','2026-06-12',4,'2026-05-16 10:00:00',3,'pending'),
  (131,112,'Diseñar cola offline','Acciones locales y sincronización.','in_progress','high','2026-06-15',110,'2026-05-13 11:00:00',1,'in_progress'),
  (132,112,'Resolver conflictos de edición','Reglas para edición offline.','pending','high','2026-06-25',110,'2026-05-15 12:00:00',2,'pending'),
  (133,113,'Copy de landing para lanzamiento','Texto de hero y beneficios.','done','medium','2026-05-20',103,'2026-05-15 12:00:00',1,'pending'),
  (134,113,'Checklist de redes sociales','Piezas para LinkedIn e Instagram.','in_progress','medium','2026-05-30',109,'2026-05-17 10:00:00',2,'in_progress'),
  (135,113,'Video corto de demostración','Guion y capturas del flujo.','pending','low','2026-06-10',8,'2026-05-20 09:00:00',3,'pending'),
  (136,114,'Revisar exposición de errores PHP','Ocultar errores y registrar logs.','done','high','2026-05-21',102,'2026-05-18 11:00:00',1,'pending'),
  (137,114,'Auditar endpoints sin sesión','Endpoints privados con validación.','in_progress','high','2026-05-31',102,'2026-05-20 10:00:00',2,'in_progress'),
  (138,114,'Checklist HTTPS y cookies','HTTPS, flags secure/httponly y CORS.','pending','high','2026-06-08',106,'2026-05-22 09:00:00',3,'pending');

-- ASIGNACIONES DE TAREAS
INSERT IGNORE INTO task_assignments (task_id, user_id, assigned_at) VALUES
  (1,3,'2026-01-16 09:05:00'),(1,8,'2026-01-16 09:10:00'),(2,3,'2026-01-17 10:05:00'),(3,8,'2026-01-18 11:05:00'),
  (4,3,'2026-01-20 09:05:00'),(4,2,'2026-01-20 09:10:00'),(5,8,'2026-01-22 14:05:00'),(6,4,'2026-02-01 09:05:00'),
  (7,4,'2026-02-02 10:05:00'),(8,4,'2026-02-03 11:05:00'),(9,4,'2026-02-05 09:05:00'),(9,2,'2026-02-05 09:10:00'),
  (10,4,'2026-02-10 10:05:00'),(11,4,'2026-02-12 11:05:00'),(11,2,'2026-02-12 11:10:00'),(12,3,'2026-02-15 09:05:00'),
  (13,4,'2026-03-01 09:05:00'),(14,4,'2026-03-02 10:05:00'),(15,4,'2026-03-03 11:05:00'),(16,3,'2026-03-05 09:05:00'),
  (17,4,'2026-03-10 10:05:00'),(17,2,'2026-03-10 10:10:00'),(18,2,'2026-03-12 11:05:00'),(19,4,'2026-03-15 09:05:00'),
  (20,3,'2026-03-18 10:05:00'),(21,9,'2026-02-05 10:05:00'),(22,9,'2026-02-07 11:05:00'),(23,9,'2026-02-12 09:05:00'),
  (24,9,'2026-02-20 10:05:00'),(25,6,'2026-03-05 08:05:00'),(26,10,'2026-03-07 09:05:00'),(27,10,'2026-03-12 10:05:00'),
  (28,6,'2026-03-20 11:05:00'),(29,4,'2026-04-05 09:05:00'),(30,4,'2026-04-07 10:05:00'),(31,7,'2026-04-10 11:05:00'),
  (32,1,'2026-05-01 08:05:00'),(33,1,'2026-05-02 09:05:00'),(34,2,'2026-05-10 10:05:00'),(35,4,'2026-05-15 11:05:00'),
  (36,3,'2026-05-12 09:05:00'),(37,7,'2026-05-18 10:05:00'),
  (101,103,'2026-05-01 10:05:00'),(101,107,'2026-05-01 10:10:00'),(102,105,'2026-05-02 09:05:00'),(102,103,'2026-05-02 09:10:00'),
  (103,105,'2026-05-04 11:05:00'),(104,102,'2026-05-02 10:05:00'),(105,102,'2026-05-05 10:35:00'),(105,1,'2026-05-05 10:40:00'),
  (106,109,'2026-05-08 09:35:00'),(107,109,'2026-05-03 11:05:00'),(108,109,'2026-05-06 10:05:00'),(108,2,'2026-05-06 10:10:00'),
  (109,2,'2026-05-10 12:05:00'),(109,109,'2026-05-10 12:10:00'),(110,105,'2026-05-04 10:05:00'),(111,103,'2026-05-08 10:25:00'),
  (112,104,'2026-05-05 10:05:00'),(113,104,'2026-05-07 10:05:00'),(114,104,'2026-05-09 11:05:00'),(115,106,'2026-05-06 11:05:00'),
  (115,104,'2026-05-06 11:10:00'),(116,106,'2026-05-08 11:05:00'),(117,108,'2026-05-07 11:05:00'),(118,108,'2026-05-09 10:05:00'),
  (119,10,'2026-05-12 09:05:00'),(120,108,'2026-05-08 12:05:00'),(120,107,'2026-05-08 12:10:00'),(121,6,'2026-05-10 12:05:00'),
  (122,106,'2026-05-10 13:05:00'),(123,102,'2026-05-12 10:05:00'),(123,4,'2026-05-12 10:10:00'),(124,4,'2026-05-14 10:05:00'),
  (125,105,'2026-05-11 10:05:00'),(126,105,'2026-05-13 09:05:00'),(127,3,'2026-05-27 09:05:00'),
  (128,110,'2026-05-12 11:05:00'),(128,102,'2026-05-12 11:10:00'),(129,102,'2026-05-14 11:05:00'),(130,4,'2026-05-16 10:05:00'),
  (130,110,'2026-05-16 10:10:00'),(131,110,'2026-05-13 11:05:00'),(132,110,'2026-05-15 12:05:00'),(133,103,'2026-05-15 12:05:00'),
  (134,109,'2026-05-17 10:05:00'),(135,8,'2026-05-20 09:05:00'),(136,102,'2026-05-18 11:05:00'),(137,102,'2026-05-20 10:05:00'),
  (137,104,'2026-05-20 10:10:00'),(138,106,'2026-05-22 09:05:00');

-- TASK TAGS
INSERT IGNORE INTO task_tags (task_id, tag_id) VALUES
  (1,3),(2,2),(3,3),(4,2),(5,3),(6,1),(7,1),(8,1),(9,10),(10,10),(11,10),(12,2),
  (13,1),(13,9),(14,1),(15,1),(16,2),(16,9),(17,9),(18,9),(19,9),(20,9),
  (21,5),(22,1),(23,2),(24,5),(25,1),(26,7),(27,2),(28,7),(29,1),(30,1),(31,1),
  (32,7),(33,7),(34,8),(35,1),(36,2),(37,5),
  (101,101),(101,10),(102,2),(102,101),(103,2),(104,1),(105,104),(106,102),
  (107,102),(108,102),(109,102),(109,107),(110,2),(111,101),(112,4),(113,4),(113,10),(114,4),(114,9),
  (115,5),(115,4),(116,4),(117,108),(118,108),(119,108),(120,108),(121,7),
  (122,105),(123,105),(123,1),(124,105),(125,105),(126,105),(127,107),
  (128,103),(128,1),(129,103),(130,103),(130,9),(131,103),(132,103),
  (133,102),(134,106),(135,106),(136,104),(137,104),(138,104),(138,107);

-- COMENTARIOS
INSERT IGNORE INTO comments (task_id, user_id, content, created_at) VALUES
  (1,3,'Propuse azul #1B5CFF como primario.','2026-01-18 10:00:00'),
  (1,1,'Aprobado. Seguimos con esa paleta.','2026-01-18 11:00:00'),
  (4,2,'Revisar el hover state en dark mode.','2026-01-28 09:00:00'),
  (4,3,'Ajustado con border-color: var(--card-border).','2026-01-28 10:00:00'),
  (9,4,'Los KPIs cargan bien en proyectos con más de 50 tareas.','2026-02-18 11:00:00'),
  (10,4,'La lógica de archivar está lista. Falta confirmación en frontend.','2026-02-25 09:00:00'),
  (13,4,'Las tablas del chat están creadas con foreign keys correctas.','2026-03-03 09:00:00'),
  (15,4,'El endpoint valida membresía antes de permitir envío.','2026-03-12 10:00:00'),
  (17,4,'La conversación de proyecto se crea en el mismo POST del proyecto.','2026-03-28 09:00:00'),
  (23,9,'El RecyclerView está funcionando con datos estáticos.','2026-03-10 11:00:00'),
  (35,4,'Detecté un N+1 en el endpoint de reportes.','2026-05-16 09:00:00'),
  (35,1,'Agregar un JOIN al query principal.','2026-05-16 10:00:00'),
  (101,107,'El cliente quiere ver entregables y mensajes recientes.','2026-05-02 09:10:00'),
  (109,2,'Documentando el paso de .env en Hostinger.','2026-05-12 16:00:00'),
  (128,110,'La app móvil ya recibe respuesta JSON limpia. Falta persistir token.','2026-05-21 13:00:00'),
  (137,104,'Detecté dos endpoints sin validar sesión.','2026-05-24 12:00:00');

-- ADJUNTOS
INSERT IGNORE INTO attachments (id, task_id, filename, url, uploaded_by, created_at) VALUES
  (101,101,'mapa-navegacion-portal.pdf','/assets/uploads/demo/mapa-navegacion-portal.pdf',103,'2026-05-02 09:00:00'),
  (102,102,'avance-cliente-wireframe.png','/assets/uploads/demo/avance-cliente-wireframe.png',105,'2026-05-05 10:00:00'),
  (103,109,'guia-hostinger-taskcolab.docx','/assets/uploads/demo/guia-hostinger-taskcolab.docx',2,'2026-05-14 12:00:00'),
  (104,113,'evidencia-proyecto-e2e.zip','/assets/uploads/demo/evidencia-proyecto-e2e.zip',104,'2026-05-12 16:00:00'),
  (105,117,'matriz-kpis-ejecutivos.xlsx','/assets/uploads/demo/matriz-kpis-ejecutivos.xlsx',108,'2026-05-13 10:30:00'),
  (106,136,'reporte-seguridad-inicial.pdf','/assets/uploads/demo/reporte-seguridad-inicial.pdf',102,'2026-05-21 11:00:00');

-- CONVERSACIONES
INSERT IGNORE INTO conversations (id, type, title, project_id, task_id, created_by, created_at, last_message_at, is_active) VALUES
  (1,'project','Rediseño TaskColab Web',1,NULL,1,'2026-01-15 08:10:00','2026-05-28 09:30:00',1),
  (2,'project','App Móvil TaskColab',2,NULL,1,'2026-02-01 09:10:00','2026-05-27 15:00:00',1),
  (3,'project','Integración de Reportes',3,NULL,6,'2026-03-01 10:10:00','2026-05-26 11:00:00',1),
  (4,'project','API REST v2',5,NULL,4,'2026-04-01 08:40:00','2026-05-25 14:00:00',1),
  (5,'project','Onboarding de Clientes',6,NULL,6,'2026-04-15 10:10:00','2026-05-24 10:00:00',1),
  (6,'direct',NULL,NULL,NULL,1,'2026-02-01 10:00:00','2026-05-28 08:00:00',1),
  (7,'direct',NULL,NULL,NULL,4,'2026-03-01 11:00:00','2026-05-27 09:00:00',1),
  (8,'direct',NULL,NULL,NULL,3,'2026-04-01 09:00:00','2026-05-26 16:00:00',1),
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

-- MIEMBROS DE CONVERSACIÓN
INSERT IGNORE INTO conversation_members (conversation_id, user_id, role_in_conversation, joined_at) VALUES
  (1,1,'owner','2026-01-15 08:10:00'),(1,3,'member','2026-01-15 08:15:00'),(1,4,'member','2026-01-15 08:15:00'),(1,8,'member','2026-01-16 09:00:00'),(1,2,'member','2026-01-17 10:00:00'),
  (2,1,'owner','2026-02-01 09:10:00'),(2,9,'member','2026-02-01 09:15:00'),(2,4,'member','2026-02-02 10:00:00'),
  (3,6,'owner','2026-03-01 10:10:00'),(3,1,'member','2026-03-01 10:15:00'),(3,10,'member','2026-03-02 09:00:00'),
  (4,4,'owner','2026-04-01 08:40:00'),(4,1,'member','2026-04-01 08:45:00'),(4,7,'member','2026-04-02 09:00:00'),
  (5,6,'owner','2026-04-15 10:10:00'),(5,1,'member','2026-04-15 10:15:00'),(5,3,'member','2026-04-16 09:00:00'),
  (6,1,'member','2026-02-01 10:00:00'),(6,2,'member','2026-02-01 10:00:00'),
  (7,4,'member','2026-03-01 11:00:00'),(7,1,'member','2026-03-01 11:00:00'),
  (8,3,'member','2026-04-01 09:00:00'),(8,8,'member','2026-04-01 09:00:00'),
  (101,107,'owner','2026-05-01 09:05:00'),(101,105,'member','2026-05-01 09:10:00'),(101,102,'member','2026-05-01 09:15:00'),(101,109,'member','2026-05-02 10:00:00'),(101,1,'member','2026-05-02 10:10:00'),
  (102,109,'owner','2026-05-03 10:05:00'),(102,103,'member','2026-05-03 10:10:00'),(102,105,'member','2026-05-04 09:00:00'),(102,2,'member','2026-05-04 09:20:00'),
  (103,104,'owner','2026-05-05 08:35:00'),(103,102,'member','2026-05-05 08:45:00'),(103,106,'member','2026-05-05 09:00:00'),(103,4,'member','2026-05-06 11:00:00'),
  (104,108,'owner','2026-05-07 09:35:00'),(104,6,'member','2026-05-07 09:45:00'),(104,1,'member','2026-05-07 10:00:00'),(104,107,'member','2026-05-08 09:00:00'),
  (105,106,'owner','2026-05-10 11:05:00'),(105,102,'member','2026-05-10 11:10:00'),(105,4,'member','2026-05-10 11:20:00'),(105,7,'member','2026-05-11 08:30:00'),
  (106,110,'owner','2026-05-12 09:05:00'),(106,9,'member','2026-05-12 09:10:00'),(106,102,'member','2026-05-12 09:20:00'),(106,4,'member','2026-05-13 09:00:00'),(106,1,'member','2026-05-13 09:20:00'),
  (107,103,'owner','2026-05-15 10:25:00'),(107,107,'member','2026-05-15 10:30:00'),(107,109,'member','2026-05-15 10:40:00'),(107,8,'member','2026-05-16 09:00:00'),
  (108,102,'owner','2026-05-18 08:50:00'),(108,106,'member','2026-05-18 09:00:00'),(108,1,'member','2026-05-18 09:15:00'),(108,104,'member','2026-05-18 09:30:00'),
  (109,110,'owner','2026-05-20 09:00:00'),(109,102,'member','2026-05-20 09:05:00'),(109,4,'member','2026-05-20 09:10:00'),
  (110,107,'member','2026-05-22 10:00:00'),(110,1,'member','2026-05-22 10:00:00'),
  (111,104,'member','2026-05-23 11:00:00'),(111,102,'member','2026-05-23 11:00:00');

-- MENSAJES
INSERT IGNORE INTO messages (id, conversation_id, user_id, body, created_at) VALUES
  (1,1,1,'¡Hola equipo! Arrancamos el sprint de rediseño hoy.','2026-01-15 08:15:00'),
  (2,1,3,'Propongo usar Inter como tipografía principal.','2026-01-15 09:00:00'),
  (3,1,4,'Los colores están listos en variables CSS.','2026-01-20 10:00:00'),
  (4,1,2,'¿Quién se encarga del componente de tarjeta Kanban?','2026-01-28 11:00:00'),
  (5,1,3,'Yo lo tengo en progreso. Lo termino hoy.','2026-01-28 11:30:00'),
  (6,1,1,'Pasamos al módulo de Proyectos desde mañana.','2026-02-01 08:00:00'),
  (7,1,4,'Las tablas de proyectos ya están. Los endpoints funcionan.','2026-02-15 10:00:00'),
  (8,2,1,'Arranquemos con el MVP de Android. Jorge lidera tableros.','2026-02-01 09:15:00'),
  (9,2,9,'La pantalla de login ya conecta con la API.','2026-02-22 14:00:00'),
  (10,3,6,'El template PDF está listo con logo y tabla de tareas.','2026-03-15 08:10:00'),
  (11,4,4,'Documenté los 23 endpoints actuales en el README.','2026-04-07 09:10:00'),
  (12,6,1,'Hola, ¿pudiste revisar las tareas del tablero general?','2026-05-28 08:00:00'),
  (13,6,2,'Sí, las que están en progreso las termino hoy.','2026-05-28 08:05:00'),
  (14,7,4,'Las consultas SQL del reporte bajaron de 2s a 300ms.','2026-05-27 09:00:00'),
  (15,7,1,'Excelente trabajo. Documéntalo en el commit.','2026-05-27 09:15:00'),
  (101,101,107,'El portal debe mostrar avance sin que el cliente entre al tablero interno.','2026-05-01 09:15:00'),
  (102,101,102,'Desde backend validaré permisos por project_members.','2026-05-02 10:00:00'),
  (103,102,109,'Ya está la primera guía de primeros pasos.','2026-05-08 10:00:00'),
  (104,103,104,'La suite E2E ya cubre login, logout y creación de proyecto.','2026-05-12 08:30:00'),
  (105,105,106,'El endpoint de proyectos ya responde más rápido.','2026-05-20 10:00:00'),
  (106,106,110,'Para móvil propongo usar token y no depender de cookies PHP.','2026-05-20 09:10:00'),
  (107,106,102,'De acuerdo. Creo endpoint dedicado de login móvil.','2026-05-20 09:20:00'),
  (108,108,102,'Revisé errores PHP visibles. Deben quedar ocultos en producción.','2026-05-21 09:00:00'),
  (109,109,110,'El login móvil ya responde usuario y token en JSON.','2026-05-25 09:00:00'),
  (110,110,107,'¿Ya puedo revisar la demo del dashboard ejecutivo?','2026-05-28 11:30:00'),
  (111,110,1,'Sí, está listo. Revisa filtros por fecha.','2026-05-28 12:00:00'),
  (112,111,104,'Luis, encontré un endpoint antiguo sin validación de sesión.','2026-05-27 12:30:00'),
  (113,111,102,'Gracias, lo cierro hoy y agrego prueba de regresión.','2026-05-27 13:00:00');

-- ACTIVIDAD
INSERT IGNORE INTO activity_logs (user_id, entity_type, entity_id, action, details, created_at) VALUES
  (1,'project',1,'create','{"name":"Rediseño TaskColab Web"}','2026-01-15 08:00:00'),
  (4,'task',6,'create','{"title":"Crear tabla projects en BD"}','2026-02-01 09:00:00'),
  (4,'task',6,'update','{"status":"done"}','2026-02-10 12:00:00'),
  (3,'task',1,'update','{"status":"done"}','2026-01-25 16:00:00'),
  (1,'project',7,'archive','{"name":"Migración a Servidor"}','2026-04-02 10:00:00'),
  (107,'project',101,'create','{"name":"Portal de Clientes TaskColab"}','2026-05-01 09:00:00'),
  (106,'project',105,'create','{"name":"Optimización de Rendimiento"}','2026-05-10 11:00:00'),
  (102,'project',108,'create','{"name":"Auditoría de Seguridad"}','2026-05-18 08:45:00'),
  (102,'task',136,'update','{"status":"done"}','2026-05-21 12:00:00');

-- NOTIFICACIONES
INSERT IGNORE INTO notifications (user_id, title, body, is_read, created_at) VALUES
  (2,'Nueva tarea asignada','Se te asignó "Vincular tableros a proyectos".', 0,'2026-02-12 11:10:00'),
  (3,'Comentario en tu tarea','Reniery comentó en "Componente de tarjeta de tarea".', 0,'2026-01-28 09:05:00'),
  (4,'Proyecto archivado','El proyecto "Migración a Servidor" fue archivado.', 1,'2026-04-02 10:05:00'),
  (9,'Nueva tarea asignada','Se te asignó "Módulo de tableros Kanban".', 1,'2026-02-12 09:10:00'),
  (1,'Tarea completada','Keyra marcó "Definir paleta de colores" como completada.', 1,'2026-01-25 16:05:00'),
  (110,'Endpoint móvil listo','El login móvil ya devuelve JSON para consumir desde Android.', 0,'2026-05-25 09:05:00'),
  (102,'Alerta de seguridad','Se detectó endpoint antiguo sin validación de sesión.', 0,'2026-05-27 13:05:00'),
  (1,'Demo completa','Se agregaron datos de demo para producción.', 0,'2026-05-28 13:30:00');

SET FOREIGN_KEY_CHECKS = 1;
