<?php
$logFile = "/var/log/checkTrunkStatus.log";
$fp = fopen($logFile, 'a');
$logMessage = "Ejecutando el script\n";
fwrite($fp, $logMessage);
echo "Ejecutando el script\n";
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
echo "Ejecutando el comando PJSIP\n";
$logMessage = "Ejecutando el comando PJSIP\n";
fwrite($fp, $logMessage);
fputs($socket, "Action: PJSIPShowEndpoints\r\n" );
fputs($socket, "\r\nAction: Logoff\r\n\r\n" );
echo "Obtengo los resultados...\n";
$response = "";
while (!feof($socket)) {
    $response .= fgets($socket);
}
// Verificar si la respuesta contiene información de los peers
if (strpos($response, 'Response: Success') !== false) {
    // Dividir la respuesta en líneas
    $lines = explode("\r\n", $response);
    $flag = 0;
    // Procesar cada línea
    foreach ($lines as $line) {
        echo $flag."\n";
        //$line = trim($line);
        $cadena = explode(":",$line);
        $campo = @trim($cadena[0]);
        $value = @trim($cadena[1]);
        
        if($campo == "ObjectName"){
            
            if(!is_numeric($value)){
                $trunk = $value;
                $flag ++;
            }else{
                $flag = -1;
                echo "No es numerico, salgo\n";
                continue;
            }
        }
        if($campo == "Transport"){
            $Transport = $value;
            $flag ++;
        }
        
        if($campo == "DeviceState"){
            $status = $value;
            $flag ++;
        }
        
        if($campo == "ActiveChannels"){
            $canales= $value;
            echo "Canales previos: ".$canales."\n";
            if($canales == ""){
                $canales = 0;
                echo "Canales 0: ".$canales."\n";
            }
            echo "Canales : ".$canales."\n";
            $flag ++;
        }
        
        if($flag > 2){
            $timestamp = date("Y-m-d H:i:s");
            // Escribir un mensaje de inicio en el archivo de registro
            $logMessage = "$timestamp - Troncal: ".$trunk." esta en estado: ".$status." y tiene ".$canales." canales activos ." . PHP_EOL."\n";
            fwrite($fp, $logMessage);
            echo "Troncal: ".$trunk." esta en estado: ".$status." y tiene ".$canales." canales activos \n";
            $flag = 0;
        }
    }
} else {
    echo "Error al ejecutar el comando PJSIPShowEndpoints\n";
    $logMessage = "Error al ejecutar el comando PJSIPShowEndpoints\n";
    fwrite($fp, $logMessage);
}
fclose($fp);
?>