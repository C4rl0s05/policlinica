<?php
// verificar_usuarios.php
require_once 'config/database.php';

echo "<h2>Verificación y Corrección de Usuarios</h2>";

$conn = getDBConnection();

// Verificar estructura de la tabla Usuarios
echo "<h3>Estructura de la tabla Usuarios:</h3>";
$query_estructura = "
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'Usuarios'
";
$stmt_estructura = sqlsrv_query($conn, $query_estructura);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Columna</th><th>Tipo</th><th>Nulo</th></tr>";
while ($row = sqlsrv_fetch_array($stmt_estructura, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>
            <td>{$row['COLUMN_NAME']}</td>
            <td>{$row['DATA_TYPE']}</td>
            <td>{$row['IS_NULLABLE']}</td>
          </tr>";
}
echo "</table>";

// Mostrar usuarios existentes
echo "<h3>Usuarios existentes:</h3>";
$query_usuarios = "SELECT Id_usuario, NombreUsuario, Contraseńa, Tipo_Usuario FROM Usuarios";
$stmt_usuarios = sqlsrv_query($conn, $query_usuarios);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Usuario</th><th>Contraseña (Hash)</th><th>Tipo</th><th>Acción</th></tr>";

while ($row = sqlsrv_fetch_array($stmt_usuarios, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>
            <td>{$row['Id_usuario']}</td>
            <td><strong>{$row['NombreUsuario']}</strong></td>
            <td style='font-family: monospace; font-size: 10px; word-break: break-all;'>
                {$row['Contraseńa']}
            </td>
            <td>{$row['Tipo_Usuario']}</td>
            <td>
                <form method='POST' style='display: inline;'>
                    <input type='hidden' name='usuario_id' value='{$row['Id_usuario']}'>
                    <input type='password' name='nueva_password' placeholder='Nueva contraseña' required>
                    <button type='submit' name='reset_password'>Resetear</button>
                </form>
            </td>
          </tr>";
}
echo "</table>";

// Procesar reset de contraseña
if (isset($_POST['reset_password'])) {
    $usuario_id = $_POST['usuario_id'];
    $nueva_password = $_POST['nueva_password'];
    
    $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
    $query_update = "UPDATE Usuarios SET Contraseńa = ? WHERE Id_usuario = ?";
    $params = array($hash, $usuario_id);
    $stmt_update = sqlsrv_query($conn, $query_update, $params);
    
    if ($stmt_update) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px 0;'>";
        echo "✅ Contraseña actualizada exitosamente. Hash: $hash";
        echo "</div>";
        
        // Recargar la página para ver los cambios
        echo "<script>setTimeout(function() { location.reload(); }, 2000);</script>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0;'>";
        echo "❌ Error al actualizar contraseña: " . print_r(sqlsrv_errors(), true);
        echo "</div>";
    }
}

// Crear nuevo usuario admin si no existe
echo "<h3>Crear nuevo usuario administrador:</h3>";
?>
<form method="POST">
    <input type="text" name="nuevo_usuario" placeholder="Nombre de usuario" value="admin" required>
    <input type="password" name="nueva_contraseña" placeholder="Contraseña" value="admin123" required>
    <button type="submit" name="crear_usuario">Crear Usuario Admin</button>
</form>

<?php
if (isset($_POST['crear_usuario'])) {
    $nuevo_usuario = $_POST['nuevo_usuario'];
    $nueva_contraseña = $_POST['nueva_contraseña'];
    
    // Obtener Id_Administracion
    $query_id = "SELECT TOP 1 Id_Administracion FROM Administracion";
    $stmt_id = sqlsrv_query($conn, $query_id);
    $row_id = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC);
    $id_admin = $row_id['Id_Administracion'];
    
    $hash = password_hash($nueva_contraseña, PASSWORD_DEFAULT);
    
    $query_insert = "INSERT INTO Usuarios (NombreUsuario, Contraseńa, Tipo_Usuario, Id_Administracion) 
                     VALUES (?, ?, 'admin', ?)";
    $params = array($nuevo_usuario, $hash, $id_admin);
    $stmt_insert = sqlsrv_query($conn, $query_insert, $params);
    
    if ($stmt_insert) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px 0;'>";
        echo "✅ Usuario creado exitosamente: $nuevo_usuario / $nueva_contraseña";
        echo "</div>";
        echo "<script>setTimeout(function() { location.reload(); }, 2000);</script>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0;'>";
        echo "❌ Error al crear usuario: " . print_r(sqlsrv_errors(), true);
        echo "</div>";
    }
}
?>

<div style="margin-top: 30px; padding: 15px; background: #e9ecef;">
    <h3>Instrucciones:</h3>
    <ol>
        <li>Verifica que el campo de contraseña se llama <strong>Contraseńa</strong> (con 'ń')</li>
        <li>Si no hay usuarios, crea uno nuevo con el formulario arriba</li>
        <li>Si los usuarios existen pero no funcionan, resetea su contraseña</li>
        <li>Prueba el login con las nuevas credenciales</li>
    </ol>
    <p><strong>Credenciales recomendadas:</strong> admin / admin123</p>
</div>