-- INGClean - Agregar campos de ubicación si no existen
-- Ejecutar en phpMyAdmin si hay errores de columnas no encontradas

-- Agregar campos de ubicación actual si no existen
ALTER TABLE partners 
ADD COLUMN IF NOT EXISTS current_latitude DECIMAL(10, 8) NULL AFTER longitude,
ADD COLUMN IF NOT EXISTS current_longitude DECIMAL(11, 8) NULL AFTER current_latitude;

-- Si la tabla partner_locations no existe, crearla
CREATE TABLE IF NOT EXISTS partner_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    order_id INT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    accuracy FLOAT NULL,
    speed FLOAT NULL,
    heading FLOAT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_partner (partner_id),
    INDEX idx_order (order_id),
    INDEX idx_recorded (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nota: Si hay error con "IF NOT EXISTS" en ALTER TABLE, 
-- puedes ejecutar cada línea por separado:
-- ALTER TABLE partners ADD COLUMN current_latitude DECIMAL(10, 8) NULL;
-- ALTER TABLE partners ADD COLUMN current_longitude DECIMAL(11, 8) NULL;
