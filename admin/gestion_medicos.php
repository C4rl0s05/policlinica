<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Médicos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>
    <div class="max-w-7xl mx-auto py-6 px-4">
        <h1 class="text-2xl font-bold">Gestión de Médicos</h1>
        <p class="text-gray-600">Página en construcción</p>
    </div>
</body>
</html>