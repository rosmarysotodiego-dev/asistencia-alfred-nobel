# SCAE Alfred Nobel — Cuentas, clases y contraseñas

Esta guía corresponde al paquete actualizado. Sustituye las instrucciones anteriores sobre usuarios y contraseñas automáticas. Incluye asistencia manual, accesibilidad, matrícula individual y acceso docente por clase.

## 1. Instalar esta actualización en tu equipo

1. Exportar `nobel_asistencia_v2` desde phpMyAdmin (estructura y datos) y guardar una copia de la carpeta actual.
2. Extraer el ZIP y copiar el contenido de `nobel-xampp` sobre `C:\xampp2\htdocs\nobel-xampp\`, aceptando reemplazar los archivos del sistema.
3. CONSERVAR `private\config.php` y `private\installed.lock` existentes. El ZIP no los incluye. La conexión debe seguir apuntando a la base que ya funciona: `nobel_asistencia_v2`. No utilizar la base anterior que mostró el error #1932.
4. Mantener Apache y MySQL iniciados. En el propio servidor abrir `http://localhost/nobel-xampp/actualizar.php` y pulsar Actualizar base de datos. Pausar el uso del sistema mientras se actualiza.
5. Si hay error, revisar el log de Apache. La migración PHP comprueba si existen las columnas antes de agregarlas y puede repetirse después de corregir el problema. MySQL/MariaDB no revierte automáticamente todo el DDL si falla una operación intermedia.
6. Abrir `http://localhost/nobel-xampp/`, pulsar Ctrl+F5 e iniciar sesión con el correo y contraseña de la cuenta actual. Las contraseñas existentes no se cambian por la migración.
7. Asignar expresamente las cuentas docentes a sus clases y matricular los estudiantes. No se asignan personas automáticamente por coincidencias de nombre o correo.

La actualización agrega class_enrollments y los vínculos teachers.teacher_user_id y class_sessions.teacher_user_id. Las asistencias existentes no se eliminan. Antes de completar los vínculos, las cuentas docentes pueden ver listas vacías y los alumnos no verán clases nuevas: deben configurarse las asignaciones y matrículas.

Para una instalación nueva: copiar la carpeta en htdocs, abrir install.php, elegir una base nueva, indicar conexión y correo/contraseña del administrador. El instalador crea diez tablas y el administrador. No importar SQL sin ejecutar el instalador, ya que también se necesita config.php e installed.lock.

## 2. Relación correcta entre personas y clases

Una cuenta permite iniciar sesión. Una cuenta de rol Estudiante se vincula con la persona correspondiente en el padrón; esta relación no asigna docentes. Solo al elegir Estudiante aparece ese campo.

La cuenta Docente se asigna en Docentes y clases a una o varias clases. Los estudiantes se matriculan en cada clase con el botón Matrícula. Un estudiante puede pertenecer a varias clases, y una clase puede tener varios estudiantes. El selector de matrícula muestra únicamente personas del programa y semestre de esa clase.

Orden recomendado: registrar el padrón → crear cuenta docente → crear o editar la clase y asignar esa cuenta → seleccionar Matrícula → elegir estudiantes → crear las cuentas de estudiante vinculadas al padrón.

El administrador y el gestor gestionan clases y matrículas. El docente crea y edita sus propias clases dentro de su carrera y consulta sus estudiantes inscritos, sin modificar marcaciones. En Asistencia puede filtrar por clase y fecha. Cada estudiante consulta su propio historial y confirma su asistencia en clases matriculadas durante su horario, sin huella ni código.

## 3. Crear cuentas con correo y contraseña elegida

Abrir Usuarios y permisos → Crear usuario. Completar nombre, correo, rol y estado. Para Docente, indicar su programa. Para Estudiante, seleccionar su registro del padrón. Escribir la contraseña asignada y confirmarla. No se generan nombres de usuario aleatorios ni contraseñas automáticas.

El usuario de acceso de las cuentas nuevas es exactamente su correo normalizado a minúsculas. Las cuentas antiguas ya admiten el correo y mantienen su contraseña. Al editar sus permisos se actualiza username para que coincida con el correo. Los antiguos alias pueden seguir funcionando hasta esa edición por compatibilidad. El correo de una cuenta existente permanece de solo lectura en esta versión.

La contraseña nueva debe tener al menos ocho caracteres y no superar 72 bytes UTF-8 (las letras con acentos y otros caracteres pueden ocupar varios bytes). No se recortan espacios de contraseñas ni se guardan en texto plano. Se almacenan con password_hash y se verifican con password_verify. No se devuelven contraseñas en la respuesta de la API ni se registran en la bitácora.

## 4. Cambio personal de contraseña

En la pantalla de ingreso existe Cambiar contraseña. Dentro del sistema existe Cambiar mi contraseña, en la barra superior. Ambos abren un formulario con correo, contraseña actual, contraseña nueva y confirmación.

Se comprueba la contraseña actual, incluida la que asignó el administrador. Se exige que la nueva coincida con su confirmación y sea distinta de la anterior. Si la verificación falla, no se modifica la cuenta. Al completarse, se eliminan todas las sesiones de esa persona y se vuelve al ingreso para utilizar su nueva contraseña. La operación queda como password.change en auditoría. No es recuperación por correo ni permite omitir la contraseña actual.

Si olvidó la contraseña, debe solicitar al administrador que la restablezca. No se implementó envío de correos ni enlaces de recuperación.

## 5. Restablecimiento por el administrador

Usuarios y permisos → localizar la cuenta → Cambiar contraseña. Escribir una contraseña nueva y confirmarla. No se requiere la contraseña anterior del titular porque esta acción está autorizada solo al administrador.

Se invalidan las sesiones de la cuenta afectada y se limpian sus intentos de acceso bloqueados por correo/alias para que pueda entrar. La cuenta mantiene su correo y permisos. La operación queda como user.password.reset. Si el administrador cambia su propia contraseña por esta vía, también tendrá que volver a iniciar sesión.

La contraseña debe comunicarse individualmente al titular por el medio institucional acordado. No se muestra la contraseña anterior ni se envía automáticamente. Esta versión permite cambiar la contraseña; no obliga a cambiarla en el primer ingreso.

## 6. Sesiones de clase e historial

Una fila de teachers representa una asignación docente–curso–día–horario. Cada sesión diaria de class_sessions se crea con la primera marcación válida y guarda una copia del curso, docente y horario. Las nuevas marcaciones del personal requieren clase y matrícula y quedan vinculadas a esa sesión, además del código de estudiante conservado en ese flujo. El docente tiene acceso de consulta, no registro del personal.

Los registros anteriores sin session_id permanecen disponibles al administrador y al propio estudiante, pero no se atribuyen a un docente por suposiciones. Al vincular una cuenta docente por primera vez a una clase, las sesiones históricas de esa clase que no tenían cuenta responsable pasan a esa cuenta. Las sesiones que ya tenían responsable conservan su responsable histórico si la clase cambia de docente.

Retirar una matrícula conserva las marcaciones anteriores. El estudiante deja de poder marcar en esa clase. Eliminar una clase retira sus matrículas, pero conserva sus sesiones y asistencias. Eliminar un estudiante mantiene el comportamiento heredado: elimina sus asistencias y matrículas; si tiene cuenta vinculada, la FK impide eliminarlo hasta desvincular esa cuenta.

No se añadieron periodos académicos, secciones, feriados, sesiones canceladas, matrícula automática ni cálculo automático de faltas/tardanzas. El autoregistro queda como Presente. Las clases deben tener inicio y fin dentro del mismo día. Antes de calcular porcentajes académicos se debe definir el total de sesiones esperadas.

## 7. Roles

| Rol | Alcance |
|---|---|
| Administrador | Cuentas, contraseñas, padrón, clases, matrículas, asistencia y auditoría. |
| Gestor académico | Padrón, clases, matrículas y asistencia, sin gestión de cuentas o contraseñas ajenas. |
| Docente | Crea y edita sus clases de su carrera; consulta estudiantes inscritos y asistencias asociadas. |
| Estudiante | Consulta sus datos e historial y marca su propia asistencia en clases matriculadas. |
| Consulta | Consulta general de información académica, sin cambios. |

Los permisos se comprueban en la API además de ocultar controles. El servidor obtiene al alumno desde su cuenta, verifica su matrícula y usa el reloj del servidor en America/Lima. UNIQUE(student_id,session_id) evita doble asistencia por sesión. Contraseñas restablecidas o cambios de rol/vínculo/estado revocan las sesiones correspondientes.

## 8. Diccionario completo: diez tablas

Motor InnoDB, juego utf8mb4, cotejamiento utf8mb4_unicode_ci. PHP 8.1 o superior, PDO MySQL. PK = clave primaria, FK = clave foránea, NULL = opcional. El resto de campos son obligatorios salvo valor predeterminado. La estructura conserva los nombres usados por el sistema.

### students

| Campo | Tipo | Regla | Descripción |
|---|---|---|---|
| id | VARCHAR(30) | PK | Código institucional. Los nuevos se generan en el servidor; los anteriores se conservan. |
| name | VARCHAR(100) | NOT NULL | Nombres y apellidos. |
| dni | CHAR(8) | NULL, UNIQUE | Documento de ocho dígitos. Vacío se guarda como NULL. |
| program | VARCHAR(32) | NOT NULL | APSTI, ENFERMERÍA o FISIOTERAPIA. |
| semester | VARCHAR(3) | NOT NULL | I, II, III, IV, V o VI. |
| verification_hash | VARCHAR(255) | NOT NULL | Hash del código de seis dígitos usado en el registro del personal. No se expone al estudiante. |

### users

| Campo | Tipo | Regla | Descripción |
|---|---|---|---|
| id | VARCHAR(36) | PK | UUID de la cuenta. Administrador inicial: primary-admin. |
| username | VARCHAR(120) | UNIQUE | Correo de acceso en cuentas nuevas o editadas. Los identificadores antiguos se conservan hasta editar la cuenta. |
| email | VARCHAR(120) | UNIQUE | Correo de la cuenta, también válido para iniciar sesión. |
| name | VARCHAR(100) | NOT NULL | Nombre visible de la cuenta. |
| role | ENUM('admin','gestor','docente','consulta','estudiante') | NOT NULL | Define permisos en el servidor. |
| program | VARCHAR(32) | NULL | Alcance de docente. Para estudiante se obtiene del padrón al guardar. |
| active | TINYINT | DEFAULT 1 | 1 activo y 0 inactivo. |
| student_id | VARCHAR(30) | NULL, UNIQUE, FK | Vincula una cuenta de rol estudiante con students.id. |
| password_hash | VARCHAR(255) | NOT NULL | Hash PHP PASSWORD_DEFAULT. Nunca contraseña en texto plano. |

### teachers

| Campo | Tipo | Regla | Descripción |
|---|---|---|---|
| id | VARCHAR(36) | PK | UUID de la asignación. |
| teacher_user_id | VARCHAR(36) | NULL, FK → users.id | Cuenta docente responsable. ON DELETE SET NULL. El vínculo se asigna explícitamente. |
| name | VARCHAR(100) | NOT NULL | Nombre del docente; puede repetirse en distintos cursos. |
| email | VARCHAR(120) | NULL | Contacto opcional. La API antigua usa cadena vacía cuando no se indica. |
| program | VARCHAR(32) | NOT NULL | Programa de la clase. |
| semester | VARCHAR(3) | NOT NULL | Semestre de la clase. |
| subject | VARCHAR(120) | NOT NULL | Curso o unidad didáctica. |
| day | VARCHAR(16) | NOT NULL | Día semanal, de Lunes a Sábado. |
| start_time | TIME | NOT NULL | Inicio del horario local. |
| end_time | TIME | NOT NULL | Fin posterior al inicio. Índice teacher_schedule(name,day,start_time,end_time) para consultas de cruce. |

### class_sessions

| Campo | Tipo | Regla | Descripción |
|---|---|---|---|
| id | VARCHAR(36) | PK | UUID de una clase en una fecha concreta. |
| teacher_id | VARCHAR(36) | NULL, FK | Asignación teachers que originó la sesión. |
| teacher_user_id | VARCHAR(36) | NULL, FK → users.id | Cuenta docente responsable al crear la sesión. Conserva el acceso al historial de esa sesión. ON DELETE SET NULL. |
| class_date | DATE | NOT NULL | Fecha local de la sesión. UNIQUE(teacher_id,class_date). |
| subject | VARCHAR(120) | NOT NULL | Copia del nombre del curso al crear la sesión. |
| teacher_name | VARCHAR(100) | NOT NULL | Copia del nombre del docente para conservar historia. |
| program | VARCHAR(32) | NOT NULL | Copia del programa. |
| semester | VARCHAR(3) | NOT NULL | Copia del semestre. |
| start_time | TIME | NOT NULL | Copia del inicio del horario. |
| end_time | TIME | NOT NULL | Copia del fin. Índice class_roster(class_date,program,semester). |

### attendance

| Campo | Tipo | Regla | Descripción |
|---|---|---|---|
| id | VARCHAR(36) | PK | UUID de la marcación. |
| session_id | VARCHAR(36) | NULL, FK | Sesión correspondiente; NULL para registros anteriores o del personal. |
| student_id | VARCHAR(30) | FK, NOT NULL | Estudiante que registra. UNIQUE(student_id,session_id). |
| status | VARCHAR(16) | NOT NULL | Presente, Tardanza o Falta. Autoregistro de estudiante: Presente. |
| method | VARCHAR(16) | NOT NULL | Manual en esta versión. No simula lecturas físicas. |
| created_at | VARCHAR(32) | NOT NULL | Instante UTC ISO 8601, por ejemplo 2026-10-22T13:15:00.000Z. Se conserva formato del sistema original. |
| actor | VARCHAR(120) | NOT NULL | Correo de quien registra. Se obtiene de la sesión autenticada. Índice attendance_order(created_at). |

### sessions

| Campo | Tipo | Regla | Descripción |
|---|---|---|---|
| token_hash | CHAR(64) | PK | SHA-256 del token de sesión, sin almacenar el token original en esta tabla. |
| user_id | VARCHAR(36) | FK, NOT NULL | Cuenta autenticada. |
| expires_at | BIGINT | NOT NULL | Vencimiento Unix en milisegundos; sesión de ocho horas en esta versión. |

### login_attempts

| Campo | Tipo | Regla | Descripción |
|---|---|---|---|
| email | VARCHAR(190) | PK | Identificador de acceso intentado, puede ser usuario o correo pese al nombre heredado. |
| attempts | INT | NOT NULL | Cantidad de intentos fallidos. |
| expires_at | BIGINT | NOT NULL | Fin del bloqueo temporal en milisegundos. Diez intentos en ventana de quince minutos. |

### audit

| Campo | Tipo | Regla | Descripción |
|---|---|---|---|
| id | VARCHAR(36) | PK | UUID de la operación. |
| actor | VARCHAR(120) | NOT NULL | Correo de quien ejecutó la acción. |
| action | VARCHAR(40) | NOT NULL | Operación: login, user.save, student.create, attendance.self, etc. |
| target | VARCHAR(120) | NOT NULL | ID del registro afectado, sin vínculo FK. |
| created_at | VARCHAR(32) | NOT NULL | Instante UTC ISO 8601. Índice audit_order(created_at). |

### class_enrollments

| Campo | Tipo | Regla | Descripción |
|---|---|---|---|
| teacher_id | VARCHAR(36) | PK compuesta, FK → teachers.id | Clase en la que se matricula al estudiante. ON DELETE CASCADE. |
| student_id | VARCHAR(30) | PK compuesta, FK → students.id | Estudiante matriculado. ON DELETE CASCADE. |
| created_at | VARCHAR(32) | NOT NULL | Instante UTC ISO 8601 de la matrícula. Una matrícula por clase y estudiante. |

### student_sequences

| Campo | Tipo | Regla | Descripción |
|---|---|---|---|
| name | VARCHAR(40) | PK | Nombre del contador, actualmente students. |
| last_value | BIGINT UNSIGNED | NOT NULL, DEFAULT 0 | Último número confirmado. Se lee con bloqueo de fila y se actualiza en la transacción de alta. |

## 9. Verificación en tu XAMPP

1. Exportar la base y ejecutar actualizar.php. Repetir la actualización para comprobar que no duplica columnas o tablas.
2. Crear dos cuentas docentes con correos distintos. Verificar que no aparece el selector de estudiante en ellas.
3. Crear dos clases, asignar una a cada docente y matricular distintos estudiantes. Un alumno puede estar en ambas si corresponde a su programa/semestre.
4. Crear una cuenta estudiante con contraseña elegida. Entrar con su correo y marcar durante el horario de la clase.
5. Entrar como cada docente: comprobar que solo obtiene sus clases, alumnos y asistencias. Probar los filtros por clase y fecha.
6. Intentar marcar sin matrícula, con otra cuenta, fuera del horario o dos veces en la misma sesión. Debe rechazarse sin crear filas nuevas.
7. Cambiar contraseña con la actual incorrecta, confirmación distinta, contraseña corta y contraseña correcta. Solo el último caso válido debe modificarla y cerrar las sesiones.
8. Restablecer una contraseña como administrador. La anterior ya no debe funcionar, la nueva sí. Una cuenta docente, estudiante o gestor no debe poder restablecer contraseñas de otros.
9. Comprobar voz, Tab, alto contraste y tamaño de texto tras la actualización.

Los controles realmente ejecutados en este entorno se describen en VERIFICACION_ACTUALIZACION.md. La instalación, el SQL y la API deben validarse contra una copia de la base real antes de reanudar su uso institucional.

## 10. Código institucional correlativo

Los estudiantes nuevos reciben códigos 001, 002, 003 y así sucesivamente. Después de 999 sigue 1000, 1001, etc., sin reiniciar cada año o semestre. El código no cambia al editar al estudiante. No se renumeran los registros anteriores ni se afectan cuentas, matrículas o asistencias vinculadas a ellos.

student_sequences almacena el último correlativo asignado. La API bloquea su fila con FOR UPDATE dentro de la transacción que crea al estudiante. La clave primaria de students impide duplicados. Si un código ya existe por datos anteriores, se salta ese valor. Eliminar un estudiante no retrocede el contador ni reutiliza un correlativo ya confirmado. Una transacción que falla no consume un número. No es un contador matemáticamente infinito: utiliza BIGINT con el límite de enteros del servidor, suficiente para el volumen institucional.

En Usuarios y permisos, el selector de estudiante asociado muestra solo nombre, carrera, semestre y DNI cuando existe. El código se conserva internamente como vínculo y sigue apareciendo en el padrón institucional, no se usa como nombre de acceso: se ingresa con correo y contraseña.

El semestre del padrón determina qué clases puede elegir el alumno. La matrícula administrativa automática queda desactivada por defecto. El estudiante se inscribe en las clases de su carrera y semestre al ingresar. El código institucional es distinto del código de verificación de seis dígitos del registro del personal.

## 11. Inscripción elegida por el estudiante

El flujo habitual es: el administrador crea la cuenta Docente indicando su carrera; el docente ingresa y abre Docentes y clases → Agregar docente / clase, indica curso, semestre, día y horario. La cuenta docente responsable se obtiene de la sesión y solo se permiten clases de su carrera. El docente puede editar sus propias clases, pero no las de otros docentes. No se agregó eliminación de clases para el docente.

El estudiante ingresa con su correo y contraseña y abre Inicio. En Clases disponibles para inscribirme ve únicamente clases de su carrera y semestre con una cuenta docente activa. Pulsa Inscribirme en esta clase. La inscripción se guarda para su propio registro, nunca para un estudiante enviado por el navegador. La clase pasa a Mis clases inscritas. Solo si corresponde al día de hoy aparecerá además en Registrar asistencia — clases de hoy; la marcación sigue limitada al horario. Inscribirse no registra asistencia automáticamente.

No se necesita que el estudiante elija al docente directamente: elige la clase, que ya tiene un docente responsable. La inscripción no requiere aprobación previa. Si se necesita aprobación institucional, debe añadirse ese estado en otro cambio. No se incluyó retiro voluntario de matrícula, el administrador o gestor puede gestionarlo desde Matrícula.

La matrícula automática administrativa al crear estudiantes queda DESACTIVADA por defecto, para que cada alumno elija sus clases. Sigue disponible como alternativa expresa del administrador. Las matrículas anteriores se conservan, por lo que esas clases ya aparecen inscritas y no se ofrecen nuevamente. Esta entrega agrega el contador student_sequences: ejecutar actualizar.php antes de crear estudiantes nuevos.

Prueba: crear una clase de un docente de APSTI, semestre VI; entrar como alumno APSTI VI y comprobar que aparece para inscripción. Entrar como alumno de otra carrera o semestre y comprobar que no se ofrece ni se permite inscribir por API. Inscribirse, revisar la lista y marcar durante el horario. Entrar como docente y verificar que el alumno y su asistencia se muestran solo en su clase.

## 12. Editar estudiante

Editar estudiante: en Estudiantes → Acción ahora aparece Editar en lugar de Nuevo código de verificación. Permite actualizar nombre, DNI, carrera y semestre. El código institucional es de solo lectura. Si el cambio deja matrículas incompatibles, se solicita marcar la opción para retirarlas. Se conservan las asistencias históricas, no se inscribe al alumno automáticamente en otras clases. Su cuenta vinculada actualiza nombre y programa; al cambiar carrera/semestre se cierran sus sesiones para volver a ingresar. La edición mantiene el código institucional. Para instalar esta entrega con contador debe ejecutarse actualizar.php, conservando la configuración existente.

## 13. Actualizar la numeración

Respaldar la base sana nobel_asistencia_v2 y la carpeta del sistema. Copiar los archivos de esta entrega sobre C:\xampp2\htdocs\nobel-xampp\ conservando private/config.php e installed.lock. Abrir http://localhost/nobel-xampp/actualizar.php en el servidor y actualizar, porque ahora se necesita la tabla student_sequences. Después abrir el sistema con Ctrl+F5. No reinstalar ni borrar las bases antiguas. La secuencia empieza en 001 para los nuevos registros, omitiendo los códigos exactos que ya existan. Probar dos altas consecutivas, comprobar el selector al crear usuario y verificar que los registros anteriores mantienen su código.
