<?php
// crear_admin_completo.php
require_once 'config/database.php';

echo "<h2>Configuración Completa del Sistema</h2>";

// Función para crear registro en Administracion si no existe
function crearRegistroAdministracion() {
    $conn = getDBConnection();
    
    // Verificar si ya existe un registro en Administracion
    $query_check = "SELECT COUNT(*) as total FROM Administracion";
    $stmt_check = sqlsrv_query($conn, $query_check);
    $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
    
    if ($row['total'] > 0) {
        return "✅ Ya existen registros en Administracion";
    }
    
    // Crear registro en Administracion
    $query = "INSERT INTO Administracion (Nombre, Apellido, Email, Telefono, Cargo) 
              VALUES (?, ?, ?, ?, ?)";
    
    $params = array(
        'Administrador', 
        'Principal', 
        'admin@clinicasanmarcos.com', 
        '+504 1234-5678', 
        'Super Administrador'
    );
    
    $stmt = sqlsrv_query($conn, $query, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        return "Error al crear registro en Administracion: " . $errors[0]['message'];
    }
    
    return "✅ Registro de Administracion creado exitosamente";
}

// Función para crear usuario admin
function crearUsuarioAdmin($nombreUsuario, $passwordPlano) {
    $conn = getDBConnection();
    
    // Primero obtener el Id_Administracion disponible
    $query_id = "SELECT TOP 1 Id_Administracion FROM Administracion ORDER BY Id_Administracion";
    $stmt_id = sqlsrv_query($conn, $query_id);
    
    if ($stmt_id === false || !sqlsrv_has_rows($stmt_id)) {
        return "Error: No hay registros en la tabla Administracion";
    }
    
    $row_id = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC);
    $id_administracion = $row_id['Id_Administracion'];
    
    // Verificar si el usuario ya existe
    $query_check = "SELECT COUNT(*) as total FROM Usuarios WHERE NombreUsuario = ?";
    $params_check = array($nombreUsuario);
    $stmt_check = sqlsrv_query($conn, $query_check, $params_check);
    $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
    
    if ($row_check['total'] > 0) {
        return "El usuario '$nombreUsuario' ya existe en la base de datos";
    }
    
    // Generar hash de la contraseña
    $hash = password_hash($passwordPlano, PASSWORD_DEFAULT);
    
    // Insertar usuario en la base de datos
    $query = "INSERT INTO Usuarios (NombreUsuario, Contraseña, Tipo_Usuario, Id_Administracion) 
              VALUES (?, ?, 'Administrador', ?)";
    
    $params = array($nombreUsuario, $hash, $id_administracion);
    $stmt = sqlsrv_query($conn, $query, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        return "Error al crear usuario: " . $errors[0]['message'];
    }
    
    return [
        'usuario' => $nombreUsuario,
        'password_plano' => $passwordPlano,
        'hash_generado' => $hash,
        'id_administracion' => $id_administracion,
        'longitud_hash' => strlen($hash),
        'mensaje' => '✅ Usuario creado exitosamente'
    ];
}

// Procesar el formulario
if ($_POST) {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear_administracion') {
        $resultado = crearRegistroAdministracion();
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>Resultado:</h3>";
        echo "<p>$resultado</p>";
        echo "</div>";
    }
    
    if ($accion === 'crear_usuario') {
        $nombreUsuario = $_POST['nombreUsuario'] ?? 'admin';
        $password = $_POST['password'] ?? 'password';
        
        $resultado = crearUsuarioAdmin($nombreUsuario, $password);
        
        if (is_array($resultado)) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>Usuario Creado Exitosamente:</h3>";
            echo "<p><strong>Usuario:</strong> {$resultado['usuario']}</p>";
            echo "<p><strong>Contraseña (plano):</strong> {$resultado['password_plano']}</p>";
            echo "<p><strong>Hash generado:</strong> <code style='background: #f8f9fa; padding: 5px; border-radius: 3px;'>{$resultado['hash_generado']}</code></p>";
            echo "<p><strong>ID Administracion:</strong> {$resultado['id_administracion']}</p>";
            echo "<p><strong>Longitud del hash:</strong> {$resultado['longitud_hash']} caracteres</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
            echo "<h3>Error:</h3>";
            echo "<p>$resultado</p>";
            echo "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Configuración Completa del Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        input, button, select { padding: 10px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; cursor: pointer; }
        button:hover { background: #0056b3; }
        .section { border-left: 4px solid #007bff; padding-left: 15px; margin: 20px 0; }
    </style>
</head>
<body>

<!-- Sección 1: Crear Registro en Administracion -->
<div class="section">
    <h3>Paso 1: Crear Registro en Tabla Administracion</h3>
    <p><em>Este paso es necesario antes de crear usuarios.</em></p>
    
    <div class="form-container">
        <form method="POST">
            <input type="hidden" name="accion" value="crear_administracion">
            <button type="submit">Crear Registro en Administracion</button>
        </form>
    </div>
</div>

<!-- Sección 2: Crear Usuario Admin -->
<div class="section">
    <h3>Paso 2: Crear Usuario Administrador</h3>
    
    <div class="form-container">
        <form method="POST">
            <input type="hidden" name="accion" value="crear_usuario">
            <div>
                <label>Nombre de Usuario:</label>
                <input type="text" name="nombreUsuario" value="admin" required>
            </div>
            <div>
                <label>Contraseña:</label>
                <input type="text" name="password" value="password" required>
            </div>
            <button type="submit">Crear Usuario Admin</button>
        </form>
    </div>
</div>

<?php
// Mostrar estado actual de las tablas
echo "<h3>Estado Actual de las Tablas:</h3>";

$conn = getDBConnection();

// Mostrar Administracion
echo "<h4>Tabla Administracion:</h4>";
$query_admin = "SELECT * FROM Administracion";
$stmt_admin = sqlsrv_query($conn, $query_admin);

if ($stmt_admin === false) {
    echo "Error al consultar Administracion";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #343a40; color: white;'>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Cargo</th>
          </tr>";
    
    while ($row = sqlsrv_fetch_array($stmt_admin, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>
                <td>{$row['Id_Administracion']}</td>
                <td>{$row['Nombre']}</td>
                <td>{$row['Apellido']}</td>
                <td>{$row['Email']}</td>
                <td>{$row['Telefono']}</td>
                <td>{$row['Cargo']}</td>
              </tr>";
    }
    echo "</table>";
}

// Mostrar Usuarios
echo "<h4>Tabla Usuarios:</h4>";
$query_usuarios = "SELECT * FROM Usuarios";
$stmt_usuarios = sqlsrv_query($conn, $query_usuarios);

if ($stmt_usuarios === false) {
    echo "Error al consultar Usuarios";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #343a40; color: white;'>
            <th>ID</th>
            <th>Usuario</th>
            <th>Contraseña (Hash)</th>
            <th>Tipo</th>
            <th>ID Admin</th>
          </tr>";
    
    while ($row = sqlsrv_fetch_array($stmt_usuarios, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>
                <td>{$row['Id_usuario']}</td>
                <td><strong>{$row['NombreUsuario']}</strong></td>
                <td style='font-family: monospace; font-size: 10px; word-break: break-all;'>
                    " . substr($row['Contraseńa'], 0, 50) . "...
                </td>
                <td>{$row['Tipo_Usuario']}</td>
                <td>{$row['Id_Administracion']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

<!-- Sección 3: Probar Login -->
<div class="section">
    <h3>Paso 3: Probar Login</h3>
    <p>Una vez creado el usuario, puedes probar el login en:</p>
    <p><a href="login.php" style="background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block;">Ir al Login</a></p>
    <p><strong>Credenciales de prueba:</strong></p>
    <ul>
        <li><strong>Usuario admin:</strong> admin</li>
        <li><strong>Contraseña:</strong> password</li>
    </ul>
</div>

</body>
</html>