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
$hostname = gethostname();

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
                        //echo "Es un trunk, sigo\n";
                        $trunk = $value;
                        $cantidad ++;
                    }else{
                        $flag = 2;
                        //echo "Es un peer, salgo\n";
                        continue;
                    }
                }else if($campo == "Contacts"){
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
                    if($status == "Unavailable"){
                        $timestamp = date("Y-m-d H:i:s");
                        // Escribir un mensaje de inicio en el archivo de registro
                        $logMessage = "$timestamp - El peer ".$trunk." se encuentra en estado: ".$status."\n";
                        fwrite($fp, $logMessage);
                        // Datos para enviar en el JSON
                        $data = array(
                                "version"=> "1.1",
                                "facility" => "asterisk",
                                "host"=> $hostname,
                                "short_message"=> "The trunk $trunk is now Unavaliable",
                                "timestamp"=> $timestamp,
                                "_trunk" => $trunk,
                                "_availability" => "false"
                        );
                        // Convertir los datos a formato JSON
                        $json_data = json_encode($data);

                        // Dirección y puerto del servidor Graylog
                        $host = 'graylog-inceptia.centralus.cloudapp.azure.com';
                        //$port = 12501;
                        $port = 12201;

                        // Crear un socket UDP
                        $conexion = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                        if ($conexion === false) {
                                echo "Error al crear el socket: " . socket_strerror(socket_last_error()) . PHP_EOL;
                                        $timestamp = date("Y-m-d H:i:s");
                                        // Escribir un mensaje de inicio en el archivo de registro
                                        $logMessage = "$timestamp - Error al crear el socket: " . socket_strerror(socket_last_error()) . PHP_EOL."\n";
                                        fwrite($fp, $logMessage);
                                return;
                        }

                        // Enviar el JSON a través del socket UDP
                        $result = socket_sendto($conexion, $json_data, strlen($json_data), 0, $host, $port);
                        if ($result === false) {
                                echo "Error al enviar el mensaje: " . socket_strerror(socket_last_error()) . PHP_EOL;
                                        $timestamp = date("Y-m-d H:i:s");
                                        // Escribir un mensaje de inicio en el archivo de registro
                                        $logMessage = "$timestamp - Error al enviar el mensaje: " . socket_strerror(socket_last_error()) . PHP_EOL."\n";
                                        fwrite($fp, $logMessage);
                        } else {
                                echo "Alerta enviada a Graylog para el troncal $peer via UDP." . PHP_EOL;
                                        $timestamp = date("Y-m-d H:i:s");
                                        // Escribir un mensaje de inicio en el archivo de registro
                                        $logMessage = "$timestamp - Alerta enviada a Graylog para el troncal $peer via UDP." . PHP_EOL."\n";
                                        fwrite($fp, $logMessage);
                        }

                        // Cerrar el socket
                        socket_close($conexion);
                    }
                    $logMessage = "Troncal: ".$trunk." Transport:".$Transport." Status: ".$status." Canales: ".$canales."\n";
                    fwrite($fp, $logMessage);
                    $cantidad = 0;
                    $flag = 0;
                }
            }else if($flag == 2){
                //echo "Es un peer, salgo\n";
                continue;
            } 
        }else{
            //echo "LINEA VACIA\n";
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