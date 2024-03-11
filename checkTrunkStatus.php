<?php
$logFile = "/var/log/checkTrunkStatus.log";
$fp = fopen($logFile, 'a');
$logMessage = "Ejecutando el script\n";
fwrite($fp, $logMessage);
// Función para obtener la lista de peers PJSIP
$output = shell_exec('asterisk -rx "pjsip show endpoints"');
$logMessage = "Antes de dormir muestro lo que consegui obtener del comando\n".var_dump($output)."\n";
fwrite($fp, $logMessage);
$logMessage = "Duermo...\n";
fwrite($fp, $logMessage);
sleep(50);
$logMessage = "DESPUES de dormir muestro lo que consegui obtener del comando\n".var_dump($output)."\n";
$lines = explode("\n", $output);
$hostname = gethostname();
$timestamp = time();
$peers = [];
//Este flag es para saber si miro o no miro el contact
$flag = 0;
$logMessage = "Entrando al loop\n";
fwrite($fp, $logMessage);
foreach ($lines as $line) {
	if (strpos($line, 'Endpoint:') !== false) {
		$parts = explode('  ', $line);
		$info = array_values(array_filter($parts));
		//var_dump($info)."\n";
		$peer = $info[1];
		$peer = trim($peer);
		$status = $info[2];
		$status = trim($status);
		if (strpos($peer, "/") !== false) {
			// Si contiene datos separados por "/", separar la cadena en partes
			$numeros = explode("/", $peer);

			$peer = $numeros[0];
		}

		if(!is_numeric($peer) and $peer != "<Endpoint/CID.....................................>"){
			if($status == "Unavailable"){
				echo "El peer ".$peer." se encuentra en estado: ".$status."\n";
				//Si paso por aca prendo el flag en 1 para revisar el contact
				$flag = 1;
				$timestamp = date("Y-m-d H:i:s");
				// Escribir un mensaje de inicio en el archivo de registro
				$logMessage = "$timestamp - El peer ".$peer." se encuentra en estado: ".$status."\n";
				fwrite($fp, $logMessage);
				// Datos para enviar en el JSON
				$data = array(
					"version"=> "1.1",
					"facility" => "asterisk",
					"host"=> $hostname,
					"short_message"=> "The trunk $peer is now Unavaliable",
					"timestamp"=> $timestamp,
        				"_trunk" => $peer,
        				"_availability" => "false"
				);
    				// Convertir los datos a formato JSON
    				$json_data = json_encode($data);

				// Dirección y puerto del servidor Graylog
    				$host = 'graylog-inceptia.centralus.cloudapp.azure.com';
    				//$port = 12501;
				$port = 12201;

    				// Crear un socket UDP
    				$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    				if ($socket === false) {
        				echo "Error al crear el socket: " . socket_strerror(socket_last_error()) . PHP_EOL;
						$timestamp = date("Y-m-d H:i:s");
						// Escribir un mensaje de inicio en el archivo de registro
						$logMessage = "$timestamp - Error al crear el socket: " . socket_strerror(socket_last_error()) . PHP_EOL."\n";
						fwrite($fp, $logMessage);
        				return;
    				}

    				// Enviar el JSON a través del socket UDP
    				$result = socket_sendto($socket, $json_data, strlen($json_data), 0, $host, $port);
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
    				socket_close($socket);

			}else{
				echo "El peer ".$peer." esta OK ".$status."\n";
				$timestamp = date("Y-m-d H:i:s");
				// Escribir un mensaje de inicio en el archivo de registro
				$logMessage = "$timestamp - El peer ".$peer." esta OK ".$status."\n";
				fwrite($fp, $logMessage);
			}
		}
	}
	if (strpos($line, 'Contact:') !== false and $flag == 1) {
		$parts = explode(' ', $line);
                $info = array_values(array_filter($parts));
                //var_dump($info)."\n";
                $contact = $info[1];
                $status = $info[3];
                if($peer != "<Endpoint/CID.....................................>"){
                        if($status != "OK"){
                                echo "El contact es: ".$contact." se encuentra en estado: ".$status."\n";
                        }else{
                                echo "El contact".$contact." esta OK\n";
                        }
                }
	}
	//Vuelvo a apagar el flag para el proximo peer
	$flag = 0;
}
fclose($fp);
?>
