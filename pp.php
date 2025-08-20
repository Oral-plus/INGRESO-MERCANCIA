<?php
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Obtener empresas de la base de datos
$sql = "SELECT id, nombre FROM empresas ORDER BY nombre";
$stmt = $database->query($sql);
$empresas = $database->fetchAll($stmt);

$database->closeConnection();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSS de Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- jQuery (requerido por Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JS de Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <title>Sistema de Mercancia</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8faff 0%, #e8f2ff 100%);
            min-height: 100vh;
            padding: 40px 20px;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 76, 151, 0.08);
            overflow: hidden;
            border-top: 4px solid #004C97;
        }
        .header {
            padding: 40px 40px 20px;
            background: linear-gradient(135deg, #004C97 0%, #0056a8 100%);
            color: white;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: white;
            margin-bottom: 4px;
        }
        .header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
        .form {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group:last-child {
            margin-bottom: 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #1a1a1a;
            font-size: 14px;
            font-weight: 600;
        }
        .required {
            color: #004C97;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: all 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #004C97;
            box-shadow: 0 0 0 3px rgba(0, 76, 151, 0.1);
        }
        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .monetary-input {
            position: relative;
        }
        .monetary-input input {
            padding-left: 28px;
        }
        .monetary-input::before {
            content: '$';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #004C97;
            font-size: 14px;
            font-weight: 600;
            pointer-events: none;
        }
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #004C97 0%, #0056a8 100%);
            color: white;
            border: none;
            padding: 16px 20px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 32px;
            box-shadow: 0 4px 12px rgba(0, 76, 151, 0.2);
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, #003875 0%, #004C97 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 76, 151, 0.3);
        }
        .submit-btn:active {
            transform: translateY(1px);
        }
        .info-text {
            font-size: 12px;
            color: #004C97;
            margin-top: 4px;
            font-weight: 500;
        }
        .botones {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 20px; /* Added margin-top for spacing */
        }
        .botones a {
            text-decoration: none;
            background-color: white;
            color: #004C97;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: bold;
            text-align: center;
            transition: background 0.3s ease;
            border: 1px solid #004C97; /* Added border for better visibility */
        }
        .botones a:hover {
            background-color: #f0f0f0;
        }
        @media (min-width: 600px) {
            .botones {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        @media (max-width: 640px) {
            body {
                padding: 20px 16px;
            }
            .container {
                border-radius: 0;
                box-shadow: none;
            }
            .header,
            .form {
                padding: 24px;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .header h1 {
                font-size: 20px;
            }
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .producto-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
        }
        .remove-btn:hover {
            background: #c82333;
        }
        .remision-info {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
            font-style: italic;
        }
        .loading {
            color: #004C97;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sistema de Mercancia</h1>
            <p>Gestión empresarial simplificada</p>
        </div>
        <div class="form">
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <form id="businessForm" action="procesar_formulario.php" method="post">
                <!-- TIPO -->
                <div class="form-group">
                    <label for="tipo">Tipo de Documento <span class="required">*</span></label>
                    <select id="tipo" name="tipo" onchange="mostrarCamposPorTipo()" required>
                        <option value="">Seleccione tipo</option>
                        <option value="FACTURA">FACTURA</option>
                        <option value="REMISION">REMISION</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="empresa">Empresa</label>
                        <select id="empresa" name="empresa_id" onchange="cargarProductos(); cargarRemisiones();" style="width: 100%;">
                            <option value="">Seleccione una empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo htmlspecialchars($empresa['id']); ?>">
                                    <?php echo htmlspecialchars($empresa['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha">Fecha <span class="required">*</span></label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="numero_documento">Número de Documento <span class="required">*</span></label>
                    <input type="text" id="numero_documento" name="numero_documento" required>
                </div>
                <!-- Campo para seleccionar remisión (solo para facturas) -->
                <div id="campoRemision" class="form-group" style="display: none;">
                    <label for="remision_relacionada">Remisión Relacionada</label>
                    <select id="remision_relacionada" name="remision_relacionada_id">
                        <option value="">Seleccione una remisión (opcional)</option>
                    </select>
                    <div class="remision-info">
                        Seleccione una remisión para relacionar con esta factura. La remisión se marcará como cerrada.
                    </div>
                </div>
                <div class="form-group">
                    <label>Productos/Servicios</label>
                    <div id="productos-container">
                        <div class="producto-item" id="producto-0">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Producto/Servicio</label>
                                    <select name="producto_id[]" class="producto-select" required>
                                        <option value="">Seleccione un producto</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Unidad</label>
                                    <select name="unidad[]" required>
                                        <option value="">Seleccione unidad</option>
                                        <option value="KG">KG</option>
                                        <option value="UND">UND</option>
                                        <option value="LT">LT</option>
                                        <option value="GL">GL</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <input type="number" name="cantidad[]" placeholder="0" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="agregarProducto()" style="margin-top: 10px; background-color: #004C97; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer;">➕ Agregar otro producto</button>
                </div>
                <div id="camposFactura">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fabrica">Fabricante</label>
                            <select id="fabrica" name="fabrica" required>
                                <option value="">Seleccione una opción</option>
                                <option value="PREPARACION">PREPARACION</option>
                                <option value="DESPACHOS">DESPACHOS</option>
                                <option value="ENSAMBLE">ENSAMBLE</option>
                                <option value="ACONDICIONAMIENTO">ACONDICIONAMIENTO</option>
                                <option value="CEPILLOS">CEPILLOS</option>
                                <option value="INYECCION">INYECCION</option>
                                <option value="LITOGRAFIA">LITOGRAFIA</option>
                                <option value="COLAPSIBLE">COLAPSIBLE</option>
                                <option value="SELLADO">SELLADO</option>
                                <option value="ENVASADO">ENVASADO</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group monetary-input">
                            <label for="valor_unitario">Subtotal <span class="required">*</span></label>
                            <input type="number" id="valor_unitario" name="valor_unitario" step="0.01" min="0" placeholder="0.00" required>
                            <div class="info-text">Sin IVA</div>
                        </div>
                    </div>
                    <div class="form-group monetary-input">
                        <label for="valor_total">Valor Total <span class="required">*</span></label>
                        <input type="number" id="valor_total" name="valor_total" step="0.01" min="0" placeholder="0.00" required>
                        <div class="info-text">Con IVA incluido</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="recibido_completo">Recibido Completo <span class="required">*</span></label>
                        <select id="recibido_completo" name="recibido_completo" required>
                            <option value="">Seleccione una opción</option>
                            <option value="SI">SI</option>
                            <option value="NO">NO</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nombre_quien_recibe">Nombre quien recibe <span class="required">*</span></label>
                        <select id="nombre_quien_recibe" name="nombre_quien_recibe" required>
                            <option value="">Seleccione un nombre</option>
                            <option value="MARIANA MISAS">MARIANA MISAS</option>
                            <option value="GIOVANA GARCIA">GIOVANA GARCIA</option>
                            <option value="LUIS MEJIA">LUIS MEJIA</option>
                            <option value="CARLOS ACEVEDO">CARLOS ACEVEDO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inconsistencias">Inconsistencias</label>
                    <input type="text" id="inconsistencias" name="inconsistencias" placeholder="Describa cualquier inconsistencia">
                </div>
                <div class="form-group">
                    <label for="cantidad_total">Cantidad Total</label>
                    <input type="number" id="cantidad_total" name="cantidad_total" readonly>
                </div>
                <button type="submit" class="submit-btn">
                    📄 Procesar Documento
                </button>
                <div class="botones">
                    <a href="ver_registros.php">📋 Ver Registros</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        let contadorProductos = 1;

        function agregarProducto() {
            const empresaSelect = document.getElementById("empresa");
            const empresaId = empresaSelect.value;
            const empresaNombre = empresaSelect.options[empresaSelect.selectedIndex].text;

            if (!empresaId) {
                alert("Por favor, seleccione una empresa primero.");
                return;
            }

            const productosContainer = document.getElementById("productos-container");
            const productoItem = document.createElement("div");
            productoItem.classList.add("producto-item");
            productoItem.id = `producto-${contadorProductos}`;

            productoItem.innerHTML = `
                <div class="form-row">
                    <div class="form-group">
                        <label>Producto/Servicio</label>
                        <select name="producto_id[]" class="producto-select" required>
                            <option value="">Cargando productos...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unidad</label>
                        <select name="unidad[]" required>
                            <option value="">Seleccione unidad</option>
                            <option value="KG">KG</option>
                            <option value="UND">UND</option>
                            <option value="LT">LT</option>
                            <option value="GL">GL</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cantidad</label>
                        <input type="number" name="cantidad[]" placeholder="0" min="0" step="0.01" required>
                    </div>
                </div>
                <button type="button" onclick="eliminarProducto(${contadorProductos})" class="remove-btn">Eliminar producto</button>
            `;
            productosContainer.appendChild(productoItem);

            // Initialize Select2 for the new product select
            const newProductSelect = productoItem.querySelector('.producto-select');
            $(newProductSelect).select2({
                placeholder: "Seleccione un producto",
                allowClear: true
            });

            // Load products for the new select
            cargarProductosParaSelect(newProductSelect, empresaId);

            contadorProductos++;
        }

        function eliminarProducto(id) {
            const productos = document.querySelectorAll('.producto-item');
            if (productos.length > 1) {
                document.getElementById(`producto-${id}`).remove();
                actualizarCantidadTotal();
            } else {
                alert("Debe haber al menos un producto.");
            }
        }

        function cargarProductos() {
            const empresaSelect = document.getElementById("empresa");
            const empresaId = empresaSelect.value;
            const selects = document.querySelectorAll(".producto-select");

            selects.forEach(select => {
                // Clear existing options and add loading message
                select.innerHTML = '<option value="">Cargando productos...</option>';
                $(select).val(null).trigger('change'); // Clear Select2 selection

                if (empresaId) {
                    cargarProductosParaSelect(select, empresaId);
                } else {
                    select.innerHTML = '<option value="">Seleccione un producto</option>';
                }
            });
        }

        function cargarProductosParaSelect(selectElement, empresaId) {
            fetch(`get_productos.php?empresa_id=${encodeURIComponent(empresaId)}`)
                .then(response => response.json())
                .then(productos => {
                    selectElement.innerHTML = '<option value="">Seleccione un producto</option>';
                    productos.forEach(prod => {
                        const option = document.createElement("option");
                        option.value = prod.id; // Use product ID
                        option.textContent = prod.nombre_producto;
                        selectElement.appendChild(option);
                    });
                    if (productos.length === 0) {
                        const option = document.createElement("option");
                        option.value = "";
                        option.textContent = "No hay productos disponibles";
                        option.disabled = true;
                        selectElement.appendChild(option);
                    }
                    $(selectElement).trigger('change'); // Update Select2
                })
                .catch(error => {
                    console.error('Error cargando productos:', error);
                    selectElement.innerHTML = '<option value="">Error cargando productos</option>';
                    $(selectElement).trigger('change');
                });
        }

        function cargarRemisiones() {
            const empresaId = document.getElementById("empresa").value;
            const remisionSelect = document.getElementById("remision_relacionada");

            if (!empresaId) {
                remisionSelect.innerHTML = '<option value="">Seleccione una remisión (opcional)</option>';
                return;
            }

            remisionSelect.innerHTML = '<option value="">Cargando remisiones...</option>';

            fetch(`get_remisiones.php?empresa_id=${encodeURIComponent(empresaId)}`)
                .then(response => response.json())
                .then(remisiones => {
                    remisionSelect.innerHTML = '<option value="">Seleccione una remisión (opcional)</option>';
                    remisiones.forEach(remision => {
                        const option = document.createElement("option");
                        option.value = remision.id;
                        option.textContent = `${remision.numero_documento} - ${remision.fecha} (Cant: ${remision.cantidad_total || 'N/A'})`;
                        remisionSelect.appendChild(option);
                    });
                    if (remisiones.length === 0) {
                        const option = document.createElement("option");
                        option.value = "";
                        option.textContent = "No hay remisiones disponibles";
                        option.disabled = true;
                        remisionSelect.appendChild(option);
                    }
                })
                .catch(error => {
                    console.error('Error cargando remisiones:', error);
                    remisionSelect.innerHTML = '<option value="">Error cargando remisiones</option>';
                });
        }

        function mostrarCamposPorTipo() {
            const tipo = document.getElementById("tipo").value;
            const camposFactura = document.getElementById("camposFactura");
            const campoRemision = document.getElementById("campoRemision");

            const facturaInputs = camposFactura.querySelectorAll("input, select");
            const remisionRelacionadaSelect = document.getElementById("remision_relacionada");

            if (tipo === "REMISION") {
                camposFactura.style.display = "none";
                campoRemision.style.display = "none";
                facturaInputs.forEach(el => el.required = false);
                remisionRelacionadaSelect.required = false;
            } else if (tipo === "FACTURA") {
                camposFactura.style.display = "block";
                campoRemision.style.display = "block";
                facturaInputs.forEach(el => el.required = true);
                // remision_relacionada is optional, so don't set required
            } else {
                camposFactura.style.display = "none";
                campoRemision.style.display = "none";
                facturaInputs.forEach(el => el.required = false);
                remisionRelacionadaSelect.required = false;
            }
        }

        function actualizarCantidadTotal() {
            const cantidades = document.querySelectorAll('input[name="cantidad[]"]');
            let total = 0;
            cantidades.forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            const totalField = document.getElementById("cantidad_total");
            if (totalField) {
                totalField.value = total.toFixed(2);
            }
        }

        // Evento global para actualizar total en tiempo real
        document.addEventListener('input', function(e) {
            if (e.target && e.target.name === 'cantidad[]') {
                actualizarCantidadTotal();
            }
        });

        // Auto-rellenar fecha actual
        window.addEventListener("DOMContentLoaded", () => {
            document.getElementById('fecha').value = new Date().toISOString().split('T')[0];
            mostrarCamposPorTipo();
            actualizarCantidadTotal();

            // Initialize Select2 for the initial product select
            $('#empresa').select2({
                placeholder: "Seleccione una empresa",
                allowClear: true
            });
            $('.producto-select').select2({
                placeholder: "Seleccione un producto",
                allowClear: true
            });
        });
    </script>
</body>
</html>
