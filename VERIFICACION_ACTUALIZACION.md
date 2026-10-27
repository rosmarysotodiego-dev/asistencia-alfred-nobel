# Verificación — Cuentas, matrícula y contraseñas

## Controles ejecutados

- Sintaxis del JavaScript de index.php y assets/accessibility.js mediante node --check: correcta.
- Pruebas de las funciones reales de interfaz en Node, con DOM y API simulados: campos específicos por rol, ocultamiento de vínculo estudiantil para docentes/administradores, correo como identificador, captura de contraseña asignada y confirmación, envío de matrícula por clase, restablecimiento administrativo, cambio personal con contraseña actual y retorno al ingreso tras el cambio. Resultado correcto.
- Pruebas de interfaz de estudiante con DOM simulado: navegación limitada, clases visibles, confirmación al marcar y retiro del botón cuando existe marcación. Resultado correcto.
- Pruebas de accesibilidad con DOM y síntesis de voz simulados: activación voluntaria, hover y foco de teclado, exclusión de valores privados, cancelación de paso breve, Escape, retorno de foco, preferencias, restablecimiento y ausencia de soporte de voz. Resultado correcto.
- Revisión estática del esquema: nueve tablas, diccionario de todas las tablas, FK, matrícula con PK compuesta, unicidad de asistencia por sesión, ausencia de DROP/TRUNCATE y compatibilidad del separador de sentencias utilizado por el instalador/actualizador.
- Revisión estática de la API: lectura docente por cuenta asignada, matrícula explícita para autoregistro, contraseña actual verificada antes de cambiarla, confirmación y longitud en servidor, contraseñas con hash, revocación de sesiones y restablecimiento ajeno reservado a administrador.
- Integridad del ZIP y ausencia de config.php e installed.lock privados: comprobadas.

## Pruebas pendientes

Este entorno no dispone de PHP, MySQL/MariaDB ni navegador gráfico instalado. No se ejecutaron php -l, instalación, migración o solicitudes contra la base real. Las pruebas simuladas no sustituyen la comprobación completa en XAMPP ni verifican visualmente todos los tamaños de pantalla.

Antes de reanudar el uso institucional, respaldar nobel_asistencia_v2 y seguir los casos de prueba de GUIA_BASE_DATOS.html: migración repetible, cuenta de cada rol, clase asignada, matrícula, registro propio, rechazo por falta de matrícula/horario, duplicado y concurrencia, contraseña actual incorrecta, confirmaciones distintas, cambio válido, invalidación de sesiones y restablecimiento solo como administrador.

No se migran datos de la base nobel_asistencia que mostró el error #1932 ni se intenta reparar tablas dañadas. Se actualiza la conexión ya configurada en private/config.php, que debe apuntar a la base sana.

## Código automático y semestre

Pasaron node --check y las pruebas de interfaz con DOM/API simulados: campo de código sin entrada manual, vista previa por programa y semestre, matrícula automática activada/opcional y aviso cuando no hay clases. Se revisó estáticamente la generación en servidor con random_bytes, el límite de longitud de students.id, la clave primaria, el reintento ante colisión y la transacción conjunta con matrículas. No se ejecutaron PHP/MySQL ni pruebas reales de concurrencia en este entorno.

## Autoinscripción y creación docente

Pasaron node --check, las pruebas de interfaz anteriores en DOM/API simulados y la revisión estática de permisos: docente con own_classes, propiedad de edición y carrera asignada; inscripción desde el alumno de la sesión, carrera/semestre, docente activo, bloqueo de duplicados y matrícula administrativa no seleccionada por defecto. Pendiente la prueba real con PHP/MySQL y navegador en XAMPP. No se afirma integración completa.

Edición de estudiante: pasó la sintaxis JavaScript y la prueba del formulario con DOM/API simulados (código de solo lectura, campos editables y retiro explícito de matrículas). Revisión estática de permisos, unicidad de DNI y conservación de código/historial. Pendiente prueba real con PHP/MySQL.

## Numeración correlativa

Ejecutado: node --check del JavaScript; prueba del selector con DOM simulado (etiqueta sin código, nombre/carrera/semestre/DNI y visibilidad según rol); revisión estática del contador con FOR UPDATE, transacción conjunta, código de longitud mínima tres, colisiones existentes y clave primaria. Los scripts de pruebas temporales de entregas anteriores no estaban disponibles en este entorno y no se afirman reejecutados. PHP/MySQL y concurrencia real siguen pendientes en XAMPP.
