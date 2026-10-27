# Accesibilidad — SCAE Alfred Nobel

## Instalar en tu XAMPP

1. Extraer el ZIP. Encontrarás la carpeta `nobel-xampp`.
2. Guardar una copia de la carpeta instalada. Si ya tienes registros, exportar además la base desde phpMyAdmin.
3. Copiar el contenido de la nueva carpeta a `C:\xampp\htdocs\nobel-xampp\`, aceptando reemplazar los archivos del sistema. Conservar tus archivos `private\config.php` y `private\installed.lock`: el ZIP no los incluye.
4. Iniciar Apache y MySQL. Abrir `http://localhost/nobel-xampp/` y pulsar Ctrl+F5.
5. El botón «Accesibilidad» aparece abajo a la derecha tanto en el ingreso como dentro del sistema. No requiere crear tablas adicionales.

Si aún usas la versión original de los botones, antes de usar las cuentas de estudiante abrir `http://localhost/nobel-xampp/actualizar.php` en el servidor. Esa actualización pertenece a la ampliación de asistencia manual incluida en este paquete. Si ya la realizaste, la accesibilidad no necesita otra actualización de base de datos. Para una instalación nueva, seguir GUIA_BASE_DATOS.html y ejecutar install.php.

## Demostración al docente

1. Abrir Accesibilidad → Activar lectura por voz. Se escucha una confirmación.
2. Colocar el cursor sobre «Iniciar sesión» o un campo. Esperar aproximadamente 0,65 segundos. Se anuncia el nombre del control.
3. Usar Tab y Mayús+Tab para recorrer los controles. La lectura también responde al foco de teclado.
4. Elegir texto Grande o Muy grande. Los controles y textos principales aumentan de tamaño.
5. Activar Alto contraste: fondo negro, texto blanco e indicadores amarillos en los elementos seleccionados y el foco.
6. Usar «Detener lectura» para interrumpir la voz, o Escape. Escape también cierra el panel y devuelve el foco al botón Accesibilidad.
7. «Restablecer opciones» vuelve al tamaño normal, contraste original y voz apagada.

El texto y el contraste se recuerdan en ese navegador. La voz debe activarse de nuevo al cargar otra página, para no empezar a hablar sin una acción del usuario. «Detener lectura» interrumpe el mensaje actual; para silenciar todos los mensajes posteriores utilizar «Desactivar lectura por voz».

## Privacidad y compatibilidad

Se leen etiquetas y funciones de los controles, nunca el valor escrito de los formularios. No se leen las tarjetas que muestran credenciales. No se usa micrófono, reconocimiento de voz ni una API de voz externa desde el código de la aplicación. La voz disponible depende de las voces instaladas/configuradas en el navegador y en el sistema operativo; algunas voces del proveedor pueden necesitar conexión. Se prefiere voz es-PE y, si no está disponible, otra voz en español.

Si no hay API de síntesis de voz, se muestra un aviso y permanecen disponibles texto y contraste. Si no se escucha audio, comprobar volumen, salida de sonido y voces españolas del sistema, y activar la opción desde un clic. Probar preferiblemente en el navegador actualizado utilizado en el instituto. Esta función complementa la navegación con teclado y los lectores de pantalla; no constituye una certificación de conformidad WCAG.

## Archivos agregados

- `assets/accessibility.js`: panel, preferencias, lectura de etiquetas, teclado y control de voz.
- `assets/accessibility.css`: panel, tamaños de texto, contraste, foco y reducción de movimiento.
- `index.php`, `install.php` y `actualizar.php`: referencias a los dos archivos anteriores.

Para agregar una explicación específica a un botón, definir `data-voice="Texto que se debe anunciar"`. Para excluir un control de la lectura automática usar `data-voice-skip`. Los controles sin explicación especial utilizan su etiqueta accesible o texto visible. No colocar contraseñas, códigos ni información privada en estos atributos.

## Pruebas realizadas y pendientes

Pasaron la comprobación de sintaxis JavaScript y las pruebas en Node con DOM y síntesis de voz simulados: activación voluntaria, cursor y foco, exclusión de valores privados, cancelación cuando el cursor se retira antes de la pausa, Escape, retorno de foco, preferencias, restablecer y ausencia de síntesis de voz. También se repitieron los controles simulados de la interfaz de asistencia.

No se dispone aquí de navegador con motor gráfico, PHP ni MySQL. Queda pendiente verificar en tu XAMPP la apariencia en pantalla y móvil, el audio real, las voces españolas, la navegación con teclado y el uso con el lector de pantalla que utilice el estudiante. No se afirma una prueba de integración completa ni conformidad de accesibilidad del sistema entero.
