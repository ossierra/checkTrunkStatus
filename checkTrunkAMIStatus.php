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
echo "Ejecutando el comando PJSIP\n";
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
    
    // Procesar cada línea
    foreach ($lines as $line) {
        $flag = 0;
        //$line = trim($line);
        $cadena = explode(":",$line);
        $campo = @trim($cadena[0]);
        $value = @trim($cadena[1]);
        
        if($campo == "ObjectName"){
            if(!is_numeric($value)){
                $trunk = $value;
                $flag ++;
            }
            echo $campo." | ".$value." | ".$flag."\n";
        }
        if($flag > 0 and $campo == "Contacts"){
            $contact = $value;
            $flag ++;
        }
        
        if($flag > 0 and $campo == "DeviceState"){
            $status = $value;
            $flag ++;
            echo $campo." | ".$value." | ".$flag."\n";
        }
        
        if($flag > 1 and $campo == "ActiveChannels"){
            if($canales == ""){
                $canales = 0;
            }else{
                $canales= $value;
            }
            $flag ++;
            echo $campo." | ".$value." | ".$flag."\n";
        }
        
        if($flag > 2){
            echo "Troncal: ".$trunk." esta en estado: ".$status." y tiene ".$canales." canales activos \n";
        }
    }
} else {
    echo "Error al ejecutar el comando PJSIPShowEndpoints\n";
    $logMessage = "Error al ejecutar el comando PJSIPShowEndpoints\n";
    fwrite($fp, $logMessage);
}
fclose($fp);
?>