-- Eliminar docentes existentes para evitar duplicados
DELETE FROM docentes;

-- Insertar datos de ejemplo en la tabla docentes
INSERT INTO docentes (codigo_docente, nombre, correo, telefono, direccion) VALUES
('1234567890', 'Juan Pérez', 'juan.perez@universidad.edu', '3001234567', 'Calle 123 #45-67'),
('0987654321', 'María López', 'maria.lopez@universidad.edu', '3109876543', 'Avenida 45 #12-34'),
('1122334455', 'Carlos Rodríguez', 'carlos.rodriguez@universidad.edu', '3157894561', 'Carrera 67 #89-12');
