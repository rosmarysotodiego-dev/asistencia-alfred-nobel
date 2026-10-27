-- SCAE Alfred Nobel. Instalación nueva. Para base existente usar actualizar.php.
CREATE TABLE IF NOT EXISTS students (
 id VARCHAR(30) PRIMARY KEY,name VARCHAR(100) NOT NULL,dni CHAR(8) NULL UNIQUE,
 program VARCHAR(32) NOT NULL,semester VARCHAR(3) NOT NULL,verification_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS users (
 id VARCHAR(36) PRIMARY KEY,
 username VARCHAR(120) NOT NULL UNIQUE,
 email VARCHAR(120) NOT NULL UNIQUE,
 name VARCHAR(100) NOT NULL,
 role ENUM('admin','gestor','docente','consulta','estudiante') NOT NULL,
 program VARCHAR(32) NULL,
 active TINYINT NOT NULL DEFAULT 1,
 student_id VARCHAR(30) NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 CONSTRAINT user_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS teachers (
 id VARCHAR(36) PRIMARY KEY,teacher_user_id VARCHAR(36) NULL,name VARCHAR(100) NOT NULL,email VARCHAR(120) NULL,program VARCHAR(32) NOT NULL,
 semester VARCHAR(3) NOT NULL,subject VARCHAR(120) NOT NULL,day VARCHAR(16) NOT NULL,
 start_time TIME NOT NULL,end_time TIME NOT NULL,
 CONSTRAINT teacher_account FOREIGN KEY(teacher_user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX teacher_schedule(name,day,start_time,end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
CREATE TABLE IF NOT EXISTS attendance (
 id VARCHAR(36) PRIMARY KEY,session_id VARCHAR(36) NULL,student_id VARCHAR(30) NOT NULL,status VARCHAR(16) NOT NULL,method VARCHAR(16) NOT NULL,
 created_at VARCHAR(32) NOT NULL,actor VARCHAR(120) NOT NULL,
 CONSTRAINT attendance_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
 CONSTRAINT attendance_class FOREIGN KEY(session_id) REFERENCES class_sessions(id) ON DELETE RESTRICT,
 UNIQUE KEY attendance_once(student_id,session_id),
 INDEX attendance_order(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS sessions (
 token_hash CHAR(64) PRIMARY KEY,user_id VARCHAR(36) NOT NULL,expires_at BIGINT NOT NULL,
 CONSTRAINT session_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS login_attempts (
 email VARCHAR(190) PRIMARY KEY,attempts INT NOT NULL,expires_at BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS audit (
 id VARCHAR(36) PRIMARY KEY,actor VARCHAR(120) NOT NULL,action VARCHAR(40) NOT NULL,target VARCHAR(120) NOT NULL,
 created_at VARCHAR(32) NOT NULL,INDEX audit_order(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
