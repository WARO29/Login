
# ANÁLISIS DE SISTEMA - DIAGRAMAS UML
## Sistema de Votación Electrónica Escolar

---

## ÍNDICE
1. [Análisis de Actores](#análisis-de-actores)
2. [Casos de Uso por Épicas](#casos-de-uso-por-épicas)
3. [Diagramas de Casos de Uso](#diagramas-de-casos-de-uso)
4. [Historias de Usuario](#historias-de-usuario)
5. [Diagrama de Clases](#diagrama-de-clases)
6. [Diagrama de Base de Datos](#diagrama-de-base-de-datos)

---

## ANÁLISIS DE ACTORES

### Actores Principales Identificados:

1. **Estudiante** - Usuario votante principal
2. **Docente** - Usuario votante para representante docente
3. **Administrativo** - Personal administrativo que vota por representante docente
4. **Administrador** - Gestor del sistema electoral
5. **Sistema** - Actor del sistema para procesos automáticos

### Características de los Actores:

| Actor | Descripción | Permisos | Restricciones |
|-------|-------------|----------|---------------|
| **Estudiante** | Estudiante de grados 6-11 | Votar por personero y representante de grado | Solo puede votar una vez, debe estar activo |
| **Docente** | Personal docente activo | Votar por representante docente | Solo puede votar una vez, debe estar activo |
| **Administrativo** | Personal administrativo | Votar por representante docente | Solo puede votar una vez, debe estar activo |
| **Administrador** | Administrador del sistema | Gestión completa del sistema | Acceso total a configuración y datos |
| **Sistema** | Procesos automáticos | Activar/cerrar elecciones, generar reportes | Basado en configuración temporal |

---

## CASOS DE USO POR ÉPICAS

### ÉPICA 1: GESTIÓN DE AUTENTICACIÓN Y ACCESO

#### Historia de Usuario:
*Como usuario del sistema, quiero poder autenticarme de forma segura para acceder a las funcionalidades correspondientes a mi rol.*

#### Casos de Uso:
- **CU-001**: Autenticar Estudiante
- **CU-002**: Autenticar Docente/Administrativo
- **CU-003**: Autenticar Administrador
- **CU-004**: Cerrar Sesión
- **CU-005**: Verificar Estado de Elección

### ÉPICA 2: GESTIÓN DE VOTACIÓN

#### Historia de Usuario:
*Como votante, quiero poder ejercer mi voto de forma intuitiva y segura durante el período electoral.*

#### Casos de Uso:
- **CU-006**: Votar por Personero (Estudiantes)
- **CU-007**: Votar por Representante de Grado (Estudiantes)
- **CU-008**: Votar por Representante Docente (Docentes/Administrativos)
- **CU-009**: Votar en Blanco
- **CU-010**: Finalizar Votación
- **CU-011**: Cancelar Votación

### ÉPICA 3: ADMINISTRACIÓN DE ELECCIONES

#### Historia de Usuario:
*Como administrador, quiero gestionar completamente el proceso electoral desde la configuración hasta los resultados.*

#### Casos de Uso:
- **CU-012**: Crear Nueva Elección
- **CU-013**: Configurar Elección
- **CU-014**: Activar Elección
- **CU-015**: Cerrar Elección
- **CU-016**: Cancelar Elección
- **CU-017**: Consultar Estado de Elecciones

### ÉPICA 4: GESTIÓN DE CANDIDATOS

#### Historia de Usuario:
*Como administrador, quiero gestionar los candidatos para garantizar que la información esté completa y actualizada.*

#### Casos de Uso:
- **CU-018**: Registrar Candidato
- **CU-019**: Editar Candidato
- **CU-020**: Eliminar Candidato
- **CU-021**: Consultar Candidatos
- **CU-022**: Subir Foto de Candidato

### ÉPICA 5: GESTIÓN DE USUARIOS

#### Historia de Usuario:
*Como administrador, quiero gestionar los usuarios del sistema para mantener la base de datos actualizada.*

#### Casos de Uso:
- **CU-023**: Gestionar Estudiantes
- **CU-024**: Gestionar Docentes
- **CU-025**: Gestionar Administrativos
- **CU-026**: Consultar Usuarios

### ÉPICA 6: REPORTES Y ESTADÍSTICAS

#### Historia de Usuario:
*Como administrador, quiero acceder a reportes y estadísticas para monitorear el proceso electoral.*

#### Casos de Uso:
- **CU-027**: Generar Estadísticas en Tiempo Real
- **CU-028**: Consultar Resultados
- **CU-029**: Generar Reportes
- **CU-030**: Consultar Logs de Acceso

---

## DIAGRAMAS DE CASOS DE USO

### Diagrama General del Sistema

```mermaid
graph TB
    %% Actores
    EST[👤 Estudiante]
    DOC[👨‍🏫 Docente]
    ADM_USER[👨‍💼 Administrativo]
    ADMIN[👨‍💻 Administrador]
    SYS[🤖 Sistema]

    %% Casos de Uso - Autenticación
    subgraph "AUTENTICACIÓN Y ACCESO"
        CU001[CU-001: Autenticar Estudiante]
        CU002[CU-002: Autenticar Docente/Administrativo]
        CU003[CU-003: Autenticar Administrador]
        CU004[CU-004: Cerrar Sesión]
        CU005[CU-005: Verificar Estado Elección]
    end

    %% Casos de Uso - Votación
    subgraph "GESTIÓN DE VOTACIÓN"
        CU006[CU-006: Votar por Personero]
        CU007[CU-007: Votar por Representante Grado]
        CU008[CU-008: Votar por Representante Docente]
        CU009[CU-009: Votar en Blanco]
        CU010[CU-010: Finalizar Votación]
        CU011[CU-011: Cancelar Votación]
    end

    %% Casos de Uso - Administración
    subgraph "ADMINISTRACIÓN DE ELECCIONES"
        CU012[CU-012: Crear Nueva Elección]
        CU013[CU-013: Configurar Elección]
        CU014[CU-014: Activar Elección]
        CU015[CU-015: Cerrar Elección]
        CU016[CU-016: Cancelar Elección]
        CU017[CU-017: Consultar Estado Elecciones]
    end

    %% Casos de Uso - Candidatos
    subgraph "GESTIÓN DE CANDIDATOS"
        CU018[CU-018: Registrar Candidato]
        CU019[CU-019: Editar Candidato]
        CU020[CU-020: Eliminar Candidato]
        CU021[CU-021: Consultar Candidatos]
        CU022[CU-022: Subir Foto Candidato]
    end

    %% Casos de Uso - Usuarios
    subgraph "GESTIÓN DE USUARIOS"
        CU023[CU-023: Gestionar Estudiantes]
        CU024[CU-024: Gestionar Docentes]
        CU025[CU-025: Gestionar Administrativos]
        CU026[CU-026: Consultar Usuarios]
    end

    %% Casos de Uso - Reportes
    subgraph "REPORTES Y ESTADÍSTICAS"
        CU027[CU-027: Generar Estadísticas]
        CU028[CU-028: Consultar Resultados]
        CU029[CU-029: Generar Reportes]
        CU030[CU-030: Consultar Logs]
    end

    %% Relaciones Estudiante
    EST --> CU001
    EST --> CU006
    EST --> CU007
    EST --> CU009
    EST --> CU010
    EST --> CU011
    EST --> CU004

    %% Relaciones Docente/Administrativo
    DOC --> CU002
    DOC --> CU008
    DOC --> CU009
    DOC --> CU010
    DOC --> CU004
    ADM_USER --> CU002
    ADM_USER --> CU008
    ADM_USER --> CU009
    ADM_USER --> CU010
    ADM_USER --> CU004

    %% Relaciones Administrador
    ADMIN --> CU003
    ADMIN --> CU012
    ADMIN --> CU013
    ADMIN --> CU014
    ADMIN --> CU015
    ADMIN --> CU016
    ADMIN --> CU017
    ADMIN --> CU018
    ADMIN --> CU019
    ADMIN --> CU020
    ADMIN --> CU021
    ADMIN --> CU022
    ADMIN --> CU023
    ADMIN --> CU024
    ADMIN --> CU025
    ADMIN --> CU026
    ADMIN --> CU027
    ADMIN --> CU028
    ADMIN --> CU029
    ADMIN --> CU030
    ADMIN --> CU004

    %% Relaciones Sistema
    SYS --> CU005
    SYS --> CU014
    SYS --> CU015
    SYS --> CU027

    %% Includes y Extends
    CU006 -.->|includes| CU005
    CU007 -.->|includes| CU005
    CU008 -.->|includes| CU005
    CU010 -.->|includes| CU009
```

### Diagrama Detallado - Épica de Votación

```mermaid
graph TB
    %% Actores
    EST[👤 Estudiante]
    DOC[👨‍🏫 Docente]
    ADM_USER[👨‍💼 Administrativo]

    %% Sistema
    subgraph "SISTEMA DE VOTACIÓN"
        %% Casos de Uso Principales
        VERIFICAR[Verificar Elegibilidad]
        MOSTRAR_CANDIDATOS[Mostrar Candidatos]
        REGISTRAR_VOTO[Registrar Voto]
        CONFIRMAR[Confirmar Votación]
        
        %% Casos de Uso Específicos
        VOTAR_PERSONERO[Votar por Personero]
        VOTAR_REPR_GRADO[Votar por Representante de Grado]
        VOTAR_REPR_DOCENTE[Votar por Representante Docente]
        VOTO_BLANCO[Votar en Blanco]
        
        %% Validaciones
        VALIDAR_HORARIO[Validar Horario Electoral]
        VALIDAR_USUARIO[Validar Usuario Activo]
        VERIFICAR_YA_VOTO[Verificar si Ya Votó]
    end

    %% Flujo Estudiantes
    EST --> VERIFICAR
    VERIFICAR --> VALIDAR_HORARIO
    VALIDAR_HORARIO --> VALIDAR_USUARIO
    VALIDAR_USUARIO --> VERIFICAR_YA_VOTO
    VERIFICAR_YA_VOTO --> MOSTRAR_CANDIDATOS
    MOSTRAR_CANDIDATOS --> VOTAR_PERSONERO
    MOSTRAR_CANDIDATOS --> VOTAR_REPR_GRADO
    MOSTRAR_CANDIDATOS --> VOTO_BLANCO
    VOTAR_PERSONERO --> REGISTRAR_VOTO
    VOTAR_REPR_GRADO --> REGISTRAR_VOTO
    VOTO_BLANCO --> REGISTRAR_VOTO
    REGISTRAR_VOTO --> CONFIRMAR

    %% Flujo Docentes/Administrativos
    DOC --> VERIFICAR
    ADM_USER --> VERIFICAR
    MOSTRAR_CANDIDATOS --> VOTAR_REPR_DOCENTE
    VOTAR_REPR_DOCENTE --> REGISTRAR_VOTO

    %% Estilos
    classDef actor fill:#e1f5fe
    classDef usecase fill:#f3e5f5
    classDef validation fill:#fff3e0
    
    class EST,DOC,ADM_USER actor
    class VOTAR_PERSONERO,VOTAR_REPR_GRADO,VOTAR_REPR_DOCENTE,VOTO_BLANCO usecase
    class VALIDAR_HORARIO,VALIDAR_USUARIO,VERIFICAR_YA_VOTO validation
```

---

## HISTORIAS DE USUARIO DETALLADAS

### ÉPICA 1: AUTENTICACIÓN Y ACCESO

#### HU-001: Autenticación de Estudiante
**Como** estudiante del colegio  
**Quiero** poder ingresar con mi número de documento  
**Para** acceder al sistema de votación y ejercer mi derecho al voto  

**Criterios de Aceptación:**
- El sistema debe validar que el documento sea numérico
- El estudiante debe estar registrado y activo en el sistema
- El sistema debe verificar que hay elecciones activas
- El sistema debe verificar que el estudiante no haya votado previamente
- Debe mostrar mensajes de error claros en caso de fallo

#### HU-002: Verificación de Estado Electoral
**Como** usuario del sistema  
**Quiero** que se verifique automáticamente el estado de las elecciones  
**Para** asegurarme de que solo puedo votar durante el período electoral activo  

**Criterios de Aceptación:**
- El sistema debe verificar fechas y horarios de votación
- Debe mostrar información clara sobre el estado actual
- Debe bloquear el acceso fuera del horario electoral
- Debe registrar intentos de acceso para auditoría

### ÉPICA 2: GESTIÓN DE VOTACIÓN

#### HU-003: Votación por Personero
**Como** estudiante  
**Quiero** poder votar por el candidato a personero de mi preferencia  
**Para** elegir quien me represente en el gobierno estudiantil  

**Criterios de Aceptación:**
- Debe mostrar todos los candidatos a personero con sus fotos y propuestas
- Debe permitir seleccionar solo un candidato
- Debe incluir opción de voto en blanco
- Debe confirmar la selección antes de registrar el voto
- El voto debe quedar registrado de forma anónima

#### HU-004: Votación por Representante de Grado
**Como** estudiante  
**Quiero** poder votar por el representante de mi grado  
**Para** elegir quien represente los intereses de mi curso  

**Criterios de Aceptación:**
- Debe mostrar solo candidatos del grado correspondiente al estudiante
- Debe validar que el estudiante pertenece al grado
- Debe permitir voto en blanco
- El sistema debe asociar correctamente el voto con el grado

#### HU-005: Votación Docente/Administrativo
**Como** docente o administrativo  
**Quiero** poder votar por el representante docente  
**Para** elegir quien represente mis intereses en el consejo directivo  

**Criterios de Aceptación:**
- Debe autenticar con cédula o código de empleado
- Debe mostrar todos los candidatos a representante docente
- Debe permitir voto en blanco
- Debe diferenciar entre docentes y administrativos en los registros

### ÉPICA 3: ADMINISTRACIÓN DE ELECCIONES

#### HU-006: Configuración de Nueva Elección
**Como** administrador  
**Quiero** poder crear y configurar una nueva elección  
**Para** establecer los parámetros del proceso electoral  

**Criterios de Aceptación:**
- Debe permitir establecer nombre y descripción de la elección
- Debe configurar fechas y horarios de inicio y cierre
- Debe seleccionar tipos de votación (estudiantes, docentes, administrativos)
- Debe validar que no haya conflictos de horarios
- Debe permitir configuraciones adicionales específicas

#### HU-007: Gestión de Estados de Elección
**Como** administrador  
**Quiero** poder cambiar el estado de las elecciones  
**Para** controlar el flujo del proceso electoral  

**Criterios de Aceptación:**
- Estados disponibles: programada, activa, cerrada, cancelada
- Debe validar transiciones de estado válidas
- Debe registrar cambios para auditoría
- Debe notificar cambios a los usuarios del sistema

### ÉPICA 4: GESTIÓN DE CANDIDATOS

#### HU-008: Registro de Candidatos
**Como** administrador  
**Quiero** poder registrar candidatos para las diferentes posiciones  
**Para** que los votantes tengan opciones disponibles  

**Criterios de Aceptación:**
- Debe permitir registro de personeros y representantes
- Debe validar datos obligatorios (nombre, número de tarjetón)
- Debe permitir subir foto del candidato
- Debe validar que no haya números de tarjetón duplicados
- Para representantes debe especificar el grado

#### HU-009: Gestión de Fotos de Candidatos
**Como** administrador  
**Quiero** poder subir y gestionar fotos de candidatos  
**Para** que los votantes puedan identificar visualmente a los candidatos  

**Criterios de Aceptación:**
- Debe aceptar formatos de imagen estándar (JPG, PNG)
- Debe redimensionar automáticamente las imágenes
- Debe tener imagen por defecto para candidatos sin foto
- Debe permitir actualizar fotos existentes

### ÉPICA 5: REPORTES Y ESTADÍSTICAS

#### HU-010: Dashboard de Estadísticas
**Como** administrador  
**Quiero** ver estadísticas en tiempo real del proceso electoral  
**Para** monitorear el progreso y participación  

**Criterios de Aceptación:**
- Debe mostrar total de votos emitidos
- Debe mostrar porcentaje de participación
- Debe mostrar estadísticas por tipo de usuario
- Debe actualizar automáticamente
- Debe mostrar votos recientes (sin revelar identidad)

#### HU-011: Consulta de Resultados
**Como** administrador  
**Quiero** poder consultar los resultados de las elecciones  
**Para** conocer los ganadores y estadísticas finales  

**Criterios de Aceptación:**
- Solo disponible cuando la elección esté cerrada
- Debe mostrar resultados por categoría (personero, representantes)
- Debe incluir votos en blanco
- Debe mostrar porcentajes y gráficos
- Debe permitir exportar resultados

---

## DIAGRAMA DE CLASES

```mermaid
classDiagram
    %% Clases de Controladores
    class AuthController {
        -EstudianteModel estudianteModel
        +login()
        +logout()
    }

    class AdminController {
        -Admin adminModel
        +login()
        +autenticar()
        +panel()
        +cerrarSesion()
    }

    class DocenteController {
        -DocenteModel docenteModel
        -AdministrativoModel administrativoModel
        +login()
        +autenticar()
        +panel()
        +cerrarSesion()
    }

    class AdminCandidatosController {
        -Candidatos candidatosModel
        +index()
        +agregarCandidato()
        +editarCandidato()
        +eliminarCandidato()
        +validarNumero()
        -procesarFoto()
    }

    class ProcesarVotosController {
        -Votos votosModel
        +procesarVoto()
        +procesarVotoEnBlanco()
        +finalizarVotacion()
        +cancelarVotacion()
    }

    class EleccionConfigController {
        -EleccionConfigModel eleccionModel
        +crearEleccion()
        +editarEleccion()
        +activarEleccion()
        +cerrarEleccion()
        +cancelarEleccion()
    }

    %% Clases de Modelos
    class EstudianteModel {
        -Database conn
        +getAllEstudiantes()
        +autenticarEstudiante(id)
        +haVotado(id)
        +crearEstudiante(datos)
        +actualizarEstudiante(id, datos)
        +eliminarEstudiante(id)
    }

    class DocenteModel {
        -Database conn
        +getDocentePorDocumento(documento)
        +getAllDocentes()
        +crearDocente(datos)
        +actualizarDocente(codigo, datos)
        +eliminarDocente(codigo)
    }

    class AdministrativoModel {
        -Database conn
        +getAdministrativoPorCedula(cedula)
        +getAllAdministrativos()
        +crearAdministrativo(datos)
        +actualizarAdministrativo(codigo, datos)
        +eliminarAdministrativo(codigo)
    }

    class Admin {
        -Database conn
        +authenticate(usuario, password)
        +getAllAdmins()
        +getById(id)
        +updateProfileImage(id, imageUrl)
    }

    class Candidatos {
        -Database conn
        +obtenerCandidatos(offset, limit, busqueda, tipo)
        +obtenerPorId(id)
        +crear(datos)
        +actualizar(id, datos)
        +eliminar(id)
        +obtenerPorTipo(tipo, grado)
        +existeNumero(numero, tipo, grado)
        -validarDatos(datos)
    }

    class Votos {
        -Database conn
        +haVotadoPorTipo(id_estudiante, tipo)
        +registrarVoto(id_estudiante, id_candidato, tipo)
        +registrarVotoEnBlanco(id_estudiante, tipo)
        +finalizarVotacion(id_estudiante)
        +getConteoVotosPorTipo(tipo)
        +haVotadoDocente(id_docente)
        +registrarVotoDocente(id_docente, id_representante, voto_blanco)
        +getEstadisticasVotacionDocentes()
    }

    class EleccionConfigModel {
        -Database conn
        +getConfiguracionActiva()
        +crearConfiguracion(datos)
        +actualizarConfiguracion(id, datos)
        +cambiarEstadoEleccion(id, estado)
        +verificarEleccionesActivas()
        +validarHorarios(fechaInicio, fechaCierre)
        +verificarConflictosHorarios(fechaInicio, fechaCierre)
    }

    class Estadisticas {
        -Database conn
        +getTotalEstudiantes()
        +getTotalVotos()
        +getTotalCandidatos()
        +getPorcentajeParticipacion()
        +getVotosRecientes(limit)
    }

    class RepresentanteDocenteModel {
        -Database conn
        +getAll()
        +getByCodigo(codigo)
    }

    %% Clases de Utilidades
    class SessionManager {
        +iniciarSesion()
        +establecerSesionEstudiante(estudiante)
        +establecerSesionAdmin(admin)
        +establecerSesionDocente(docente)
        +esEstudianteAutenticado()
        +esAdminAutenticado()
        +esDocenteAutenticado()
        +cerrarSesionEstudiante()
        +cerrarSesionAdmin()
        +cerrarSesionDocente()
    }

    class EleccionMiddleware {
        +verificarAccesoVotante(tipoUsuario)
        +verificarEleccionesActivas()
        +verificarHorarioVotacion()
        +registrarIntentoAcceso(datos)
        +registrarAccesoExitoso(datos)
        +puedeVotar(tipoUsuario, idUsuario)
        +yaVoto(tipoUsuario, idUsuario)
    }

    class CandidatoImageHelper {
        +obtenerImagenCandidato(foto, cache_busting)
        +generarImagenHTML(candidato, width, height, class)
        +tieneFotoPersonalizada(foto)
    }

    class Database {
        -mysqli connection
        +getConnection()
        +closeConnection()
    }

    %% Relaciones entre Controladores y Modelos
    AuthController --> EstudianteModel
    AdminController --> Admin
    AdminController --> Estadisticas
    DocenteController --> DocenteModel
    DocenteController --> AdministrativoModel
    AdminCandidatosController --> Candidatos
    ProcesarVotosController --> Votos
    EleccionConfigController --> EleccionConfigModel

    %% Relaciones de Modelos con Database
    EstudianteModel --> Database
    DocenteModel --> Database
    AdministrativoModel --> Database
    Admin --> Database
    Candidatos --> Database
    Votos --> Database
    EleccionConfigModel --> Database
    Estadisticas --> Database
    RepresentanteDocenteModel --> Database

    %% Relaciones de uso de Utilidades
    AuthController --> SessionManager
    AuthController --> EleccionMiddleware
    AdminController --> SessionManager
    DocenteController --> EleccionMiddleware
    AdminCandidatosController --> CandidatoImageHelper
    ProcesarVotosController --> SessionManager

    %% Relaciones de herencia/composición
    Votos --> EstudianteModel : uses
    Votos --> DocenteModel : uses
    Votos --> AdministrativoModel : uses
    Estadisticas -->
    Estadisticas --> Votos : uses
    Estadisticas --> EstudianteModel : uses
    Estadisticas --> Candidatos : uses
```

---

## DIAGRAMA DE BASE DE DATOS

```mermaid
erDiagram
    %% Tabla de configuración de elecciones
    configuracion_elecciones {
        int id PK
        varchar nombre_eleccion
        text descripcion
        datetime fecha_inicio
        datetime fecha_cierre
        enum estado "programada, activa, cerrada, cancelada"
        json tipos_votacion
        json configuracion_adicional
        int creado_por
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }

    %% Tabla de estudiantes
    estudiantes {
        varchar documento PK
        varchar nombre
        varchar apellido
        varchar grado
        varchar grupo
        varchar correo
        tinyint estado
        timestamp fecha_registro
    }

    %% Tabla de docentes
    docentes {
        varchar codigo_docente PK
        varchar nombre
        varchar correo
        varchar area
        enum estado "ACTIVO, INACTIVO"
        timestamp fecha_registro
    }

    %% Tabla de administrativos
    administrativos {
        int id PK
        varchar cedula UK
        varchar nombre
        varchar apellido
        varchar correo
        varchar telefono
        varchar cargo
        enum estado "Activo, Inactivo"
        timestamp fecha_registro
    }

    %% Tabla de administradores
    administradores {
        int id PK
        varchar usuario UK
        varchar password
        varchar nombre
        varchar correo
        varchar imagen_url
        tinyint activo
        timestamp fecha_creacion
    }

    %% Tabla de candidatos
    candidatos {
        int id_candidato PK
        varchar nombre
        varchar apellido
        varchar numero UK
        enum tipo_candidato "PERSONERO, REPRESENTANTE"
        int grado
        varchar foto
        text propuesta
        tinyint estado
        timestamp fecha_registro
    }

    %% Tabla de representantes docentes
    representante_docente {
        varchar codigo_repres_docente PK
        varchar nombre_repre_docente
        varchar correo_repre_docente
        varchar telefono_repre_docente
        varchar direccion_repre_docente
        varchar cargo_repre_docente
        varchar propuesta_repre_docente
    }

    %% Tabla de votos de estudiantes
    votos {
        int id PK
        varchar id_estudiante FK
        int id_candidato FK
        enum tipo_voto "PERSONERO, REPRESENTANTE"
        timestamp fecha_voto
    }

    %% Tabla de votos de docentes
    votos_docentes {
        int id_voto PK
        varchar id_docente FK
        varchar codigo_representante FK
        tinyint voto_blanco
        timestamp fecha_voto
    }

    %% Tabla de votos de administrativos
    votos_administrativos {
        int id_voto PK
        int id_administrativo FK
        varchar codigo_representante FK
        tinyint voto_blanco
        timestamp fecha_voto
        varchar ip_address
        text user_agent
    }

    %% Tabla de logs de acceso
    logs_acceso_elecciones {
        int id PK
        enum tipo_usuario "estudiante, docente, administrativo"
        varchar id_usuario
        varchar nombre_usuario
        enum tipo_acceso "intento, exitoso, bloqueado, voto"
        varchar motivo
        int id_eleccion FK
        varchar ip_address
        text user_agent
        json datos_adicionales
        timestamp fecha_acceso
    }

    %% Tabla de configuración del sistema
    configuracion_sistema {
        int id PK
        varchar clave UK
        text valor
        varchar tipo
        varchar descripcion
        varchar categoria
        int modificado_por FK
        timestamp fecha_modificacion
    }

    %% Relaciones
    estudiantes ||--o{ votos : "vota"
    candidatos ||--o{ votos : "recibe_votos"
    docentes ||--o{ votos_docentes : "vota"
    administrativos ||--o{ votos_administrativos : "vota"
    representante_docente ||--o{ votos_docentes : "recibe_votos"
    representante_docente ||--o{ votos_administrativos : "recibe_votos"
    configuracion_elecciones ||--o{ logs_acceso_elecciones : "registra"
    administradores ||--o{ configuracion_elecciones : "crea"
    administradores ||--o{ configuracion_sistema : "modifica"
```

---

## ARQUITECTURA DEL SISTEMA

### Patrón Arquitectónico: MVC (Model-View-Controller)

```mermaid
graph TB
    %% Capa de Presentación
    subgraph "CAPA DE PRESENTACIÓN (Views)"
        V1[Login Estudiantes]
        V2[Panel Votación]
        V3[Login Docentes]
        V4[Panel Admin]
        V5[Gestión Candidatos]
        V6[Configuración Elecciones]
        V7[Reportes y Estadísticas]
    end

    %% Capa de Controladores
    subgraph "CAPA DE CONTROLADORES"
        C1[AuthController]
        C2[DocenteController]
        C3[AdminController]
        C4[ProcesarVotosController]
        C5[AdminCandidatosController]
        C6[EleccionConfigController]
        C7[ResultadosController]
    end

    %% Capa de Modelos
    subgraph "CAPA DE MODELOS (Business Logic)"
        M1[EstudianteModel]
        M2[DocenteModel]
        M3[AdministrativoModel]
        M4[Candidatos]
        M5[Votos]
        M6[EleccionConfigModel]
        M7[Estadisticas]
    end

    %% Capa de Utilidades
    subgraph "CAPA DE UTILIDADES"
        U1[SessionManager]
        U2[EleccionMiddleware]
        U3[CandidatoImageHelper]
        U4[Database]
    end

    %% Capa de Datos
    subgraph "CAPA DE DATOS"
        DB[(Base de Datos MySQL)]
        FILES[Archivos de Imágenes]
        LOGS[Logs del Sistema]
    end

    %% Flujo de datos
    V1 --> C1
    V2 --> C4
    V3 --> C2
    V4 --> C3
    V5 --> C5
    V6 --> C6
    V7 --> C7

    C1 --> M1
    C2 --> M2
    C2 --> M3
    C3 --> M7
    C4 --> M5
    C5 --> M4
    C6 --> M6
    C7 --> M7

    M1 --> U4
    M2 --> U4
    M3 --> U4
    M4 --> U4
    M5 --> U4
    M6 --> U4
    M7 --> U4

    C1 --> U1
    C1 --> U2
    C2 --> U2
    C4 --> U1
    C5 --> U3

    U4 --> DB
    U3 --> FILES
    U2 --> LOGS
```

---

## FLUJO DE PROCESOS PRINCIPALES

### Proceso de Votación de Estudiantes

```mermaid
sequenceDiagram
    participant E as Estudiante
    participant AC as AuthController
    participant EM as EleccionMiddleware
    participant EstM as EstudianteModel
    participant PVC as ProcesarVotosController
    participant VM as Votos
    participant DB as Database

    E->>AC: Ingresa documento
    AC->>EM: Verificar estado elección
    EM->>DB: Consultar elecciones activas
    DB-->>EM: Estado elección
    EM-->>AC: Elección activa
    
    AC->>EstM: Autenticar estudiante
    EstM->>DB: Buscar estudiante
    DB-->>EstM: Datos estudiante
    EstM-->>AC: Estudiante válido
    
    AC->>VM: Verificar si ya votó
    VM->>DB: Consultar votos previos
    DB-->>VM: No ha votado
    VM-->>AC: Puede votar
    
    AC-->>E: Mostrar interfaz votación
    
    E->>PVC: Seleccionar candidatos
    PVC->>VM: Registrar voto personero
    VM->>DB: INSERT voto personero
    DB-->>VM: Voto registrado
    
    PVC->>VM: Registrar voto representante
    VM->>DB: INSERT voto representante
    DB-->>VM: Voto registrado
    
    PVC->>VM: Finalizar votación
    VM->>DB: Verificar votos completos
    DB-->>VM: Votación completa
    VM-->>PVC: Votación exitosa
    PVC-->>E: Confirmación votación
```

### Proceso de Configuración de Elecciones

```mermaid
sequenceDiagram
    participant A as Administrador
    participant AC as AdminController
    participant ECC as EleccionConfigController
    participant ECM as EleccionConfigModel
    participant DB as Database

    A->>AC: Login administrador
    AC->>DB: Verificar credenciales
    DB-->>AC: Credenciales válidas
    AC-->>A: Acceso panel admin
    
    A->>ECC: Crear nueva elección
    ECC->>ECM: Validar datos elección
    ECM->>ECM: Validar horarios
    ECM->>ECM: Verificar conflictos
    ECM->>DB: Insertar configuración
    DB-->>ECM: Elección creada
    ECM-->>ECC: ID nueva elección
    ECC-->>A: Elección configurada
    
    A->>ECC: Activar elección
    ECC->>ECM: Cambiar estado a 'activa'
    ECM->>DB: UPDATE estado elección
    DB-->>ECM: Estado actualizado
    ECM-->>ECC: Elección activada
    ECC-->>A: Confirmación activación
```

---

## PATRONES DE DISEÑO IMPLEMENTADOS

### 1. **Singleton Pattern**
- **Clase**: `Database`
- **Propósito**: Garantizar una única instancia de conexión a la base de datos
- **Implementación**: Control de instancia única para optimizar recursos

### 2. **Factory Pattern**
- **Clase**: `SessionManager`
- **Propósito**: Crear y gestionar diferentes tipos de sesiones (estudiante, docente, admin)
- **Implementación**: Métodos estáticos para crear sesiones específicas

### 3. **Strategy Pattern**
- **Clase**: `EleccionMiddleware`
- **Propósito**: Diferentes estrategias de validación según el tipo de usuario
- **Implementación**: Métodos específicos para cada tipo de votante

### 4. **Observer Pattern**
- **Clase**: `LogsAccesoModel`
- **Propósito**: Registrar eventos del sistema automáticamente
- **Implementación**: Registro automático de accesos y votaciones

### 5. **Template Method Pattern**
- **Clases**: Controladores de administración
- **Propósito**: Estructura común para operaciones CRUD
- **Implementación**: Métodos base para crear, leer, actualizar, eliminar

---

## CONSIDERACIONES DE SEGURIDAD

### Autenticación y Autorización
- **Validación de entrada**: Sanitización de todos los datos de entrada
- **Control de sesiones**: Gestión segura de sesiones con timeouts
- **Verificación de permisos**: Middleware para verificar acceso por rol
- **Prevención de ataques**: Protección contra SQL injection y XSS

### Integridad de Datos
- **Transacciones**: Uso de transacciones para operaciones críticas
- **Validaciones**: Múltiples niveles de validación de datos
- **Auditoría**: Registro completo de todas las acciones del sistema
- **Respaldos**: Estrategia de respaldo automático de datos

### Privacidad del Voto
- **Anonimización**: Los votos no se asocian directamente con identidades
- **Separación de datos**: Información de votantes separada de votos
- **Encriptación**: Datos sensibles encriptados en tránsito y reposo
- **Acceso restringido**: Solo administradores pueden acceder a resultados

---

## MÉTRICAS Y MONITOREO

### Indicadores Clave de Rendimiento (KPIs)
- **Participación electoral**: Porcentaje de votantes que ejercieron su derecho
- **Tiempo de respuesta**: Velocidad de procesamiento de votos
- **Disponibilidad del sistema**: Uptime durante período electoral
- **Errores de sistema**: Tasa de errores y fallos

### Monitoreo en Tiempo Real
- **Dashboard administrativo**: Estadísticas en vivo del proceso
- **Alertas automáticas**: Notificaciones de eventos críticos
- **Logs de auditoría**: Registro detallado de todas las operaciones
- **Métricas de uso**: Análisis de patrones de acceso y uso

---

## CONCLUSIONES

Este análisis presenta un sistema de votación electrónica robusto y bien estructurado que implementa:

1. **Arquitectura MVC clara** con separación de responsabilidades
2. **Múltiples tipos de usuarios** con diferentes niveles de acceso
3. **Proceso electoral completo** desde configuración hasta resultados
4. **Seguridad integral** con múltiples capas de protección
5. **Escalabilidad** para manejar diferentes tipos de elecciones
6. **Auditoría completa** con trazabilidad de todas las operaciones

El sistema está diseñado para garantizar la integridad, seguridad y transparencia del proceso electoral, cumpliendo con los requisitos de un entorno educativo y proporcionando una base sólida para futuras mejoras y expansiones.

### Recomendaciones para Mejoras Futuras:

1. **Implementar autenticación de dos factores** para administradores
2. **Agregar notificaciones en tiempo real** para eventos críticos
3. **Desarrollar API REST** para integración con otros sistemas
4. **Implementar sistema de respaldos automáticos** más robusto
5. **Agregar funcionalidad de exportación** de datos en múltiples formatos
6. **Desarrollar aplicación móvil** para facilitar el acceso
7. **Implementar sistema de encuestas** post-electorales
8. **Agregar funcionalidad de múltiples idiomas** para inclusión

---

**Documento generado por:** Análisis de Sistema  
**Fecha:** 2025-01-09  
**Versión:** 1.0  
**Estado:** Completo