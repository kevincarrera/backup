#!/usr/bin/env bash
ruta="/home/maestro/backup/data/temp";
date=`/bin/date +%Y%m%d`
echo $ruta/reset_${date}.log;
if [ -f $ruta/reset_${date}.log  ]; then
echo "existe";

mv  $ruta/reset_${date}.log $ruta/reset_${date}_ok.log;
inicio=`date`;
echo  "se inicio el reinicio $inicio">>${ruta}/reiniciaOpenVpn.log;
 sudo service openvpn restart>>${ruta}/reiniciaOpenVpn.log;
fin=`date`;
echo  "acabo el reinicio $fin">>${ruta}/reiniciaOpenVpn.log;

else
echo "no existe";
fi;

