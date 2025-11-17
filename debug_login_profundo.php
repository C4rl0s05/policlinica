<?php
// debug_login_profundo.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>🔍 Depuración Profunda del Login</h2>";

// Función para ver EXACTAMENTE qué hay en la base de datos
function debugProfundo($nombreUsuario, $password) {
    $conn = getDBConnection();
    
    echo "<div style='background: #fff3cd; padding: 20px; border: 2px solid #ffeaa7; margin: 15px 0; border-radius: 8px;'>";
    echo "<h3>🔎 ANALIZANDO USUARIO: $nombreUsuario</h3>";
    
    // 1. Ver TODOS los usuarios en la base de datos
    echo "<h4>1. TODOS los usuarios en la BD:</h4>";
    $query_all = "SELECT Id_usuario, NombreUsuario, Contraseña, Tipo_Usuario FROM Usuarios";
    $stmt_all = sqlsrv_query($conn, $query_all);
    
    if ($stmt_all === false) {
        echo "<p style='color: red;'>❌ Error al consultar usuarios: " . print_r(sqlsrv_errors(), true) . "</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #343a40; color: white;'>
                <th>ID</th>
                <th>Usuario</th>
                <th>Contraseña (Hash)</th>
                <th>Tipo</th>
                <th>¿Coincide?</th>
              </tr>";
        
        while ($row = sqlsrv_fetch_array($stmt_all, SQLSRV_FETCH_ASSOC)) {
            $coincide = ($row['NombreUsuario'] === $nombreUsuario) ? "✅ SÍ" : "❌ NO";
            $fondo = ($row['NombreUsuario'] === $nombreUsuario) ? 'background: #d4edda;' : '';
            
            echo "<tr style='$fondo'>
                    <td>{$row['Id_usuario']}</td>
                    <td><strong>{$row['NombreUsuario']}</strong></td>
                    <td style='font-family: monospace; font-size: 10px; word-break: break-all;'>
                        {$row['Contraseña']}
                    </td>
                    <td>{$row['Tipo_Usuario']}</td>
                    <td><strong>$coincide</strong></td>
                  </tr>";
        }
        echo "</table>";
    }
    
    // 2. Buscar específicamente el usuario
    echo "<h4>2. Búsqueda específica del usuario '$nombreUsuario':</h4>";
    $query_specific = "SELECT * FROM Usuarios WHERE NombreUsuario = ?";
    $params = array($nombreUsuario);
    $stmt_specific = sqlsrv_query($conn, $query_specific, $params);
    
    if ($stmt_specific === false) {
        echo "<p style='color: red;'>❌ Error en consulta específica: " . print_r(sqlsrv_errors(), true) . "</p>";
    } elseif (!sqlsrv_has_rows($stmt_specific)) {
        echo "<p style='color: red;'>❌ Usuario '$nombreUsuario' NO EXISTE en la base de datos</p>";
    } else {
        $user = sqlsrv_fetch_array($stmt_specific, SQLSRV_FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Usuario encontrado</p>";
        
        echo "<h5>Detalles del usuario:</h5>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        foreach ($user as $campo => $valor) {
            echo "<tr>
                    <td><strong>$campo</strong></td>
                    <td>$valor</td>
                    <td>" . (is_string($valor) ? strlen($valor) . " chars" : "") . "</td>
                  </tr>";
        }
        echo "</table>";
        
        // 3. Verificar la contraseña
        echo "<h4>3. Verificación de contraseña:</h4>";
        $hash = $user['Contraseña'];
        $password_valido = password_verify($password, $hash);
        
        echo "<p><strong>Contraseña ingresada:</strong> '$password'</p>";
        echo "<p><strong>Hash en BD:</strong> <code style='background: #f8f9fa; padding: 5px;'>$hash</code></p>";
        echo "<p><strong>Resultado password_verify:</strong> " . 
             ($password_valido ? "✅ <span style='color: green;'>VÁLIDA</span>" : "❌ <span style='color: red;'>INVÁLIDA</span>") . "</p>";
        
        if (!$password_valido) {
            echo "<h5>🔧 Probando diferentes contraseñas:</h5>";
            $passwords_comunes = [
                'admin1234', 'admin123', 'password', 'admin', 
                'Admin123', 'Admin1234', 'administrador', '123456'
            ];
            
            foreach ($passwords_comunes as $pwd_test) {
                $test_valido = password_verify($pwd_test, $hash);
                echo "<p>Contraseña '<strong>$pwd_test</strong>': " . 
                     ($test_valido ? "✅ <span style='color: green;'>VÁLIDA</span>" : "❌ inválida") . "</p>";
                
                if ($test_valido) {
                    echo "<p style='color: green; font-weight: bold;'>🎉 ¡CONTRASEÑA ENCONTRADA! Es: <strong>$pwd_test</strong></p>";
                    break;
                }
            }
            
            // Generar nuevo hash para comparar
            echo "<h5>🔧 Generando nuevo hash para comparación:</h5>";
            $nuevo_hash = password_hash($password, PASSWORD_DEFAULT);
            echo "<p><strong>Nuevo hash generado para '$password':</strong> <code>$nuevo_hash</code></p>";
            echo "<p><strong>¿Coincide con el de la BD?</strong> " . ($hash === $nuevo_hash ? "✅ SÍ" : "❌ NO") . "</p>";
        }
        
        // 4. Verificar tipo de usuario
        echo "<h4>4. Verificación de tipo de usuario:</h4>";
        $tipo_usuario = $user['Tipo_Usuario'];
        $es_administrador = ($tipo_usuario === 'Administrator' || $tipo_usuario === 'admin');
        
        echo "<p><strong>Tipo de usuario en BD:</strong> $tipo_usuario</p>";
        echo "<p><strong>¿Es administrador?</strong> " . ($es_administrador ? "✅ SÍ" : "❌ NO") . "</p>";
        
        if (!$es_administrador) {
            echo "<p style='color: orange;'>⚠️ El usuario no tiene permisos de administrador</p>";
        }
        
        // 5. Resultado final
        echo "<h4>5. RESULTADO FINAL DEL LOGIN:</h4>";
        if ($password_valido && $es_administrador) {
            echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 ¡LOGIN DEBERÍA FUNCIONAR!</p>";
            echo "<p>Usuario: <strong>$nombreUsuario</strong></p>";
            echo "<p>Contraseña: <strong>$password</strong></p>";
            echo "<p>Tipo: <strong>$tipo_usuario</strong></p>";
        } else {
            echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ LOGIN FALLIDO</p>";
            echo "<p>Problemas:</p>";
            echo "<ul>";
            if (!$password_valido) echo "<li>Contraseña incorrecta</li>";
            if (!$es_administrador) echo "<li>No es administrador</li>";
            echo "</ul>";
        }
    }
    
    echo "</div>";
}

// Procesar el formulario
if ($_POST) {
    $nombreUsuario = $_POST['nombreUsuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    debugProfundo($nombreUsuario, $password);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Profundo Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .form-container { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0; }
        input, button { padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; }
        input { width: 300px; }
        button { background: #007bff; color: white; cursor: pointer; border: none; }
        button:hover { background: #0056b3; }
        .info-box { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 6px; margin: 15px 0; }
    </style>
</head>
<body>

<div class="form-container">
    <h3>🔐 Probar Login con Depuración Profunda</h3>
    <div class="info-box">
        <strong>Este debugger mostrará TODA la información de la base de datos</strong>
    </div>
    
    <form method="POST">
        <div>
            <label><strong>Nombre de Usuario:</strong></label><br>
            <input type="text" name="nombreUsuario" value="admin1" required>
        </div>
        <div>
            <label><strong>Contraseña:</strong></label><br>
            <input type="password" name="password" value="admin1234" required>
        </div>
        <button type="submit">🔍 Ejecutar Depuración Profunda</button>
    </form>
</div>

<?php
// Script para resetear la contraseña si es necesario
if ($_POST && isset($_POST['reset_password'])) {
    $usuario_reset = $_POST['usuario_reset'];
    $nueva_password = $_POST['nueva_password'];
    
    $conn = getDBConnection();
    $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
    $query_update = "UPDATE Usuarios SET Contraseña = ? WHERE NombreUsuario = ?";
    $params = array($hash, $usuario_reset);
    $stmt_update = sqlsrv_query($conn, $query_update, $params);
    
    if ($stmt_update) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>✅ Contraseña actualizada</h3>";
        echo "<p>Usuario: <strong>$usuario_reset</strong></p>";
        echo "<p>Nueva contraseña: <strong>$nueva_password</strong></p>";
        echo "<p>Nuevo hash: <code>$hash</code></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>❌ Error al actualizar contraseña</h3>";
        echo "<p>" . print_r(sqlsrv_errors(), true) . "</p>";
        echo "</div>";
    }
}
?>

<div class="form-container">
    <h3>🔄 Resetear Contraseña (Si es necesario)</h3>
    <form method="POST">
        <div>
            <label>Usuario a resetear:</label><br>
            <input type="text" name="usuario_reset" value="admin1" required>
        </div>
        <div>
            <label>Nueva contraseña:</label><br>
            <input type="text" name="nueva_password" value="admin123" required>
        </div>
        <button type="submit" name="reset_password">🔄 Resetear Contraseña</button>
    </form>
</div>

</body>
</html>