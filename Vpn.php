<?php
class Vpn {
    public function __construct( $variables = array() )
    {
            //TODO;
    }

    public function getFileVpn()
    {
        $file="/home/maestro/backup/openvpn.log ";
        $comantExtra= "| grep \"TLS: Initial packet from\"" ;
        exec("cat " .$file .$comantExtra,$outs,$rv);
        //var_dump("cat " .$file .$comantExtra);
        $i=0;
        $info=array();
        foreach ($outs as $out){
            //var_dump($out);
            /**
             * ip y  puerto
             */
            $data = strstr($out," TLS: Initial packet from [AF_INET]",true);
            if ($data) {
                /**
                para optener el ip y puerto
                 */
                //var_dump($data);
                $datas = explode(" ", $data);
                $inicoSesion = date('Y-m-d H:i:s',strtotime( $datas[0]. " ". $datas[1]. " ".$datas[2]. " ".$datas[3]. " ". $datas[4]));
                $ipPuerto = end($datas);
                list($ip,$puerto)= explode(":",$ipPuerto);


                //var_dump($ip,$puerto);
                $info[$i]["SESION_INICIADA"] = $inicoSesion;
                $info[$i]["IP"] = $ip;
                $info[$i]["PUERTO"] = $puerto;
                $sids= strstr($out," TLS: Initial packet from [AF_INET]".$info[$i]["IP"].":".$info[$i]["PUERTO"]);
                $sid= explode(", ",$sids);
                $info[$i]["SID"]=end($sid);
                exec("cat ". $file ." | grep " ."\"".$ipPuerto."\"",$res );
                //var_dump("cat ". $file ." | grep " ."\"".$ipPuerto."\"");

                foreach ($res as $re){
                 //  var_dump($res);
                    /**
                    para optener el client
                     **/
                    $clientes = strstr($re,$info[$i]["IP"].":".$info[$i]["PUERTO"]." PUSH: Received control message: 'PUSH_REQUEST'", true);
                   // var_dump($clientes);
                    if ($clientes && empty($info[$i]["CLIENTE"])) {
                       // var_dump($clientes);
                        $partes = explode(" ", $clientes);
                        $clienteIp = end($partes);
                        //list($cliente,$ipPuertos) = explode("/",$clienteIp);
                        $info[$i]["CLIENTE"] = $clienteIp;
                        continue;

                    }

                    /**
                    para optenr el usuario
                     **/
                    $usuarios = strstr($re, $info[$i]["IP"].":".$info[$i]["PUERTO"] ." TLS: Username/Password authentication succeeded for username ");
                    if ($usuarios && empty($info[$i]["USUARIO"])) {
                        //var_dump($usuarios);
                        $parte = explode(" ", $usuarios);
                        $usuario= str_replace("'","",end($parte));
                        //var_dump($usuario);
                        $info[$i]["USUARIO"]= $usuario;
                        //var_dump($info);
                        continue;
                    }

                    /**
                     * seccion terminada
                     */

                    if (!empty($info[$i]["CLIENTE"])) {
                        //SIGUSR1[soft,ping-restart] received, client-instance restarting
                        $cerrar = strstr($re, $info[$i]["CLIENTE"] . "/" . $info[$i]["IP"].":".$info[$i]["PUERTO"] . " SIGTERM[soft,remote-exit] received, client-instance exiting", true);
                        $cerrar0 = strstr($re, $info[$i]["CLIENTE"] . "/" . $info[$i]["IP"].":".$info[$i]["PUERTO"] . " SIGUSR1[soft,ping-restart] received, client-instance restarting", true);

                            $dateCerrado = empty($cerrar)? (empty($cerrar0)? null:$cerrar0):$cerrar;
                       $date=null;
                        if (!empty($dateCerrado))
                       {
                           $date= date('Y-m-d H:i:s',strtotime($dateCerrado));
                           $info[$i]["SESION_CERRADA"]= $date;
                           continue;
                       }


                    }
                    $errors = strstr($re, $info[$i]["IP"].":".$info[$i]["PUERTO"] . " TLS Error: TLS handshake failed");
                    if ($errors && empty($info[$i]["ERROR"])){
                        $info[$i]["ERROR"]=true;
                        continue;
                    }
                }
                //$info[$i]=array($inicoSesion,$cliente,$ip, $puerto, $usuario, $date);
            }

        $i++;
        }
        var_dump($info);
    }
}
?>