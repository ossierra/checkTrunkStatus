<?php
$logFile = "/var/log/checkTrunkStatus.log";
$fp = fopen($logFile, 'a');
$logMessage = "Ejecutando el script\n";
echo "Ejecutando el script\n";
fwrite($fp, $logMessage);

// Datos de conexión AMI
$ami_host = '127.0.0.1';
$ami_port = '5038';
$ami_username = 'inceptia';
$ami_password = 'jdK8mYqRMFwbuTXp5t';

// Crear el socket de conexión AMI
$socket = fsockopen($ami_host, $ami_port, $errno, $errstr, 10);
if (!$socket) {
    echo "Error al conectar a AMI: $errstr ($errno)\n";
    $logMessage = "Error al conectar a AMI: $errstr ($errno)\n";
    fwrite($fp, $logMessage);
    exit(1);
}

// Iniciar sesión AMI
ami_login($socket, $ami_username, $ami_password);

// Ejecutar comando AMI para obtener información de los peers
$response = ami_command($socket, 'Action: PJSIPShowContacts');

// Verificar si la respuesta contiene información de los peers
if (strpos($response, 'Response: Success') !== false) {
    // Dividir la respuesta en líneas
    $lines = explode("\r\n", $response);
    // Procesar cada línea
    foreach ($lines as $line) {
        // Extraer información del peer de la línea
        // Aquí debes analizar la estructura de la respuesta de PJSIPShowEndpoints
        // y extraer la información del peer, como su nombre y estado
        // Luego, puedes aplicar la lógica de tu script para procesar esta información
    }
} else {
    echo "Error al ejecutar el comando PJSIPShowEndpoints\n";
    $logMessage = "Error al ejecutar el comando PJSIPShowEndpoints\n";
    fwrite($fp, $logMessage);
}

// Cerrar el socket de conexión AMI
ami_logout($socket);
fclose($fp);

// Función para iniciar sesión en AMI
function ami_login($socket, $username, $password) {
    // Enviar comando de inicio de sesión AMI
    $login_command = "Action: Login\r\nUsername: $username\r\nSecret: $password\r\n\r\n";
    fputs($socket, $login_command);
    // Leer y descartar la respuesta
    $response = fgets($socket);
    while ($response != "\r\n") {
        $response = fgets($socket);
    }
}

// Función para ejecutar un comando AMI
function ami_command($socket, $command) {
    // Enviar comando AMI
    $command .= "\r\nAction: Logoff\r\n\r\n";
    fputs($socket, $command);
    // Leer y retornar la respuesta
    $response = '';
    while (!feof($socket)) {
        $response .= fgets($socket);
    }
    return $response;
}

// Función para cerrar sesión en AMI
function ami_logout($socket) {
    // Enviar comando de cierre de sesión AMI
    fputs($socket, "Action: Logoff\r\n\r\n");
    // Cerrar el socket
    fclose($socket);
}
?>