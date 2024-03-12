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
    // Procesar cada línea
    $flag = 0;
    $cantidad = 0;
    foreach ($lines as $line) {

        if($line != ""){
            //echo $line."\n";
            $cadena = explode(":",$line);
            $campo = @trim($cadena[0]);
            $value = @trim($cadena[1]);
            
            //Flag = 0, es nuevo, Flag = 1, a revisar,Flag = 2 trunk, Flag = 3 peer.
            if($flag == 0){
                if($line == "Event: EndpointList"){
                    $flag = 1;
                    continue;
                }else{
                    $flag = 0;
                    continue;
                }
            }else if($flag == 1){
                if($campo == "ObjectName"){
                    if(!is_numeric($value)){
                        echo "Es un trunk, sigo\n";
                        $trunk = $value;
                        $cantidad ++;
                    }else{
                        $flag = 2;
                        echo "Es un peer, salgo\n";
                        continue;
                    }
                }else if($campo == "Transport"){
                    $Transport = $value;
                    $cantidad ++;
                }else if($campo == "DeviceState"){
                    $status = $value;
                    $cantidad ++;
                }else if($campo == "ActiveChannels"){
                    echo $line."\n";
                    $canales= $value;
                    $cantidad ++;
                }
                
                //Si ya recopile las 4 variables del troncal reporto al graylog
                if($cantidad > 3){
                    echo "Troncal: ".$trunk." Transport:".$Transport." Status: ".$status." Canales: ".$canales."\n";
                    $cantidad = 0;
                    $flag = 0;
                }
            }else if($flag == 2){
                echo "Es un peer, salgo\n";
                continue;
            } 
        }else{
            echo "LINEA VACIA\n";
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