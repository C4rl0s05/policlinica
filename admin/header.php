<?php
// admin/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../components/logo.php';
?>
<!-- Header -->
<header class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center">
                <?php echo renderLogo('small'); ?>
                <div class="ml-3">
                    <h1 class="text-2xl font-bold text-gray-900">Panel de Administración</h1>
                    <p class="text-sm text-gray-600">Policlínicas San Marcos</p>
                </div>
            </div>
            <div class="flex items-center space-x-6">
                <div class="text-right">
                    <p class="text-sm text-gray-600">Bienvenido</p>
                    <p class="font-semibold text-gray-900"><?php echo $_SESSION['user_nombre'] ?? 'Administrador'; ?></p>
                </div>
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-200">
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
            <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 font-medium py-2">Dashboard</a>
            <a href="gestion_medicos.php" class="text-blue-600 border-b-2 border-blue-600 font-medium py-2">Médicos</a>
            <a href="citas_pendientes.php" class="text-gray-600 hover:text-blue-600 font-medium py-2">Citas</a>
            <a href="pagos_validar.php" class="text-gray-600 hover:text-blue-600 font-medium py-2">Pagos</a>
            <a href="solicitudes_pendientes.php" class="text-gray-600 hover:text-blue-600 font-medium py-2">Solicitudes</a>
            <a href="reportes.php" class="text-gray-600 hover:text-blue-600 font-medium py-2">Reportes</a>
        </div>
    </div>
</nav>