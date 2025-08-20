-- Crear base de datos
CREATE DATABASE IF NOT EXISTS sistema_mercancia;
USE sistema_mercancia;

-- Tabla para empresas
CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar empresas predefinidas
INSERT IGNORE INTO empresas (nombre) VALUES 
('PLASTICOS FR'),
('MINIPET'),
('SIGMA PLASTICOS'),
('PERYSA SAS'),
('BELL FLAVORS Y FRAGANCES'),
('ALMACEN SOLOENVASES SAS'),
('ENTACOL O PICCOLLINI'),
('TP COLOMBIA'),
('AMTEX SAS'),
('SPARCOL CHEMICAL Y LIFE SAS'),
('FLXO IMPRESOS'),
('LAMINATE PACKAGING COLOMBIA'),
('MEGA SOLUCIONES JYM SAS'),
('OTRA DINAMICA'),
('MAQUILLAS'),
('AZELIS ROCSA COLOMBIA SAS'),
('AFR SAS'),
('CODINMAQ'),
('SCAPEL SAS'),
('COLSABOR SAS'),
('WONDER PACK'),
('MULTI PROYECTOS INDUSTRIALES');

-- Tabla para facturas
CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    fecha DATE NOT NULL,
    numero_factura VARCHAR(100) NOT NULL,
    productos JSON NOT NULL,
    fabricante VARCHAR(100),
    valor_unitario DECIMAL(10,2),
    valor_total DECIMAL(10,2),
    recibido_completo ENUM('SI', 'NO') NOT NULL,
    nombre_quien_recibe VARCHAR(255) NOT NULL,
    inconsistencias TEXT,
    cantidad_total DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    INDEX idx_empresa_fecha (empresa_id, fecha),
    INDEX idx_numero_factura (numero_factura)
);

-- Tabla para remisiones
CREATE TABLE IF NOT EXISTS remisiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    fecha DATE NOT NULL,
    numero_factura VARCHAR(100) NOT NULL,
    productos JSON NOT NULL,
    recibido_completo ENUM('SI', 'NO') NOT NULL,
    nombre_quien_recibe VARCHAR(255) NOT NULL,
    inconsistencias TEXT,
    cantidad_total DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    INDEX idx_empresa_fecha (empresa_id, fecha),
    INDEX idx_numero_factura (numero_factura)
);
