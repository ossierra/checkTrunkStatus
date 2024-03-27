<?php
$logFile = "/var/log/checkTrunkStatus.log";
$hostname = gethostname();
$timestamp = time();
$peers = [];
$fp = fopen($logFile, 'a');
$logMessage = "$timestamp : Ejecutando el comando\n";
fwrite($fp, $logMessage);
echo "Ejecutando el comando\n";

// Función para obtener la lista de peers PJSIP
exec('asterisk -rx "pjsip list endpoints"',$output);

$logMessage = "$timestamp : Entrando al loop\n";
echo "Entrando al loop\n";
fwrite($fp, $logMessage);
foreach ($output as $line) {
    if (strpos($line, 'Endpoint:') !== false) {
        $parts = explode('  ', $line);
        $info = array_values(array_filter($parts));
        $peer = $info[1];
        $peer = trim($peer);
        if (strpos($peer, "/") !== false) {
                $peer_part = explode('/', $peer);
                $peer = $peer_part[0];
        }
        $status = $info[2];
        $status = trim($status);
        $canales_part  = explode(" ",$info[3]);
        $canales = $canales_part[0];
        if (strpos($peer, "<Endpoint") !== false) {
                continue;
        }else if(!is_numeric($peer)){
            if($status == "Unavailable"){
                $logMessage = "$timestamp : ALARMA!!! El peer: ".$peer." esta en estado: ".$status."\n";
                fwrite($fp, $logMessage);
                echo "ALARMA!!! El peer: ".$peer." esta en estado: ".$status."\n";
                
                $data = array(
                    "version"=> "1.1",
                    "facility" => "asterisk",
                    "host"=> $hostname,
                    "short_message"=> "El troncal $peer esta Unavaliable",
                    "timestamp"=> $timestamp,
                    "_trunk" => $peer,
                    "_availability" => "false"
                );
                // Convertir los datos a formato JSON
                $json_data = json_encode($data);
            }else{
                $logMessage = "$timestamp : El peer: ".$peer." esta en estado: ".$status." y tiene ".$canales." canales activos\n";
                fwrite($fp, $logMessage);
                echo "El peer: ".$peer." esta en estado: ".$status." y tiene ".$canales." canales activos\n";
                
                $data = array(
                    "version"=> "1.1",
                    "facility" => "asterisk",
                    "host"=> $hostname,
                    "short_message"=> "El troncal: ".$peer." esta en estado <<".$status.">> y tiene ".$canales." canales activos",
                    "timestamp"=> $timestamp,
                    "_trunk" => $peer,
                    "_status" => $status,
                    "_channels" => $peer,
                    "_availability" => "$canales"
                );
                // Convertir los datos a formato JSON
                $json_data = json_encode($data);
            }
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
    }
}
fclose($fp);
?>