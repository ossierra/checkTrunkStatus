# checkTrunkStatus
Ubicacion sugerida para el script: /home/inceptia/scripts/checkTrunkStatus

INSTALACION:
1) mkdir scripts (Si no existe dentro del home)
2) git clone https://github.com/ossierra/checkTrunkStatus.git
3) cd checkTrunkStatus
4) Copiar el contenido del archivo manager_custom.conf en /etc/asterisk/manager_conf. Ojo con reemplazar el archivo, puede que ya tenga un usuario de otra app, solo copiar el contenido.
5) ejecutar asterisk -rx "manager reload"
6) ejecutar asterisk -rx "manager show users" y ver si figura el user inceptia
7) editar el crontab con "crontab -e" y agregar la linea "*/3 * * * * /usr/bin/php -q /home/inceptia/scripts/checkTrunkStatus/checkTrunkAMIStatus.php  > /dev/null".

El script escribe un log de actividad en "/var/log/checkTrunkStatus.log" para ver si esta corriendo se puede hacer un "tail -f /var/log/checkTrunkStatus.log" y ver si se obtienen los datos de los troncales
