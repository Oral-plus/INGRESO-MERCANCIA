USE sistema_mercancia;
GO

-- Agregar campo estado a remisiones
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('remisiones') AND name = 'estado')
BEGIN
    ALTER TABLE remisiones ADD estado NVARCHAR(20) DEFAULT 'ABIERTA' CHECK (estado IN ('ABIERTA', 'CERRADA'));
END
GO

-- Agregar campo remision_id a facturas para la relación
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('facturas') AND name = 'remision_id')
BEGIN
    ALTER TABLE facturas ADD remision_id INT NULL;
    ALTER TABLE facturas ADD FOREIGN KEY (remision_id) REFERENCES remisiones(id);
END
GO

-- Actualizar remisiones existentes para que tengan estado ABIERTA
UPDATE remisiones SET estado = 'ABIERTA' WHERE estado IS NULL;
GO

-- Crear índice para mejorar rendimiento
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_remisiones_estado_empresa')
BEGIN
    CREATE INDEX idx_remisiones_estado_empresa ON remisiones(estado, empresa_id);
END
GO
