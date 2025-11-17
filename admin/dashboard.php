<?php
// admin/dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../components/logo.php';

// Verificar autenticación
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Obtener estadísticas del día (funciones existentes)
function getEstadisticasDelDia() {
    $conn = getDBConnection();
    
    $query_citas = "
        SELECT 
            COUNT(*) as total_citas,
            SUM(CASE WHEN Estado_consulta = 'Completada' THEN 1 ELSE 0 END) as completadas
        FROM Consulta 
        WHERE CAST(Fecha_consulta AS DATE) = CAST(GETDATE() AS DATE)
    ";
    
    $stmt_citas = sqlsrv_query($conn, $query_citas);
    $citas = sqlsrv_fetch_array($stmt_citas, SQLSRV_FETCH_ASSOC);
    
    $query_pagos = "
        SELECT 
            COUNT(*) as total_facturas,
            SUM(CASE WHEN Estado_Factura = 'Pagada' THEN 1 ELSE 0 END) as pagadas
        FROM Facturacion 
        WHERE CAST(Fecha_Factura AS DATE) = CAST(GETDATE() AS DATE)
    ";
    
    $stmt_pagos = sqlsrv_query($conn, $query_pagos);
    $pagos = sqlsrv_fetch_array($stmt_pagos, SQLSRV_FETCH_ASSOC);
    
    $query_solicitudes = "
        SELECT 
            COUNT(*) as total_pendientes,
            SUM(CASE WHEN Estado_consulta = 'Confirmada' THEN 1 ELSE 0 END) as confirmadas
        FROM Consulta 
        WHERE CAST(Fecha_consulta AS DATE) >= CAST(GETDATE() AS DATE)
        AND Estado_consulta IN ('Pendiente', 'Confirmada')
    ";
    
    $stmt_solicitudes = sqlsrv_query($conn, $query_solicitudes);
    $solicitudes = sqlsrv_fetch_array($stmt_solicitudes, SQLSRV_FETCH_ASSOC);
    
    return [
        'citas' => [
            'completadas' => $citas['completadas'] ?? 0,
            'totales' => $citas['total_citas'] ?? 0
        ],
        'pagos' => [
            'validados' => $pagos['pagadas'] ?? 0,
            'totales' => $pagos['total_facturas'] ?? 0
        ],
        'solicitudes' => [
            'procesadas' => $solicitudes['confirmadas'] ?? 0,
            'totales' => $solicitudes['total_pendientes'] ?? 0
        ]
    ];
}

// Obtener actividad reciente (función existente)
function getActividadReciente() {
    $conn = getDBConnection();
    
    $query = "
        SELECT TOP 5
            'nueva_solicitud' as tipo,
            CONCAT('Nueva solicitud de ', p.Nombre_paciente, ' ', p.Apellido_paciente) as descripcion,
            m.Especialidad as especialidad,
            DATEDIFF(MINUTE, c.Fecha_consulta, GETDATE()) as minutos,
            c.Fecha_consulta as fecha,
            'Nueva' as etiqueta
        FROM Consulta c
        INNER JOIN Pacientes p ON c.Id_paciente = p.Id_paciente
        INNER JOIN Medicos m ON c.Id_Medico = m.Id_Medico
        WHERE c.Estado_consulta = 'Pendiente'
        AND c.Fecha_consulta >= DATEADD(DAY, -1, GETDATE())
        
        UNION ALL
        
        SELECT TOP 5
            'pago_recibido' as tipo,
            CONCAT('Pago recibido de ', p.Nombre_paciente, ' ', p.Apellido_paciente) as descripcion,
            CONCAT('L ', CAST(f.Total AS DECIMAL(10,0))) as especialidad,
            DATEDIFF(MINUTE, f.Fecha_Factura, GETDATE()) as minutos,
            f.Fecha_Factura as fecha,
            'Pago' as etiqueta
        FROM Facturacion f
        INNER JOIN Pacientes p ON f.Id_paciente = p.Id_paciente
        WHERE f.Estado_Factura = 'Pagada'
        AND f.Fecha_Factura >= DATEADD(DAY, -1, GETDATE())
        
        UNION ALL
        
        SELECT TOP 5
            'cita_confirmada' as tipo,
            CONCAT('Cita confirmada con Dr. ', m.Nombre, ' ', m.Apellido) as descripcion,
            CONCAT(FORMAT(c.Fecha_consulta, 'dd/MM/yyyy'), ', ', CONVERT(VARCHAR(8), c.Hora_consulta, 108)) as especialidad,
            DATEDIFF(MINUTE, c.Fecha_consulta, GETDATE()) as minutos,
            c.Fecha_consulta as fecha,
            'Confirmada' as etiqueta
        FROM Consulta c
        INNER JOIN Medicos m ON c.Id_Medico = m.Id_Medico
        WHERE c.Estado_consulta = 'Confirmada'
        AND c.Fecha_consulta >= DATEADD(DAY, -1, GETDATE())
        
        ORDER BY fecha DESC
    ";
    
    $stmt = sqlsrv_query($conn, $query);
    $actividades = [];
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $actividades[] = $row;
    }
    
    return $actividades;
}

// Obtener horario de médicos con citas para hoy
function getHorarioMedicosHoy() {
    $conn = getDBConnection();
    
    $query = "
        SELECT 
            m.Id_Medico,
            m.Nombre + ' ' + m.Apellido as Medico,
            m.Especialidad,
            c.Id_Consulta,
            p.Nombre_paciente + ' ' + p.Apellido_paciente as Paciente,
            c.Hora_consulta,
            c.Estado_consulta,
            cu.Numero_Cubiculo
        FROM Medicos m
        LEFT JOIN Consulta c ON m.Id_Medico = c.Id_Medico 
            AND CAST(c.Fecha_consulta AS DATE) = CAST(GETDATE() AS DATE)
            AND c.Estado_consulta IN ('Confirmada', 'Pendiente')
        LEFT JOIN Pacientes p ON c.Id_paciente = p.Id_paciente
        LEFT JOIN Cubiculos cu ON c.Id_Cubiculo = cu.Id_Cubiculo
        WHERE m.Id_Medico IN (
            SELECT DISTINCT Id_Medico 
            FROM Consulta 
            WHERE CAST(Fecha_consulta AS DATE) = CAST(GETDATE() AS DATE)
        )
        ORDER BY m.Nombre, c.Hora_consulta
    ";
    
    $stmt = sqlsrv_query($conn, $query);
    $horario = [];
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $horario[] = $row;
    }
    
    return $horario;
}

// Obtener todas las citas de hoy organizadas por hora
function getCitasPorHoraHoy() {
    $conn = getDBConnection();
    
    $query = "
        SELECT 
            c.Hora_consulta,
            m.Nombre + ' ' + m.Apellido as Medico,
            m.Especialidad,
            p.Nombre_paciente + ' ' + p.Apellido_paciente as Paciente,
            c.Estado_consulta,
            cu.Numero_Cubiculo as Cubiculo,
            c.Id_Consulta
        FROM Consulta c
        INNER JOIN Medicos m ON c.Id_Medico = m.Id_Medico
        INNER JOIN Pacientes p ON c.Id_paciente = p.Id_paciente
        LEFT JOIN Cubiculos cu ON c.Id_Cubiculo = cu.Id_Cubiculo
        WHERE CAST(c.Fecha_consulta AS DATE) = CAST(GETDATE() AS DATE)
        AND c.Estado_consulta IN ('Confirmada', 'Pendiente')
        ORDER BY c.Hora_consulta, m.Nombre
    ";
    
    $stmt = sqlsrv_query($conn, $query);
    $citas = [];
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $citas[] = $row;
    }
    
    return $citas;
}

// Obtener médicos con citas hoy
function getMedicosConCitasHoy() {
    $conn = getDBConnection();
    
    $query = "
        SELECT DISTINCT
            m.Id_Medico,
            m.Nombre + ' ' + m.Apellido as Medico,
            m.Especialidad,
            COUNT(c.Id_Consulta) as Total_Citas
        FROM Medicos m
        INNER JOIN Consulta c ON m.Id_Medico = c.Id_Medico
        WHERE CAST(c.Fecha_consulta AS DATE) = CAST(GETDATE() AS DATE)
        AND c.Estado_consulta IN ('Confirmada', 'Pendiente')
        GROUP BY m.Id_Medico, m.Nombre, m.Apellido, m.Especialidad
        ORDER BY Total_Citas DESC
    ";
    
    $stmt = sqlsrv_query($conn, $query);
    $medicos = [];
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $medicos[] = $row;
    }
    
    return $medicos;
}

// Obtener resumen financiero (función existente)
function getResumenFinanciero() {
    $conn = getDBConnection();
    
    $query = "
        SELECT 
            ISNULL(SUM(CASE WHEN Estado_Factura = 'Pagada' THEN Total ELSE 0 END), 0) as total_pagado,
            ISNULL(SUM(CASE WHEN Estado_Factura = 'Pendiente' THEN Total ELSE 0 END), 0) as total_pendiente,
            COUNT(*) as total_facturas
        FROM Facturacion 
        WHERE MONTH(Fecha_Factura) = MONTH(GETDATE())
        AND YEAR(Fecha_Factura) = YEAR(GETDATE())
    ";
    
    $stmt = sqlsrv_query($conn, $query);
    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

// Obtener total de médicos (función existente)
function getTotalMedicos() {
    $conn = getDBConnection();
    $query = "SELECT COUNT(*) as total FROM Medicos";
    $stmt = sqlsrv_query($conn, $query);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['total'] ?? 0;
}

// Obtener total de pacientes (función existente)
function getTotalPacientes() {
    $conn = getDBConnection();
    $query = "SELECT COUNT(*) as total FROM Pacientes";
    $stmt = sqlsrv_query($conn, $query);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['total'] ?? 0;
}

$estadisticas = getEstadisticasDelDia();
$actividades = getActividadReciente();
$resumenFinanciero = getResumenFinanciero();
$totalMedicos = getTotalMedicos();
$totalPacientes = getTotalPacientes();
$horarioMedicos = getHorarioMedicosHoy();
$citasPorHora = getCitasPorHoraHoy();
$medicosConCitas = getMedicosConCitasHoy();

// Horas del día para el horario
$horasDelDia = [
    '08:00', '09:00', '10:00', '11:00', 
    '12:00', '13:00', '14:00', '15:00', 
    '16:00', '17:00', '18:00'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Policlínicas San Marcos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <?php
                    $logoPath = '../assets/images/logo-clinica.png';
                    if (file_exists($logoPath)) {
                        echo '
                        <div class="flex items-center">
                            <img src="' . $logoPath . '" 
                                 alt="Policlínicas San Marcos" 
                                 class="w-12 h-12 object-contain">
                            <div class="ml-3">
                                <h1 class="text-xl font-bold text-gray-900">Panel de Administración</h1>
                                <p class="text-sm text-gray-600">Policlínicas San Marcos</p>
                            </div>
                        </div>
                        ';
                    } else {
                        echo '
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                PSM
                            </div>
                            <div class="ml-3">
                                <h1 class="text-xl font-bold text-gray-900">Panel de Administración</h1>
                                <p class="text-sm text-gray-600">Policlínicas San Marcos</p>
                            </div>
                        </div>
                        ';
                    }
                    ?>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Bienvenido</p>
                        <p class="font-semibold text-gray-900"><?php echo $_SESSION['user_nombre'] ?? 'Administrador'; ?></p>
                    </div>
                    <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-200 flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8 py-4">
                <a href="dashboard.php" class="text-blue-600 border-b-2 border-blue-600 font-medium py-2 flex items-center">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="gestion_medicos.php" class="text-gray-600 hover:text-blue-600 font-medium py-2 flex items-center">
                    <i class="fas fa-user-md mr-2"></i>Médicos
                </a>
                <a href="citas_pendientes.php" class="text-gray-600 hover:text-blue-600 font-medium py-2 flex items-center">
                    <i class="fas fa-calendar-check mr-2"></i>Citas
                </a>
                <a href="pagos_validar.php" class="text-gray-600 hover:text-blue-600 font-medium py-2 flex items-center">
                    <i class="fas fa-money-check mr-2"></i>Pagos
                </a>
                <a href="solicitudes_pendientes.php" class="text-gray-600 hover:text-blue-600 font-medium py-2 flex items-center">
                    <i class="fas fa-clipboard-list mr-2"></i>Solicitudes
                </a>
                <a href="reportes.php" class="text-gray-600 hover:text-blue-600 font-medium py-2 flex items-center">
                    <i class="fas fa-chart-bar mr-2"></i>Reportes
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Estadísticas Principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Médicos -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Médicos</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $totalMedicos; ?></p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="fas fa-user-md text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Pacientes -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Pacientes</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $totalPacientes; ?></p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-users text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Citas Hoy -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Citas Hoy</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $estadisticas['citas']['completadas']; ?>/<?php echo $estadisticas['citas']['totales']; ?>
                        </p>
                        <p class="text-sm text-gray-500">Completadas</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-calendar-check text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo $estadisticas['citas']['totales'] > 0 ? ($estadisticas['citas']['completadas'] / $estadisticas['citas']['totales'] * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Ingresos del Mes -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Ingresos Mes</p>
                        <p class="text-2xl font-bold text-gray-900">L <?php echo number_format($resumenFinanciero['total_pagado'] ?? 0, 0); ?></p>
                        <p class="text-sm text-gray-500">Pagado</p>
                    </div>
                    <div class="p-3 bg-orange-100 rounded-lg">
                        <i class="fas fa-money-bill-wave text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Columna Izquierda - Actividad Reciente y Horario -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Horario de Médicos Hoy -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-calendar-day mr-2 text-blue-600"></i>
                            Horario de Hoy - <?php echo date('d/m/Y'); ?>
                        </h2>
                        <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                            <?php echo count($citasPorHora); ?> citas
                        </span>
                    </div>
                    
                    <?php if (empty($citasPorHora)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-calendar-times text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No hay citas programadas para hoy</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach ($citasPorHora as $cita): ?>
                                <div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition duration-200">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-clock text-blue-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">
                                                <?php echo date('H:i', strtotime($cita['Hora_consulta'])); ?> - 
                                                <?php echo $cita['Paciente']; ?>
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                <i class="fas fa-user-md mr-1"></i>
                                                <?php echo $cita['Medico']; ?> - <?php echo $cita['Especialidad']; ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <i class="fas fa-door-closed mr-1"></i>
                                                Cubiculo <?php echo $cita['Cubiculo'] ?? 'N/A'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $cita['Estado_consulta'] === 'Confirmada' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <i class="fas fa-circle mr-1" style="font-size: 6px;"></i>
                                            <?php echo $cita['Estado_consulta']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Médicos con Citas Hoy -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-user-md mr-2 text-green-600"></i>
                            Médicos con Citas Hoy
                        </h2>
                        <span class="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full">
                            <?php echo count($medicosConCitas); ?> médicos
                        </span>
                    </div>
                    
                    <?php if (empty($medicosConCitas)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-user-md text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No hay médicos con citas hoy</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($medicosConCitas as $medico): ?>
                                <div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition duration-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user-md text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800"><?php echo $medico['Medico']; ?></p>
                                            <p class="text-sm text-gray-600"><?php echo $medico['Especialidad']; ?></p>
                                        </div>
                                    </div>
                                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-1 rounded-full">
                                        <?php echo $medico['Total_Citas']; ?> citas
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actividad Reciente -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Actividad Reciente</h2>
                        <a href="solicitudes_pendientes.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Ver todas</a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($actividades)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500">No hay actividad reciente</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($actividades as $actividad): ?>
                                <div class="flex items-start space-x-4 p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition duration-200">
                                    <div class="flex-shrink-0">
                                        <?php if ($actividad['tipo'] === 'nueva_solicitud'): ?>
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-calendar-plus text-blue-600"></i>
                                            </div>
                                        <?php elseif ($actividad['tipo'] === 'pago_recibido'): ?>
                                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-money-bill-wave text-green-600"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-check-circle text-purple-600"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <p class="text-gray-800 font-medium truncate"><?php echo $actividad['descripcion']; ?></p>
                                        <p class="text-gray-600 text-sm"><?php echo $actividad['especialidad']; ?></p>
                                        <p class="text-gray-500 text-xs mt-1">
                                            <?php
                                            $minutos = $actividad['minutos'];
                                            if ($minutos < 60) {
                                                echo "Hace $minutos min";
                                            } elseif ($minutos < 1440) {
                                                $horas = floor($minutos / 60);
                                                echo "Hace $horas hora" . ($horas > 1 ? 's' : '');
                                            } else {
                                                $dias = floor($minutos / 1440);
                                                echo "Hace $dias día" . ($dias > 1 ? 's' : '');
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $actividad['etiqueta'] === 'Nueva' ? 'bg-blue-100 text-blue-800' : 
                                                   ($actividad['etiqueta'] === 'Pago' ? 'bg-green-100 text-green-800' : 
                                                   'bg-purple-100 text-purple-800'); ?>">
                                            <?php echo $actividad['etiqueta']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha - Estadísticas y Acciones -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Resumen Financiero -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Resumen Financiero</h2>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-green-800">Total Pagado</p>
                                <p class="text-2xl font-bold text-green-900">
                                    L <?php echo number_format($resumenFinanciero['total_pagado'] ?? 0, 2); ?>
                                </p>
                            </div>
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        
                        <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-yellow-800">Pendiente</p>
                                <p class="text-2xl font-bold text-yellow-900">
                                    L <?php echo number_format($resumenFinanciero['total_pendiente'] ?? 0, 2); ?>
                                </p>
                            </div>
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        
                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-blue-800">Total Facturas</p>
                                <p class="text-2xl font-bold text-blue-900">
                                    <?php echo $resumenFinanciero['total_facturas'] ?? 0; ?>
                                </p>
                            </div>
                            <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Acciones Rápidas</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <a href="gestion_medicos.php" class="flex flex-col items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition duration-200">
                            <i class="fas fa-user-md text-blue-600 text-2xl mb-2"></i>
                            <span class="text-sm font-medium text-blue-800 text-center">Gestionar Médicos</span>
                        </a>
                        
                        <a href="citas_pendientes.php" class="flex flex-col items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition duration-200">
                            <i class="fas fa-calendar-check text-green-600 text-2xl mb-2"></i>
                            <span class="text-sm font-medium text-green-800 text-center">Ver Citas</span>
                        </a>
                        
                        <a href="pagos_validar.php" class="flex flex-col items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition duration-200">
                            <i class="fas fa-money-check text-purple-600 text-2xl mb-2"></i>
                            <span class="text-sm font-medium text-purple-800 text-center">Validar Pagos</span>
                        </a>
                        
                        <a href="solicitudes_pendientes.php" class="flex flex-col items-center p-4 bg-orange-50 hover:bg-orange-100 rounded-lg transition duration-200">
                            <i class="fas fa-clipboard-list text-orange-600 text-2xl mb-2"></i>
                            <span class="text-sm font-medium text-orange-800 text-center">Solicitudes</span>
                        </a>
                    </div>
                </div>

                <!-- Información del Sistema -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Información del Sistema</h2>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Fecha del Sistema:</span>
                            <span class="text-sm font-medium text-gray-800"><?php echo date('d/m/Y'); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Hora:</span>
                            <span class="text-sm font-medium text-gray-800" id="hora-actual"><?php echo date('H:i:s'); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Usuario:</span>
                            <span class="text-sm font-medium text-gray-800"><?php echo $_SESSION['user_nombre'] ?? 'Administrador'; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Rol:</span>
                            <span class="text-sm font-medium text-gray-800">Administrador</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <?php
                    if (file_exists($logoPath)) {
                        echo '<img src="' . $logoPath . '" alt="Policlínicas San Marcos" class="w-8 h-8 object-contain mr-3">';
                    } else {
                        echo '<div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm mr-3">PSM</div>';
                    }
                    ?>
                    <p class="text-sm text-gray-600">Policlínicas San Marcos &copy; <?php echo date('Y'); ?></p>
                </div>
                <p class="text-sm text-gray-600">Sistema de Gestión de Citas Médicas</p>
            </div>
        </div>
    </footer>

   