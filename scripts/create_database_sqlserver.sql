-- Crear base de datos
IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'sistema_mercancia')
BEGIN
    CREATE DATABASE sistema_mercancia;
END
GO

USE sistema_mercancia;
GO

-- Tabla para empresas
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='empresas' AND xtype='U')
BEGIN
    CREATE TABLE empresas (
        id INT IDENTITY(1,1) PRIMARY KEY,
        nombre NVARCHAR(255) NOT NULL UNIQUE,
        created_at DATETIME2 DEFAULT GETDATE()
    );
END
GO

-- Insertar empresas predefinidas
MERGE empresas AS target
USING (VALUES 
    ('OPTI MOLDES S.A.S'),

) AS source (nombre)
ON target.nombre = source.nombre
WHEN NOT MATCHED THEN
    INSERT (nombre) VALUES (source.nombre);
GO

-- Tabla para facturas
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='facturas' AND xtype='U')
BEGIN
    CREATE TABLE facturas (
        id INT IDENTITY(1,1) PRIMARY KEY,
        empresa_id INT NOT NULL,
        fecha DATE NOT NULL,
        numero_factura NVARCHAR(100) NOT NULL,
        productos NVARCHAR(MAX) NOT NULL,
        fabricante NVARCHAR(100),
        valor_unitario DECIMAL(10,2),
        valor_total DECIMAL(10,2),
        recibido_completo NVARCHAR(10) NOT NULL CHECK (recibido_completo IN ('SI', 'NO')),
        nombre_quien_recibe NVARCHAR(255) NOT NULL,
        inconsistencias NVARCHAR(MAX),
        cantidad_total DECIMAL(10,2),
        created_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    );
    
    CREATE INDEX idx_facturas_empresa_fecha ON facturas(empresa_id, fecha);
    CREATE INDEX idx_facturas_numero ON facturas(numero_factura);
END
GO

-- Tabla para remisiones
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='remisiones' AND xtype='U')
BEGIN
    CREATE TABLE remisiones (
        id INT IDENTITY(1,1) PRIMARY KEY,
        empresa_id INT NOT NULL,
        fecha DATE NOT NULL,
        numero_factura NVARCHAR(100) NOT NULL,
        productos NVARCHAR(MAX) NOT NULL,
        recibido_completo NVARCHAR(10) NOT NULL CHECK (recibido_completo IN ('SI', 'NO')),
        nombre_quien_recibe NVARCHAR(255) NOT NULL,
        inconsistencias NVARCHAR(MAX),
        cantidad_total DECIMAL(10,2),
        created_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    );
    
    CREATE INDEX idx_remisiones_empresa_fecha ON remisiones(empresa_id, fecha);
    CREATE INDEX idx_remisiones_numero ON remisiones(numero_factura);
END
GO
