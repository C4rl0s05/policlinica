<?php
// test_login_corregido.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>✅ Test del Login Corregido</h2>";

// Probar la función corregida
function testLoginCorregido($nombreUsuario, $password) {
    $conn = getDBConnection();
    
    echo "<h3>Probando login para: $nombreUsuario</h3>";
    
    // Usar la consulta CORREGIDA
    $query = "SELECT Contraseña, NombreUsuario, Tipo_Usuario FROM Usuarios WHERE NombreUsuario = ? AND Tipo_Usuario = 'Administrador'";
    $params = array($nombreUsuario);
    $stmt = sqlsrv_query($conn, $query, $params);
    
    if ($stmt === false) {
        echo "<p style='color: red;'>❌ Error en consulta: " . print_r(sqlsrv_errors(), true) . "</p>";
        return false;
    }
    
    if (!sqlsrv_has_rows($stmt)) {
        echo "<p style='color: red;'>❌ Usuario '$nombreUsuario' no encontrado o no es Administrador</p>";
        return false;
    }
    
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    echo "<p><strong>Usuario:</strong> {$row['NombreUsuario']}</p>";
    echo "<p><strong>Tipo:</strong> {$row['Tipo_Usuario']}</p>";
    
    $password_valido = password_verify($password, $row['Contraseña']);
    echo "<p><strong>Contraseña válida:</strong> " . ($password_valido ? "✅ SÍ" : "❌ NO") . "</p>";
    
    if ($password_valido) {
        echo "<p style='color: green; font-weight: bold;'>🎉 ¡LOGIN EXITOSO!</p>";
        $_SESSION['user_type'] = 'admin';
        $_SESSION['user_nombre'] = $row['NombreUsuario'];
        return true;
    }
    
    return false;
}

// Probar con diferentes usuarios
$usuarios_prueba = [
    ['admin1', 'admin1234'],
    ['admin', 'admin123']
];

foreach ($usuarios_prueba as $usuario) {
    $resultado = testLoginCorregido($usuario[0], $usuario[1]);
    
    if ($resultado) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>✅ Credenciales funcionan:</h3>";
        echo "<p>Usuario: <strong>{$usuario[0]}</strong></p>";
        echo "<p>Contraseña: <strong>{$usuario[1]}</strong></p>";
        echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Ir al Login</a></p>";
        echo "</div>";
        break;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Login Corregido</title>
</head>
<body>
    <div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 5px;">
        <h3>Resumen de la corrección:</h3>
        <ul>
            <li>✅ <strong>Problema identificado:</strong> Tipo de usuario es 'Administrador' no 'Administrator'</li>
            <li>✅ <strong>Solución:</strong> Cambiar 'Administrator' por 'Administrador' en la consulta SQL</li>
            <li>✅ <strong>Usuario de prueba:</strong> admin1</li>
            <li>✅ <strong>Contraseña:</strong> admin1234</li>
        </ul>
    </div>
</body>
</html>