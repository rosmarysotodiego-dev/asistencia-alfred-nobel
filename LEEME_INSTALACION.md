# Código correlativo y selector sin código

Esta entrega incluye todas las mejoras anteriores. Ahora los nuevos estudiantes reciben 001, 002, 003… y después de 999 sigue 1000. No se renumeran registros anteriores. El selector al crear cuentas muestra nombre, carrera, semestre y DNI si existe; el código sigue siendo el vínculo interno y permanece visible en el padrón.

## Instalar la actualización

1. Exportar nobel_asistencia_v2 desde phpMyAdmin y respaldar la carpeta actual.
2. Extraer el ZIP y copiar el contenido de nobel-xampp sobre C:\xampp2\htdocs\nobel-xampp\ conservando private/config.php y private/installed.lock.
3. Abrir http://localhost/nobel-xampp/actualizar.php en el propio servidor y pulsar Actualizar base de datos. ESTA ENTREGA SÍ requiere ejecutar el actualizador porque agrega student_sequences.
4. Abrir http://localhost/nobel-xampp/ y pulsar Ctrl+F5.
5. Crear dos estudiantes y verificar la secuencia; revisar el selector en Usuarios y permisos.

No basta con copiar index.php y api.php: también cambia private/bootstrap.php y database/schema.sql. Copiar el contenido del paquete completo, no eliminar la carpeta existente. El ZIP no incluye los dos archivos privados que debes conservar.

Los códigos anteriores se conservan para mantener cuentas, matrículas e historial. Si un número ya existe, se omite para evitar duplicados. Eliminar un estudiante no retrocede el contador. No requiere cambiar el nombre de la base ni reinstalar.

Detalles: GUIA_BASE_DATOS.html, secciones 10 y 13. Para instalación nueva: install.php.
