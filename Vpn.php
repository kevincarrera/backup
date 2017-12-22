<?php
include "Model/diccionarioOwncloud.inc.php";

/**
 * Class Vpn
 *
 */
class Vpn {

    /**
     * __construct
     *
     * Vpn constructor.
     * @param array $variables
     */
    public function __construct( $variables = array() )
    {
            //TODO;
    }

    /**
     * getFileVpn
     *
     */
    public function getFileVpn()
    {
        $file=FILE_LOG_VPN;
        $comantExtra= "| grep \"TLS: Initial packet from\"" ;
        exec("cat " .$file .$comantExtra,$outs,$rv);
        //var_dump("cat " .$file .$comantExtra);
        $i=0;
        $info=array();
        foreach ($outs as $out){
            /**
             * ip y  puerto
             */
            $data = strstr($out," TLS: Initial packet from [AF_INET]",true);
            if ($data) {
                /**
                para optener el ip y puerto
                 */
                $datas = explode(" ", $data);
                $inicoSesion = date('Y-m-d H:i:s',strtotime( $datas[0]. " ". $datas[1]. " ".$datas[2]. " ".$datas[3]. " ". $datas[4]));
                $ipPuerto = end($datas);
                list($ip,$puerto)= explode(":",$ipPuerto);


                $info[$i]["SESION_INICIADA"] = $inicoSesion;
                $ip = explode("/",$ip);
                $info[$i]["IP"] = end($ip);
                $info[$i]["PUERTO"] = $puerto;
                $sids= strstr($out," TLS: Initial packet from [AF_INET]".$info[$i]["IP"].":".$info[$i]["PUERTO"]);
                $sid= explode(", ",$sids);
                $info[$i]["SID"]=end($sid);
                $res=array();
                exec("cat ". $file ." | grep " ."\"".$ipPuerto."\"",$res[$i] );

                foreach ($res[$i] as $re){
                    /**
                     * para optener el client
                     **/
                    $clientes = strstr($re,$info[$i]["IP"].":".$info[$i]["PUERTO"]." PUSH: Received control message: 'PUSH_REQUEST'", true);
                    if ($clientes && empty($info[$i]["CLIENTE"])) {
                       // var_dump($clientes);
                        $partes = explode(" ", $clientes);
                        $clienteIp = end($partes);
                        //list($cliente,$ipPuertos) = explode("/",$clienteIp);
                        $info[$i]["CLIENTE"] = $clienteIp;
                        continue;

                    }

                    /**
                     *   para optenr el usuario
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
                        $cerrar = strstr($re, $info[$i]["CLIENTE"]  . $info[$i]["IP"].":".$info[$i]["PUERTO"] . " SIGTERM[soft,remote-exit] received, client-instance exiting", true);
                        $cerrar0 = strstr($re, $info[$i]["CLIENTE"] . $info[$i]["IP"].":".$info[$i]["PUERTO"] . " SIGUSR1[soft,ping-restart] received, client-instance restarting", true);

                        $dateCerrado = empty($cerrar)? (empty($cerrar0)? null:$cerrar0):$cerrar;
                        //var_dump($info[$i]["CLIENTE"] .  $info[$i]["IP"].":".$info[$i]["PUERTO"] . " SIGTERM[soft,remote-exit] received, client-instance exiting");
                       $date=null;
                        if (!empty($dateCerrado))
                       {
                           $date= date('Y-m-d H:i:s',strtotime($dateCerrado));
                           $info[$i]["SESION_CERRADA"]= $date;
                           continue;
                       }


                    }

                    $errors = strstr($re, $info[$i]["IP"].":".$info[$i]["PUERTO"] . " TLS Error: TLS handshake failed");
                    $errors0 = strstr($re, $info[$i]["IP"].":".$info[$i]["PUERTO"] . " PLUGIN_CALL: plugin function PLUGIN_AUTH_USER_PASS_VERIFY failed with status 1: /usr/lib/openvpn/openvpn-plugin-auth-pam.so");
                    if ($info[$i]['IP'].":".$info[$i]['PUERTO']==="181.176.88.179:34961") {
                    var_dump($errors,$errors0);
                    }
                    $erro = empty($errors)? (empty($errors0)? null:$errors0):$errors;
                    if ($erro && empty($info[$i]["ERROR"])) {
                        $info[$i]["ERROR"]=true;
                        $info[$i]["DETALLE_ERROR"]=$erro;
                        continue;
                    }
                }
            }
            if (empty($info[$i]["ERROR"])){
                $info[$i]["ERROR"]=false;
                $info[$i]["DETALLE_ERROR"]='';
            }
        $i++;
        }
        $this->setLogVpn($info);
    }

    /**
     * setLogVpn
     *
     * @param $infos
     */
    public function setLogVpn($infos){
        global $dbOwncloud;

        foreach ($infos as $info) {
            $ip =$info['IP'];
            $puerto = $info['PUERTO'];
            $dateStart = $info['SESION_INICIADA'];
            $existe = $this->getLogVpn($ip,$puerto,$dateStart);

            if (empty($info['ERROR'])){
                $error=0;
            } else {
                $error=1;
            }
            $detalle_error=$info["DETALLE_ERROR"];

            if (empty($existe->V_IP)) {
                $sql = "INSERT INTO tb_log_vpn (v_ip, v_port, d_start, d_exit, v_sid, v_user , v_client,v_error, v_detalle_error)
                VALUES ('$ip', '$puerto', '$dateStart', '$info[SESION_CERRADA]','$info[SID]', '$info[USUARIO]',
                '$info[CLIENTE]', '$error','$detalle_error')";
                $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar insertMaestro ");


            } else {
                if ($existe->D_EXIT == '0000-00-00 00:00:00' && $existe->V_ERROR == '0') {
                    $sqlUp = "UPDATE tb_log_vpn SET d_exit='$info[SESION_CERRADA]'
                              WHERE (v_ip='$ip') AND
                              (v_port='$puerto') AND
                              (d_start='$dateStart')";
                    $rs = $dbOwncloud->Execute($sqlUp) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar insertMaestroUP ");
                }
            }

        }

        }

    /**
     * getLogVpn
     *
     * @param $ip
     * @param $puerto
     * @param $dstar
     * @return mixed
     */
    function getLogVpn($ip, $puerto, $dstar) {
        global $dbOwncloud;
        $sql = "SELECT * FROM tb_log_vpn
                WHERE v_ip = '$ip' AND
                v_port= '$puerto' AND
                d_start='$dstar'
                ";
        $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar getLogVpn ");
        return $rs->FetchObject();
    }

    /**
     * resetVpn
     */
    function resetVpn (){

        $file=FILE_VPN_STATUS;
        exec("cat ".$file,$outs);
        $conectado='';
        foreach ($outs as $out){
            $conectados = strstr($out, "Common Name,Real Address,Bytes Received,Bytes Sent,Connected Since");
            if ($conectados){
                $inicio = true;
                continue;
            }
            $conectadoTerminado = strstr($out, "ROUTING TABLE");
            if ($conectadoTerminado){
                $fin = true;
                continue;
            }
            if (!empty($inicio) && empty($fin)) {
                $conectado .= $out."\n";
            }
        }
        $fileResetOk= "/home/maestro/backup/data/temp/"."reset_".date("Ymd")."_ok.log";
        if (empty($conectado) && !file_exists($fileResetOk)) {
            print_r("Se esta creando aplicacion para el reinicio de OpenVpn");
            $fileReset= "/home/maestro/backup/data/temp/"."reset_".date("Ymd").".log";
            touch($fileReset);
        } else{
            echo "El reinicio de openvpn ya se realizo por el dia hoy "."\n";
        }
        print_r($conectado);
    }

}
?>