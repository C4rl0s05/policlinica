<?php
// login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'components/logo.php';
require_once 'components/input.php';
require_once 'components/button.php';

$userType = $_GET['type'] ?? 'paciente';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($userType === 'paciente') {
        $identidad = $_POST['identidad'];
        if (validarPaciente($identidad)) {
            $_SESSION['user_type'] = 'paciente';
            $_SESSION['user_identidad'] = $identidad;
            header('Location: patient/dashboard.php');
            exit;
        } else {
            $error = "Número de identidad no encontrado";
        }
    } else {
        $nombreUsuario = $_POST['nombreUsuario'];
        $password = $_POST['password'];
        if (validarAdministrador($nombreUsuario, $password)) {
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_nombre'] = $nombreUsuario;
            header('Location: admin/dashboard.php');
            exit;
        } else {
            $error = "Credenciales incorrectas";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Policlínicas San Marcos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="login-container flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="login-card p-8">
            <!-- Logo con texto -->
            <?php echo renderLogo('large', true); ?>
            
            <!-- Selector de Tipo de Usuario -->
            <div class="flex bg-gray-100 rounded-lg p-1 mb-6">
                <a href="?type=paciente" 
                   class="flex-1 text-center py-3 rounded-md transition duration-200 <?php echo $userType === 'paciente' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-blue-600'; ?>">
                   <i class="fas fa-user-injured mr-2"></i>Paciente
                </a>
                <a href="?type=admin" 
                   class="flex-1 text-center py-3 rounded-md transition duration-200 <?php echo $userType === 'admin' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-blue-600'; ?>">
                   <i class="fas fa-user-shield mr-2"></i>Administrador
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <?php if ($userType === 'paciente'): ?>
                    <!-- Login Paciente -->
                    <div class="text-center mb-2">
                        <h2 class="text-xl font-semibold text-gray-800">Iniciar Sesión - Paciente</h2>
                        <p class="text-gray-600 text-sm">Ingresa tu número de identidad para acceder</p>
                    </div>
                    
                    <?php echo renderInput([
                        'label' => 'Número de Identidad',
                        'name' => 'identidad',
                        'placeholder' => '0801-1990-12345',
                        'required' => true,
                        'icon' => 'fas fa-id-card'
                    ]); ?>
                    
                <?php else: ?>
                    <!-- Login Administrador -->
                    <div class="text-center mb-2">
                        <h2 class="text-xl font-semibold text-gray-800">Iniciar Sesión - Administrador</h2>
                        <p class="text-gray-600 text-sm">Accede al panel de administración</p>
                    </div>
                    
                    <?php echo renderInput([
                        'label' => 'Nombre de Usuario',
                        'name' => 'nombreUsuario',
                        'placeholder' => 'admin1',
                        'required' => true,
                        'icon' => 'fas fa-user'
                    ]); ?>
                    
                    <?php echo renderInput([
                        'label' => 'Contraseña',
                        'name' => 'password',
                        'type' => 'password',
                        'placeholder' => '••••••••',
                        'required' => true,
                        'icon' => 'fas fa-lock'
                    ]); ?>
                    
                <?php endif; ?>
                
                <?php echo renderButton([
                    'text' => '<i class="fas fa-sign-in-alt mr-2"></i>Ingresar',
                    'type' => 'submit',
                    'classes' => 'mt-2'
                ]); ?>
            </form>
            
            <!-- Información de prueba -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-blue-800 text-center">
                    <strong><i class="fas fa-key mr-1"></i>Credenciales de prueba:</strong><br>
                    <span class="text-xs">
                        Usuario: <strong>admin1</strong> | 
                        Contraseña: <strong>admin1234</strong>
                    </span>
                </p>
            </div>
        </div>
        
        <!-- Footer del login -->
        <div class="text-center mt-6">
            <p class="text-white text-sm opacity-80">
                &copy; <?php echo date('Y'); ?> Policlínicas San Marcos
            </p>
        </div>
    </div>
</body>
</html>