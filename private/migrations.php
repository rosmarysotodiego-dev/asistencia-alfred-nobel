<?php
declare(strict_types=1);
function migrate_manual(PDO $db): void {
 $column=function(string $table,string $name)use($db):bool{$s=$db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');$s->execute([$table,$name]);return (bool)$s->fetchColumn();};
 $db->exec("ALTER TABLE users MODIFY role ENUM('admin','gestor','docente','consulta','estudiante') NOT NULL");
 if(!$column('users','student_id'))$db->exec('ALTER TABLE users ADD student_id VARCHAR(30) NULL, ADD UNIQUE KEY user_student_unique(student_id), ADD CONSTRAINT user_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE RESTRICT');
 foreach(preg_split('/;\s*/',file_get_contents(__DIR__.'/../database/schema.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
 if(!$column('attendance','session_id'))$db->exec('ALTER TABLE attendance ADD session_id VARCHAR(36) NULL, ADD UNIQUE KEY attendance_once(student_id,session_id), ADD CONSTRAINT attendance_class FOREIGN KEY(session_id) REFERENCES class_sessions(id) ON DELETE RESTRICT');
 if(!$column('teachers','teacher_user_id'))$db->exec('ALTER TABLE teachers ADD teacher_user_id VARCHAR(36) NULL, ADD CONSTRAINT teacher_account FOREIGN KEY(teacher_user_id) REFERENCES users(id) ON DELETE SET NULL');
 if(!$column('class_sessions','teacher_user_id'))$db->exec('ALTER TABLE class_sessions ADD teacher_user_id VARCHAR(36) NULL, ADD CONSTRAINT class_account FOREIGN KEY(teacher_user_id) REFERENCES users(id) ON DELETE SET NULL');

}
