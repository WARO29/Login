# Modelo Lógico de Base de Datos - Sistema de Votaciones Electrónicas COSAFA

## 📊 **ENTIDADES PRINCIPALES Y SUS RELACIONES**

### **1. ENTIDADES DE USUARIOS**

#### **ADMINISTRADORES**
- **Clave Primaria:** `id`
- **Claves Únicas:** `usuario`
- **Campos:** id, usuario, password, nombre, imagen_url, email, fecha_creacion, ultimo_acceso
- **Función:** Gestión y administración del sistema electoral

#### **ESTUDIANTES**
- **Clave Primaria:** `id_estudiante`
- **Campos:** id_estudiante, nombre, correo, apellido, grado, grupo, estado
- **Función:** Votantes principales del sistema (personero y representante)

#### **DOCENTES**
- **Clave Primaria:** `id`
- **Claves Únicas:** `codigo_docente`, `correo`
- **Campos:** id, codigo_docente, nombre, correo, area, estado
- **Función:** Votantes para representante docente

#### **ADMINISTRATIVOS**
- **Clave Primaria:** `cedula`
- **Campos:** cedula, nombre, apellido, correo, telefono, direccion, cargo, estado
- **Función:** Votantes para representante docente

### **2. ENTIDADES DE CANDIDATOS**

#### **CANDIDATOS**
- **Clave Primaria:** `id_candidato`
- **Índices:** `tipo_candidato`
- **Campos:** id_candidato, nombre, apellido, numero, tipo_candidato, grado, foto, propuesta
- **Tipos:** PERSONERO, REPRESENTANTE
- **Función:** Candidatos para votación estudiantil

#### **REPRESENTANTE_DOCENTE**
- **Clave Primaria:** `codigo_repres_docente`
- **Claves Únicas:** `correo_repre_docente`
- **Campos:** codigo_repres_docente, nombre_repre_docente, correo_repre_docente, telefono_repre_docente, direccion_repre_docente, cargo_repre_docente, propuesta_repre_docente, created_at, updated_at
- **Función:** Candidatos para votación de docentes y administrativos

### **3. ENTIDADES DE VOTACIÓN**

#### **VOTOS** (Estudiantes)
- **Clave Primaria:** `id_voto`
- **Clave Única Compuesta:** `(id_estudiante, tipo_voto)`
- **Claves Foráneas:** 
  - `id_estudiante` → `estudiantes(id_estudiante)`
  - `id_candidato` → `candidatos(id_candidato)` [NULLABLE para votos en blanco]
- **Campos:** id_voto, id_estudiante, id_candidato, tipo_voto, fecha_voto
- **Función:** Registro de votos de estudiantes

#### **VOTOS_DOCENTES**
- **Clave Primaria:** `id_voto`
- **Campos:** id_voto, id_docente, codigo_representante, voto_blanco, fecha_voto
- **Función:** Registro de votos de docentes

#### **VOTOS_ADMINISTRATIVOS**
- **Clave Primaria:** `id_voto`
- **Clave Única:** `id_administrativo`
- **Índices:** `id_administrativo`, `codigo_representante`, `fecha_voto`
- **Campos:** id_voto, id_administrativo, codigo_representante, voto_blanco, fecha_voto, ip_address, user_agent
- **Función:** Registro de votos de administrativos

### **4. ENTIDADES DE CONFIGURACIÓN**

#### **CONFIGURACION_ELECCIONES**
- **Clave Primaria:** `id`
- **Clave Foránea:** `creado_por` → `administradores(id)`
- **Índices:** `estado`, `(fecha_inicio, fecha_cierre)`, `creado_por`
- **Campos:** id, nombre_eleccion, descripcion, fecha_inicio, fecha_cierre, estado, tipos_votacion, configuracion_adicional, creado_por, fecha_creacion, fecha_actualizacion
- **Estados:** programada, activa, cerrada, cancelada
- **Función:** Control temporal y configuración de elecciones

#### **CONFIGURACION_SISTEMA**
- **Clave Primaria:** `id`
- **Clave Única:** `clave`
- **Clave Foránea:** `modificado_por` → `administradores(id)`
- **Índices:** `clave`, `categoria`
- **Campos:** id, clave, valor, descripcion, tipo, categoria, modificado_por, fecha_modificacion
- **Tipos:** string, integer, boolean, datetime, json
- **Función:** Configuración general del sistema

### **5. ENTIDADES DE AUDITORÍA**

#### **LOGS_ACCESO_ELECCIONES**
- **Clave Primaria:** `id`
- **Clave Foránea:** `id_eleccion` → `configuracion_elecciones(id)`
- **Índices:** `id_eleccion`, `tipo_usuario`, `fecha_evento`, `accion`
- **Campos:** id, id_eleccion, tipo_usuario, id_usuario, cedula_usuario, nombre_usuario, accion, motivo, ip_address, user_agent, fecha_evento
- **Tipos Usuario:** estudiante, docente, administrativo, administrador
- **Acciones:** intento_login, login_exitoso, login_bloqueado, voto_registrado
- **Función:** Auditoría y trazabilidad del sistema

## 🔗 **RELACIONES ENTRE ENTIDADES**

### **RELACIONES PRINCIPALES**

```
ADMINISTRADORES (1) ──────── (N) CONFIGURACION_ELECCIONES
    │                              │
    │                              │
    └─── (N) CONFIGURACION_SISTEMA │
                                   │
                                   │
CONFIGURACION_ELECCIONES (1) ──── (N) LOGS_ACCESO_ELECCIONES

ESTUDIANTES (1) ──────── (N) VOTOS
                              │
                              │
CANDIDATOS (1) ──────── (N) VOTOS [NULLABLE]

DOCENTES (1) ──────── (N) VOTOS_DOCENTES
                           │
                           │
REPRESENTANTE_DOCENTE (1) ─┴─ (N) VOTOS_DOCENTES

ADMINISTRATIVOS (1) ──────── (N) VOTOS_ADMINISTRATIVOS
                                  │
                                  │
REPRESENTANTE_DOCENTE (1) ───────┘
```

### **RESTRICCIONES DE INTEGRIDAD**

#### **Restricciones de Clave Foránea:**
1. `configuracion_elecciones.creado_por` → `administradores.id`
2. `configuracion_sistema.modificado_por` → `administradores.id`
3. `logs_acceso_elecciones.id_eleccion` → `configuracion_elecciones.id`
4. `votos.id_estudiante` → `estudiantes.id_estudiante`
5. `votos.id_candidato` → `candidatos.id_candidato` [NULLABLE]

#### **Restricciones de Unicidad:**
1. `votos(id_estudiante, tipo_voto)` - Un estudiante solo puede votar una vez por tipo
2. `votos_administrativos(id_administrativo)` - Un administrativo solo puede votar una vez
3. `administradores(usuario)` - Usuario único por administrador
4. `docentes(codigo_docente, correo)` - Código y correo únicos
5. `representante_docente(correo_repre_docente)` - Correo único por representante

## 📋 **REGLAS DE NEGOCIO IMPLEMENTADAS**

### **1. CONTROL DE VOTACIÓN**
- **Un voto por tipo:** Los estudiantes pueden votar una vez por PERSONERO y una vez por REPRESENTANTE
- **Voto único administrativo:** Los administrativos solo pueden votar una vez
- **Votos en blanco:** Permitidos mediante `id_candidato = NULL` o `voto_blanco = 1`

### **2. SEGMENTACIÓN DE CANDIDATOS**
- **Personeros:** Pueden ser de cualquier grado (campo grado opcional)
- **Representantes:** Deben tener grado específico (campo grado obligatorio)
- **Representantes docentes:** Entidad separada para votación de docentes/administrativos

### **3. CONTROL TEMPORAL**
- **Estados de elección:** programada → activa → cerrada/cancelada
- **Fechas controladas:** fecha_inicio y fecha_cierre en configuracion_elecciones
- **Auditoría temporal:** Todos los votos y accesos tienen timestamp

### **4. AUDITORÍA COMPLETA**
- **Logs de acceso:** Registro de todos los intentos de login y acciones
- **Trazabilidad:** IP, user agent, motivos de bloqueo
- **Tipos de evento:** Diferenciación entre intentos, éxitos y bloqueos

## 🎯 **CARACTERÍSTICAS DEL MODELO**

### **ESCALABILIDAD**
- Índices optimizados para consultas frecuentes
- Separación de tipos de voto en tablas especializadas
- Configuración flexible mediante JSON

### **SEGURIDAD**
- Restricciones de integridad referencial
- Auditoría completa de accesos
- Control de estados y permisos

### **FLEXIBILIDAD**
- Configuración dinámica del sistema
- Soporte para múltiples tipos de elecciones
- Extensibilidad para nuevos tipos de usuarios

Este modelo lógico representa una base de datos robusta y bien estructurada para un sistema de votaciones electrónicas institucional, con controles de integridad, auditoría completa y flexibilidad para diferentes escenarios electorales.