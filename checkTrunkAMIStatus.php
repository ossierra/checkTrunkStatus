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
$socket = fsockopen($ami_host,"5038", $ami_port, $errstr, 10);
fputs($socket, "Action: Login\r\n");
fputs($socket, "UserName: $ami_username\r\n");
fputs($socket, "Secret: $ami_password\r\n\r\n");
if (!$socket) {
    echo "Error al conectar a AMI: $errstr ($errno)\n";
    $logMessage = "Error al conectar a AMI: $errstr ($errno)\n";
    fwrite($fp, $logMessage);
    exit(1);
}
echo "Me conecto al AMI\n";
$wrets=fgets($socket);

echo var_dump($wrets)."\n";
echo "Ejecutando el comando PJSIP\n";
fputs($socket, "Action: PJSIPShowEndpoint\r\n" );
fputs($socket, "Endpoint: kamailio-dev\r\n" );
echo "Duermo 15 segundos\n";
sleep(15);
$wrets=fgets($socket);
echo "Y el resultado es.... PJSIP\n";
echo var_dump($wrets)."\n";
exit;
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
?>