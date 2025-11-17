<?php
// config/database.php
// REMOVEMOS session_start() de aquí

// Configuración para SQL Server
define('DB_SERVER', 'CARLOS-ARITA');
define('DB_DATABASE', 'PoliclinicaSM');
define('DB_USERNAME', 'sa');
define('DB_PASSWORD', 'HOLLISTER2025');

function getDBConnection() {
    try {
        $connectionInfo = array(
            "Database" => DB_DATABASE,
            "UID" => DB_USERNAME,
            "PWD" => DB_PASSWORD,
            "CharacterSet" => "UTF-8",
            "ReturnDatesAsStrings" => true
        );
        
        $conn = sqlsrv_connect(DB_SERVER, $connectionInfo);
        
        if ($conn === false) {
            $errors = sqlsrv_errors();
            throw new Exception("Error de conexión SQL Server: " . $errors[0]['message']);
        }
        
        return $conn;
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

function validarPaciente($identidad) {
    $conn = getDBConnection();
    $query = "SELECT Id_paciente FROM Pacientes WHERE Num_identidad = ?";
    $params = array($identidad);
    $stmt = sqlsrv_query($conn, $query, $params);
    
    if ($stmt === false) {
        return false;
    }
    
    return sqlsrv_has_rows($stmt);
}

function validarAdministrador($nombreUsuario, $password) {
    $conn = getDBConnection();
    
    // CORREGIDO: Usar 'Administrador' en lugar de 'Administrator'
    $query = "SELECT Contraseña, NombreUsuario FROM Usuarios WHERE NombreUsuario = ? AND Tipo_Usuario = 'Administrador'";
    $params = array($nombreUsuario);
    $stmt = sqlsrv_query($conn, $query, $params);
    
    if ($stmt === false) {
        return false;
    }
    
    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Verificar contraseña
        if (password_verify($password, $row['Contraseña'])) {
            $_SESSION['user_nombre'] = $row['NombreUsuario'];
            return true;
        }
    }
    
    return false;
}
?>