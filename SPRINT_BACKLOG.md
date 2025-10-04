# Sprint Backlog - Sistema de Votación

Este documento detalla el backlog del sprint para el desarrollo y la mejora del sistema de votación.

---

## Épica 1: Gestión de Autenticación y Usuarios

**Objetivo:** Asegurar que los diferentes tipos de usuarios (Administradores, Docentes, Estudiantes) puedan iniciar y cerrar sesión de forma segura para acceder a sus respectivas funcionalidades.

### Historias de Usuario

**HU 1.1: Como Administrador, quiero iniciar sesión de forma segura para acceder al panel de administración.**
*   **Tarea 1.1.1:** Diseñar e implementar el formulario de login para administradores.
*   **Tarea 1.1.2:** Implementar la validación de credenciales en el lado del servidor.
*   **Tarea 1.1.3:** Crear y gestionar la sesión del administrador tras un inicio de sesión exitoso.
*   **Tarea 1.1.4:** Redirigir al panel de administración después del login.

**HU 1.2: Como Docente, quiero iniciar sesión de forma segura para acceder al panel de docentes.**
*   **Tarea 1.2.1:** Diseñar e implementar el formulario de login para docentes.
*   **Tarea 1.2.2:** Implementar la validación de credenciales y la gestión de sesión para docentes.
*   **Tarea 1.2.3:** Redirigir al panel de docentes después del login.

**HU 1.3: Como Estudiante, quiero iniciar sesión de forma segura para poder votar.**
*   **Tarea 1.3.1:** Diseñar e implementar el formulario de login para estudiantes.
*   **Tarea 1.3.2:** Implementar la validación de credenciales y la gestión de sesión para estudiantes.
*   **Tarea 1.3.3:** Redirigir a la página de votación después del login.

**HU 1.4: Como usuario autenticado, quiero poder cerrar sesión.**
*   **Tarea 1.4.1:** Implementar un botón o enlace de "Cerrar Sesión".
*   **Tarea 1.4.2:** Crear un script que destruya la sesión actual del usuario.
*   **Tarea 1.4.3:** Redirigir a la página de inicio o de login después de cerrar sesión.

---

## Épica 2: Panel de Administración

**Objetivo:** Proporcionar a los administradores las herramientas necesarias para gestionar los datos maestros del sistema, como usuarios y candidatos.

### Historias de Usuario

**HU 2.1: Como Administrador, quiero gestionar la lista de estudiantes.**
*   **Tarea 2.1.1:** Crear una interfaz para listar, agregar, editar y eliminar estudiantes.
*   **Tarea 2.1.2:** Implementar la lógica del backend (CRUD) para la gestión de estudiantes.

**HU 2.2: Como Administrador, quiero gestionar la lista de docentes.**
*   **Tarea 2.2.1:** Crear una interfaz para listar, agregar, editar y eliminar docentes.
*   **Tarea 2.2.2:** Implementar la lógica del backend (CRUD) para la gestión de docentes.

**HU 2.3: Como Administrador, quiero gestionar los candidatos de las elecciones.**
*   **Tarea 2.3.1:** Crear una interfaz para gestionar los candidatos (estudiantes y representantes de docentes).
*   **Tarea 2.3.2:** Permitir la carga de fotos para los candidatos.
*   **Tarea 2.3.3:** Implementar la lógica del backend (CRUD) para la gestión de candidatos.

**HU 2.4: Como Administrador, quiero poder reiniciar el conteo de votos.**
*   **Tarea 2.4.1:** Añadir una función en el panel de admin para borrar todos los votos registrados.
*   **Tarea 2.4.2:** Implementar un modal de confirmación para evitar el borrado accidental.

---

## Épica 3: Proceso de Votación

**Objetivo:** Permitir que los estudiantes y docentes autenticados emitan su voto de manera fácil e intuitiva.

### Historias de Usuario

**HU 3.1: Como Estudiante, quiero ver la lista de candidatos para poder emitir mi voto.**
*   **Tarea 3.1.1:** Diseñar la página de votación mostrando los candidatos con su foto y nombre.
*   **Tarea 3.1.2:** Incluir una opción clara para el "Voto en Blanco".
*   **Tarea 3.1.3:** Asegurar que la interfaz sea clara y fácil de usar.

**HU 3.2: Como Estudiante, quiero que mi voto se registre de forma segura y única.**
*   **Tarea 3.2.1:** Implementar la lógica para procesar y almacenar el voto en la base de datos.
*   **Tarea 3.2.2:** Validar que un estudiante solo pueda votar una vez.
*   **Tarea 3.2.3:** Mostrar un mensaje de confirmación después de que el voto ha sido emitido.

**HU 3.3: Como Docente, quiero votar por el representante de los docentes.**
*   **Tarea 3.3.1:** Crear una página de votación específica para los docentes.
*   **Tarea 3.3.2:** Implementar la lógica para registrar el voto del docente y asegurar que sea único.

---

## Épica 4: Resultados y Estadísticas

**Objetivo:** Mostrar los resultados de la votación de forma clara y en tiempo real para los administradores y de forma pública una vez finalizado el proceso.

### Historias de Usuario

**HU 4.1: Como Administrador, quiero ver los resultados de la votación en tiempo real.**
*   **Tarea 4.1.1:** Crear una sección en el panel de administración para visualizar los resultados.
*   **Tarea 4.1.2:** Implementar gráficos (ej. barras) para mostrar el conteo de votos por candidato.
*   **Tarea 4.1.3:** Utilizar AJAX para que los resultados se actualicen automáticamente sin recargar la página.
*   **Tarea 4.1.4:** Mostrar estadísticas separadas para votos de estudiantes y de docentes.

**HU 4.2: Como usuario, quiero poder ver los resultados finales una vez que la votación haya cerrado.**
*   **Tarea 4.2.1:** Crear una página pública de resultados.
*   **Tarea 4.2.2:** Implementar una función para que el administrador "cierre" la votación y haga públicos los resultados.
*   **Tarea 4.2.3:** Mostrar claramente al ganador o ganadores de la elección.

---

## Épica 5: Deuda Técnica y Refactorización

**Objetivo:** Mejorar la calidad, seguridad y mantenibilidad del código base del proyecto.

### Historias de Usuario

**HU 5.1: Como desarrollador, quiero refactorizar el código para seguir el patrón MVC de forma más estricta.**
*   **Tarea 5.1.1:** Mover toda la lógica de negocio y acceso a datos a los Modelos.
*   **Tarea 5.1.2:** Asegurar que los Controladores solo se encarguen de la lógica de la aplicación y la comunicación entre Vistas y Modelos.
*   **Tarea 5.1.3:** Limpiar las Vistas de código PHP complejo, utilizando plantillas si es posible.

**HU 5.2: Como desarrollador, quiero asegurar la aplicación contra vulnerabilidades comunes.**
*   **Tarea 5.2.1:** Auditar y refactorizar todas las consultas a la base de datos para usar sentencias preparadas y prevenir inyección SQL.
*   **Tarea 5.2.2:** Sanitizar todas las entradas del usuario para prevenir ataques XSS.
*   **Tarea 5.2.3:** Implementar tokens CSRF en todos los formularios para prevenir ataques de falsificación de solicitudes.

**HU 5.3: Como desarrollador, quiero mejorar la gestión del esquema de la base de datos.**
*   **Tarea 5.3.1:** Consolidar todos los scripts SQL (`.sql`) en un sistema de migraciones o en un único archivo de inicialización.
*   **Tarea 5.3.2:** Documentar el esquema de la base de datos.

---

## Épica 6: Gestión de Personal Administrativo

**Objetivo:** Permitir a los administradores gestionar el personal administrativo de la institución con funcionalidades completas de CRUD, búsqueda y paginación.

### Historias de Usuario

**HU 6.1: Como Administrador, quiero gestionar la lista de personal administrativo.**
*   **Tarea 6.1.1:** Crear una interfaz para listar administrativos con paginación y búsqueda.
*   **Tarea 6.1.2:** Implementar funcionalidad para agregar nuevos administrativos.
*   **Tarea 6.1.3:** Desarrollar la capacidad de editar información de administrativos existentes.
*   **Tarea 6.1.4:** Implementar eliminación lógica (soft delete) de administrativos.

**HU 6.2: Como Administrador, quiero buscar y filtrar administrativos eficientemente.**
*   **Tarea 6.2.1:** Implementar búsqueda por cédula, nombre, apellido, correo y cargo.
*   **Tarea 6.2.2:** Agregar filtros por estado (activo/inactivo).
*   **Tarea 6.2.3:** Implementar paginación configurable (10-100 registros por página).

**HU 6.3: Como Administrador, quiero validar la información de administrativos.**
*   **Tarea 6.3.1:** Validar que la cédula sea única en el sistema.
*   **Tarea 6.3.2:** Implementar validación de campos obligatorios (cédula, nombre, apellido).
*   **Tarea 6.3.3:** Validar formato de correo electrónico.

---

## Épica 7: Sistema de Notificaciones por Correo

**Objetivo:** Implementar un sistema de notificaciones automáticas por correo electrónico para confirmar acciones importantes del sistema.

### Historias de Usuario

**HU 7.1: Como Estudiante, quiero recibir confirmación de mi voto por correo electrónico.**
*   **Tarea 7.1.1:** Configurar PHPMailer para envío de correos SMTP.
*   **Tarea 7.1.2:** Crear plantilla de correo de confirmación de voto.
*   **Tarea 7.1.3:** Incluir resumen del voto (personero y representante seleccionados).
*   **Tarea 7.1.4:** Generar ID de verificación único para cada voto.

**HU 7.2: Como Sistema, quiero manejar errores de envío de correo graciosamente.**
*   **Tarea 7.2.1:** Implementar manejo de excepciones para fallos de SMTP.
*   **Tarea 7.2.2:** Mostrar mensajes informativos al usuario sobre el estado del envío.
*   **Tarea 7.2.3:** Configurar opciones SSL para entornos de desarrollo local.

---

## Épica 8: Gestión Avanzada de Imágenes de Candidatos

**Objetivo:** Proporcionar un sistema robusto para la gestión de imágenes de candidatos con fallbacks y optimizaciones.

### Historias de Usuario

**HU 8.1: Como Sistema, quiero manejar imágenes de candidatos de forma eficiente.**
*   **Tarea 8.1.1:** Implementar helper para obtener imágenes con fallback a imagen predeterminada.
*   **Tarea 8.1.2:** Agregar cache-busting automático para evitar problemas de caché.
*   **Tarea 8.1.3:** Crear imagen SVG predeterminada para candidatos sin foto.

**HU 8.2: Como Administrador, quiero subir imágenes de perfil para administradores.**
*   **Tarea 8.2.1:** Implementar controlador para subida de imágenes de perfil.
*   **Tarea 8.2.2:** Validar tipos de archivo permitidos (JPG, PNG, GIF).
*   **Tarea 8.2.3:** Limitar tamaño máximo de archivo (2MB).
*   **Tarea 8.2.4:** Generar nombres únicos para evitar conflictos.

**HU 8.3: Como Sistema, quiero verificar la existencia de archivos de imagen.**
*   **Tarea 8.3.1:** Implementar verificación de existencia de archivos físicos.
*   **Tarea 8.3.2:** Proporcionar método para detectar si un candidato tiene foto personalizada.
*   **Tarea 8.3.3:** Generar HTML optimizado para mostrar imágenes con atributos apropiados.

---

## Épica 9: Sistema de Mesas Virtuales

**Objetivo:** Implementar un sistema completo de mesas virtuales para organizar la votación por grados y grupos, con asignación automática y estadísticas.

### Historias de Usuario

**HU 9.1: Como Administrador, quiero crear y gestionar mesas virtuales.**
*   **Tarea 9.1.1:** Crear estructura de base de datos para mesas virtuales.
*   **Tarea 9.1.2:** Implementar asignación de mesas por grado y grupo.
*   **Tarea 9.1.3:** Configurar capacidad máxima por mesa.
*   **Tarea 9.1.4:** Implementar estados de mesa (activa, inactiva, cerrada).

**HU 9.2: Como Sistema, quiero asignar estudiantes automáticamente a mesas.**
*   **Tarea 9.2.1:** Desarrollar procedimiento almacenado para asignación automática.
*   **Tarea 9.2.2:** Implementar lógica de distribución equitativa por capacidad.
*   **Tarea 9.2.3:** Evitar duplicados con restricciones de base de datos.

**HU 9.3: Como Administrador, quiero supervisar mesas virtuales.**
*   **Tarea 9.3.1:** Crear tabla de supervisores de mesa con roles.
*   **Tarea 9.3.2:** Implementar asignación de personal responsable por mesa.
*   **Tarea 9.3.3:** Definir roles (presidente, secretario, vocal).

**HU 9.4: Como Administrador, quiero ver estadísticas de participación por mesa.**
*   **Tarea 9.4.1:** Crear tabla de estadísticas automáticas por mesa.
*   **Tarea 9.4.2:** Calcular porcentajes de participación en tiempo real.
*   **Tarea 9.4.3:** Generar reportes por mesa, grado y general.
*   **Tarea 9.4.4:** Implementar procedimiento para actualización automática de estadísticas.

---

## Épica 10: Votación de Docentes y Representantes

**Objetivo:** Extender el sistema de votación para incluir la participación de docentes en la elección de representantes docentes.

### Historias de Usuario

**HU 10.1: Como Docente, quiero votar por el representante de los docentes.**
*   **Tarea 10.1.1:** Crear tabla específica para votos de docentes.
*   **Tarea 10.1.2:** Implementar interfaz de votación para docentes.
*   **Tarea 10.1.3:** Asegurar que cada docente solo pueda votar una vez.
*   **Tarea 10.1.4:** Incluir opción de voto en blanco para docentes.

**HU 10.2: Como Sistema, quiero integrar estadísticas de votación docente.**
*   **Tarea 10.2.1:** Modificar API de estadísticas para incluir datos de docentes.
*   **Tarea 10.2.2:** Mostrar estadísticas separadas para votos de estudiantes y docentes.
*   **Tarea 10.2.3:** Implementar conteo de votos recientes de docentes.

---

## Épica 11: API y Servicios Web

**Objetivo:** Proporcionar endpoints API para acceso a datos estadísticos y funcionalidades del sistema de forma programática.

### Historias de Usuario

**HU 11.1: Como Sistema, quiero exponer estadísticas a través de API REST.**
*   **Tarea 11.1.1:** Crear endpoint para estadísticas generales del sistema.
*   **Tarea 11.1.2:** Implementar endpoint específico para estadísticas de docentes.
*   **Tarea 11.1.3:** Agregar endpoint de validación de números de tarjetón.
*   **Tarea 11.1.4:** Crear endpoint de ping para verificación de estado del sistema.

**HU 11.2: Como Administrador, quiero acceder a datos en tiempo real.**
*   **Tarea 11.2.1:** Implementar cache-control para datos dinámicos.
*   **Tarea 11.2.2:** Agregar timestamps para sincronización de datos.
*   **Tarea 11.2.3:** Incluir votos recientes tanto de estudiantes como docentes.

**HU 11.3: Como Sistema, quiero asegurar el acceso a las APIs.**
*   **Tarea 11.3.1:** Implementar verificación de autenticación en endpoints sensibles.
*   **Tarea 11.3.2:** Retornar códigos de estado HTTP apropiados.
*   **Tarea 11.3.3:** Implementar logging de errores para debugging.

---

## Épica 12: Mejoras de Experiencia de Usuario

**Objetivo:** Mejorar la experiencia del usuario con funcionalidades adicionales y optimizaciones de interfaz.

### Historias de Usuario

**HU 12.1: Como Usuario, quiero una interfaz más intuitiva y responsive.**
*   **Tarea 12.1.1:** Optimizar interfaces para dispositivos móviles.
*   **Tarea 12.1.2:** Implementar feedback visual para acciones del usuario.
*   **Tarea 12.1.3:** Mejorar mensajes de error y confirmación.

**HU 12.2: Como Administrador, quiero herramientas de gestión más eficientes.**
*   **Tarea 12.2.1:** Implementar acciones en lote para gestión de usuarios.
*   **Tarea 12.2.2:** Agregar exportación de datos en formatos estándar (CSV, Excel).
*   **Tarea 12.2.3:** Crear dashboard con métricas clave del sistema.

**HU 12.3: Como Sistema, quiero optimizar el rendimiento.**
*   **Tarea 12.3.1:** Implementar índices de base de datos para consultas frecuentes.
*   **Tarea 12.3.2:** Optimizar consultas SQL complejas.
*   **Tarea 12.3.3:** Implementar caching para datos estáticos.