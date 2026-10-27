-- SOLO sobre la estructura original de siete tablas, una vez y con respaldo.
-- Ruta recomendada e idempotente: actualizar.php. No ejecutar ambas rutas.
ALTER TABLE users MODIFY role ENUM('admin','gestor','docente','consulta','estudiante') NOT NULL;
ALTER TABLE users ADD student_id VARCHAR(30) NULL, ADD UNIQUE KEY user_student_unique(student_id), ADD CONSTRAINT user_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE RESTRICT;
ALTER TABLE teachers ADD teacher_user_id VARCHAR(36) NULL, ADD CONSTRAINT teacher_account FOREIGN KEY(teacher_user_id) REFERENCES users(id) ON DELETE SET NULL;
CREATE TABLE IF NOT EXISTS class_sessions (
 id VARCHAR(36) PRIMARY KEY,
 teacher_id VARCHAR(36) NULL,
 teacher_user_id VARCHAR(36) NULL,
 class_date DATE NOT NULL,
 subject VARCHAR(120) NOT NULL,
 teacher_name VARCHAR(100) NOT NULL,
 program VARCHAR(32) NOT NULL,
 semester VARCHAR(3) NOT NULL,
 start_time TIME NOT NULL,
 end_time TIME NOT NULL,
 CONSTRAINT class_account FOREIGN KEY(teacher_user_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT class_teacher FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
 UNIQUE KEY class_day(teacher_id,class_date),
 INDEX class_roster(class_date,program,semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE attendance ADD session_id VARCHAR(36) NULL, ADD UNIQUE KEY attendance_once(student_id,session_id), ADD CONSTRAINT attendance_class FOREIGN KEY(session_id) REFERENCES class_sessions(id) ON DELETE RESTRICT;
CREATE TABLE IF NOT EXISTS class_enrollments (
 teacher_id VARCHAR(36) NOT NULL,
 student_id VARCHAR(30) NOT NULL,
 created_at VARCHAR(32) NOT NULL,
 PRIMARY KEY(teacher_id,student_id),
 CONSTRAINT enrollment_class FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
 CONSTRAINT enrollment_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_sequences (
 name VARCHAR(40) PRIMARY KEY,
 last_value BIGINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
