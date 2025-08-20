<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Verificar si la conexión fue exitosa
if ($conn === false) {
    die("No se pudo conectar a la base de datos. Por favor, revise la configuración.");
}

// Obtener empresas de la base de datos
$sql = "SELECT id, nombre FROM empresas ORDER BY nombre";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    echo "Error al ejecutar la consulta de empresas.<br />";
    die(print_r(sqlsrv_errors(), true));
}

$empresas = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $empresas[] = $row;
}
sqlsrv_free_stmt($stmt);

// Obtener todos los productos agrupados por empresa_id
$sqlProductos = "SELECT empresa_id, nombre_producto FROM productos_empresa WHERE activo = 1";
$stmtProductos = sqlsrv_query($conn, $sqlProductos);
if ($stmtProductos === false) {
    echo "Error al ejecutar la consulta de productos.<br />";
    die(print_r(sqlsrv_errors(), true));
}

$productos_raw = [];
while ($row = sqlsrv_fetch_array($stmtProductos, SQLSRV_FETCH_ASSOC)) {
    $productos_raw[] = $row;
}
sqlsrv_free_stmt($stmtProductos);

// Agrupar productos por nombre de empresa
$productosAgrupados = [];
foreach ($productos_raw as $producto) {
    $empresaId = $producto['empresa_id'];
    $empresaNombre = '';
    foreach ($empresas as $emp) {
        if ($emp['id'] == $empresaId) {
            $empresaNombre = $emp['nombre'];
            break;
        }
    }
    if ($empresaNombre) {
        $productosAgrupados[$empresaNombre][] = $producto['nombre_producto'];
    }
}

// Convertir a JSON para usar en JS
$productos_js = json_encode($productosAgrupados);
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
        
        /* Estilos específicos para Select2 múltiple */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ddd;
            border-radius: 6px;
            min-height: 44px;
            padding: 4px 8px;
        }
        
        .select2-container--default .select2-selection--multiple:focus {
            border-color: #004C97;
            box-shadow: 0 0 0 3px rgba(0, 76, 151, 0.1);
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #004C97;
            border: 1px solid #004C97;
            color: white;
            padding: 2px 8px;
            margin: 2px;
            border-radius: 4px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 5px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #ffcccc;
        }
        
        .remisiones-seleccionadas {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .remision-tag {
            display: inline-block;
            background-color: #004C97;
            color: white;
            padding: 4px 8px;
            margin: 2px;
            border-radius: 4px;
            font-size: 12px;
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
                        <select id="empresa" name="empresa" onchange="cargarProductos(); cargarRemisiones();" style="width: 100%;">
                            <option value="">Seleccione una empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo htmlspecialchars($empresa['nombre']); ?>">
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
                    <label for="numero_factura">Número de Documento <span class="required">*</span></label>
                    <input type="text" id="numero_factura" name="numero_factura" required>
                </div>

                <!-- Campo para seleccionar múltiples remisiones (solo para facturas) -->
                <div id="campoRemision" class="form-group" style="display: none;">
                    <label for="remisiones_relacionadas">Remisiones Relacionadas</label>
                    <select id="remisiones_relacionadas" name="remisiones_relacionadas[]" multiple="multiple" style="width: 100%;">
                    </select>
                    <div class="remision-info">
                        Seleccione una o más remisiones para relacionar con esta factura. Las remisiones seleccionadas se marcarán como cerradas.
                    </div>
                    <div id="resumen-remisiones" class="remisiones-seleccionadas" style="display: none;">
                        <strong>Remisiones seleccionadas:</strong>
                        <div id="lista-remisiones-seleccionadas"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Productos/Servicios</label>
                    <div id="productos-container">
                        <div class="producto-item">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Producto/Servicio</label>
                                    <select name="producto[]" class="producto-select" required>
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
                                <option value="ENVASADO">ENVASADO</option>
                                <option value="SELLADO">SELLADO</option>
                                <option value="LOTEO">LOTEO</option>
                                <option value="PRODUCCION">PRODUCCION</option>
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
                 
                <div class="botones">
                    <a href="insertar_empresa.php">📋 Ingresar empresa</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    const productos = <?php echo $productos_js; ?>;
    let contadorProductos = 1;
    let remisionesDisponibles = [];

    function agregarProducto() {
        const empresaSeleccionada = document.getElementById("empresa").value;
        const productosLista = productos[empresaSeleccionada] || [];
        
        const productoItem = document.createElement("div");
        productoItem.classList.add("producto-item");
        productoItem.id = `producto-${contadorProductos}`;

        const row = document.createElement("div");
        row.classList.add("form-row");

        // Select de productos
        const selectContainer = document.createElement("div");
        selectContainer.classList.add("form-group");
        const selectLabel = document.createElement("label");
        selectLabel.textContent = "Producto/Servicio";
        selectContainer.appendChild(selectLabel);

        const select = document.createElement("select");
        select.name = "producto[]";
        select.classList.add("producto-select");
        select.required = true;

        const defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.textContent = "Seleccione un producto";
        select.appendChild(defaultOption);

        productosLista.forEach(prod => {
            const option = document.createElement("option");
            option.value = prod;
            option.textContent = prod;
            select.appendChild(option);
        });

        selectContainer.appendChild(select);
        row.appendChild(selectContainer);

        // Select de unidad
        const unidadContainer = document.createElement("div");
        unidadContainer.classList.add("form-group");
        const unidadLabel = document.createElement("label");
        unidadLabel.textContent = "Unidad";
        unidadContainer.appendChild(unidadLabel);

        const unidadSelect = document.createElement("select");
        unidadSelect.name = "unidad[]";
        unidadSelect.required = true;

        const unidades = ["", "KG", "UND", "LT", "GL"];
        unidades.forEach(unidad => {
            const option = document.createElement("option");
            option.value = unidad;
            option.textContent = unidad === "" ? "Seleccione unidad" : unidad;
            unidadSelect.appendChild(option);
        });

        unidadContainer.appendChild(unidadSelect);
        row.appendChild(unidadContainer);

        // Campo de cantidad
        const cantidadContainer = document.createElement("div");
        cantidadContainer.classList.add("form-group");
        const cantidadLabel = document.createElement("label");
        cantidadLabel.textContent = "Cantidad";
        cantidadContainer.appendChild(cantidadLabel);

        const cantidadInput = document.createElement("input");
        cantidadInput.type = "number";
        cantidadInput.name = "cantidad[]";
        cantidadInput.placeholder = "0";
        cantidadInput.min = "0";
        cantidadInput.step = "0.01";
        cantidadInput.required = true;
        cantidadInput.addEventListener('input', actualizarCantidadTotal);
        cantidadContainer.appendChild(cantidadInput);
        row.appendChild(cantidadContainer);

        productoItem.appendChild(row);

        // Botón para eliminar (solo si hay más de un producto)
        const removeBtn = document.createElement("button");
        removeBtn.type = "button";
        removeBtn.className = "remove-btn";
        removeBtn.textContent = "Eliminar producto";
        removeBtn.onclick = () => eliminarProducto(contadorProductos);
        productoItem.appendChild(removeBtn);

        document.getElementById("productos-container").appendChild(productoItem);
        contadorProductos++;
    }

    function eliminarProducto(id) {
        const productos = document.querySelectorAll('.producto-item');
        if (productos.length > 1) {
            document.getElementById(`producto-${id}`).remove();
            actualizarCantidadTotal();
        }
    }

    function cargarProductos() {
        const empresaSeleccionada = document.getElementById("empresa").value;
        const productosLista = productos[empresaSeleccionada] || [];
        
        const selects = document.querySelectorAll(".producto-select");
        selects.forEach(select => {
            select.innerHTML = '<option value="">Seleccione un producto</option>';
            productosLista.forEach(producto => {
                const option = document.createElement("option");
                option.value = producto;
                option.textContent = producto;
                select.appendChild(option);
            });
        });
    }

    function cargarRemisiones() {
        const empresaSeleccionada = document.getElementById("empresa").value;
        const remisionSelect = $('#remisiones_relacionadas');
        
        if (!empresaSeleccionada) {
            remisionSelect.empty().trigger('change');
            remisionesDisponibles = [];
            return;
        }

        // Mostrar loading
        remisionSelect.empty().append('<option value="">Cargando remisiones...</option>').trigger('change');

        fetch(`get_remisiones.php?empresa=${encodeURIComponent(empresaSeleccionada)}`)
            .then(response => response.json())
            .then(remisiones => {
                remisionesDisponibles = remisiones;
                remisionSelect.empty();
                
                remisiones.forEach(remision => {
                    const option = new Option(
                        `${remision.numero_factura} - ${remision.fecha} (Cant: ${remision.cantidad_total || 'N/A'})`,
                        remision.id
                    );
                    remisionSelect.append(option);
                });
                
                if (remisiones.length === 0) {
                    remisionSelect.append('<option value="" disabled>No hay remisiones disponibles</option>');
                }
                
                remisionSelect.trigger('change');
            })
            .catch(error => {
                console.error('Error cargando remisiones:', error);
                remisionSelect.empty().append('<option value="">Error cargando remisiones</option>').trigger('change');
            });
    }

    function mostrarCamposPorTipo() {
        const tipo = document.getElementById("tipo").value;
        const camposFactura = document.getElementById("camposFactura");
        const campoRemision = document.getElementById("campoRemision");
        
        if (tipo === "REMISION") {
            camposFactura.style.display = "none";
            campoRemision.style.display = "none";
            const inputs = camposFactura.querySelectorAll("input, select");
            inputs.forEach(el => el.required = false);
        } else if (tipo === "FACTURA") {
            camposFactura.style.display = "block";
            campoRemision.style.display = "block";
            const inputs = camposFactura.querySelectorAll("input, select");
            inputs.forEach(el => el.required = true);
        } else {
            camposFactura.style.display = "none";
            campoRemision.style.display = "none";
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

    function actualizarResumenRemisiones() {
        const remisionesSeleccionadas = $('#remisiones_relacionadas').val();
        const resumenDiv = document.getElementById('resumen-remisiones');
        const listaDiv = document.getElementById('lista-remisiones-seleccionadas');
        
        if (remisionesSeleccionadas && remisionesSeleccionadas.length > 0) {
            resumenDiv.style.display = 'block';
            listaDiv.innerHTML = '';
            
            remisionesSeleccionadas.forEach(remisionId => {
                const remision = remisionesDisponibles.find(r => r.id == remisionId);
                if (remision) {
                    const tag = document.createElement('span');
                    tag.className = 'remision-tag';
                    tag.textContent = `${remision.numero_factura} - ${remision.fecha}`;
                    listaDiv.appendChild(tag);
                }
            });
        } else {
            resumenDiv.style.display = 'none';
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
    });
    </script>

    <script>
        $(document).ready(function() {
            // Inicializar Select2 para empresa
            $('#empresa').select2({
                placeholder: "Seleccione una empresa",
                allowClear: true
            });
            
            $('#remisiones_relacionadas').select2({
                placeholder: "Seleccione una o más remisiones",
                allowClear: true,
                closeOnSelect: false
            });
            
            $('#remisiones_relacionadas').on('change', function() {
                actualizarResumenRemisiones();
            });
        });
    </script>
</body>
</html>
