-- Crear tabla de logs del sistema
CREATE TABLE IF NOT EXISTS logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL COMMENT 'Tipo de acción: mesas_virtuales, elecciones, usuarios, etc.',
    descripcion TEXT NOT NULL COMMENT 'Descripción detallada de la acción',
    usuario_id INT NULL COMMENT 'ID del administrador que realizó la acción',
    datos_adicionales JSON NULL COMMENT 'Datos adicionales en formato JSON',
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la acción',
    ip_address VARCHAR(45) NULL COMMENT 'Dirección IP del usuario',
    
    -- Índices para optimizar consultas
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha_hora),
    INDEX idx_usuario (usuario_id),
    INDEX idx_tipo_fecha (tipo, fecha_hora),
    
    -- Clave foránea
    FOREIGN KEY (usuario_id) REFERENCES administradores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tabla de logs y auditoría del sistema';

-- Insertar algunos logs de ejemplo para demostración
INSERT INTO logs_sistema (tipo, descripcion, usuario_id, datos_adicionales, ip_address) VALUES
('sistema', 'Tabla de logs creada exitosamente', NULL, '{"version": "1.0", "modulo": "logs"}', '127.0.0.1'),
('mesas_virtuales', 'Sistema de logs implementado para mesas virtuales', NULL, '{"funcionalidad": "auditoria"}', '127.0.0.1');
