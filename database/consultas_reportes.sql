-- Seleccionar primero la base. Consultas de solo lectura, sin contraseñas.
SELECT s.id,s.name,s.program,s.semester,u.email AS correo_acceso,u.active
FROM students s LEFT JOIN users u ON u.student_id=s.id ORDER BY s.name;
SELECT t.id,t.subject,t.day,t.start_time,t.end_time,u.email AS cuenta_docente,s.id AS estudiante,s.name
FROM teachers t LEFT JOIN users u ON u.id=t.teacher_user_id
LEFT JOIN class_enrollments e ON e.teacher_id=t.id LEFT JOIN students s ON s.id=e.student_id ORDER BY t.subject,s.name;
SELECT c.class_date,c.subject,c.teacher_name,u.email AS cuenta_docente,s.id,s.name,a.status,a.method,a.created_at
FROM attendance a JOIN students s ON s.id=a.student_id LEFT JOIN class_sessions c ON c.id=a.session_id
LEFT JOIN users u ON u.id=c.teacher_user_id ORDER BY a.created_at DESC;
SELECT id,subject,name FROM teachers WHERE teacher_user_id IS NULL;
SELECT id,student_id,status,created_at FROM attendance WHERE session_id IS NULL;
