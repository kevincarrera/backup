<?php
/**
 * Created by PhpStorm.
 * User: sistema03
 * Date: 16/10/2017
 * Time: 11:39 AM
 */
include "Model/diccionario.inc.php";
include "Model/diccionarioOwncloud.inc.php";

class Maestro {

    public $config;

    public function __construct( $config = array() )
    {

        $this->config = array_merge( array(
            'video_path' => dirname( __FILE__ ) . '/input/',
            'output_path' => dirname( __FILE__ ) . '/output/',
            'tmp_path' => dirname( __FILE__ ) . '/tmp/',
            'log_path' => dirname( __FILE__ ) . '/tmp/log.txt',
            'ffmpeg_path' => 'ffmpeg',
            'session_id' => 'my',
            'lang' => 'en',
            'user_id' => '',
            'is_admin' => false,
            'user_dir' => '',
            'upload_allowed' => array('mp4','flv','avi','mpg','webm'),
            'out_video_formats' => array('mp4','flv','webm','ogv','mp3'),
            'access_permissions' => array( 'upload', 'delete_output_files', 'delete_input_files', 'create_video' ),
            'out_video_sizes' => array( 360, 480, 576, 720),
            'youtube_download' => array( 'quality' => 'medium', 'type' => 'mp4' ),
            'max_output_files_count' => false,
            'use_mp4box' => false,
            'use_mencoder' => false,
            'use_avidemux' => false,
            'use_ffmpeg_concat_filter' => false,
            'ffmpeg_string_arr' => array(
                'flv' => '-vcodec flv -s {resolution} -aspect {aspect} -b:v {quality} -acodec libmp3lame -b:a 64k',//libfaac | aac
                'mp4' => '-vcodec libx264 -s {resolution} -aspect {aspect} -b:v {quality} -acodec libmp3lame -b:a 64k',//mpeg4
                'webm' => '-vcodec libvpx -s {resolution} -aspect {aspect} -b:v {quality} -acodec libvorbis -b:a 64k',
                'ogv' => '-vcodec libtheora -s {resolution} -aspect {aspect} -b:v {quality} -acodec libvorbis -b:a 64k'
            )
        ), $config );

    }

    public function getMaestroUse($date)
    {
        $dateInicial = date("Ymd", (strtotime ("-15 day")));
        global $db;
        $sql = "SELECT * FROM mae_audiovisual
           WHERE fecha_pub < $date
           AND fecha_pub > $dateInicial
           AND ruta_origen <> ''
           GROUP BY ruta_origen
           ";
        $rs = $db->Execute($sql) or die ($db->ErrorMsg() . " Error al ejecutar getMaestroUse ");
        while ($o = $rs->FetchNextObject()) {
            $articulo[]= $o->RUTA_ORIGEN;
        }
        return $articulo;

    }

    function upDateMaestro($maestro) {
        global $dbOwncloud;
        $sql = "UPDATE tb_maestro SET i_prioridad='$maestro[prioridad]'
                WHERE (v_ruta_completa='$maestro[rutaCompleta]')";
        $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar upDateMaestro ");
    }

    function getFile($dir_path="/mnt/", $repetir=false , $limit= false) {
     $raiz= "/mnt/";
     $date = $hour =date("YmdHi", (strtotime ("-24 Hours")));
     $out = array();
     if (!$repetir) {
         $videos = $this->getMaestroUse(date("Ymd", (strtotime ("-24 Hours"))));
         foreach ($videos as $video) {
             $rutaVideo = explode("/", $video);
             switch ($rutaVideo[0]) {
                 case "primaria":
                     $origenes = array("mythtv4/", "mythtv5/", "mythtv3/");
                     break;
                 case "secundaria":
                     $origenes = array("mythtv7/", "mythtv8/", "mythtv6/");
                     break;
             }
             foreach ($origenes as $origene) {
                 $maestro = $raiz . $origene . $rutaVideo[1];

                 if (file_exists($maestro)) {
                     //insert en la base de datos de clip para backupear
                     $maestroExiste = $this->getMaestro(array(
                         'rutaCompleta'=>$maestro));
                     if (empty($maestroExiste->V_RUTA_COMPLETA)) {
                         $maestroEx = explode("/",$maestro);
                         $idMaestro= end($maestroEx);
                         $prioridadEx = explode("_",substr($idMaestro,0,17));
                         $lenString=strlen($prioridadEx[1]);
                         $prioridad = 1;
                         $time =date('Y-m-d h:i:s', time());
                        $duracion = $this->getDuration($maestro);

                         $data = array(
                             'rutaCompleta'=>$maestro,
                             'maestro'=>substr($idMaestro,0,15),
                             'fechaMaestro'=>$prioridadEx[1],
                             'prioridad'=>$prioridad,
                             'dateCreate'=> $time,
                             'size' => round((filesize($maestro) / 1024 / 1024), 2),
                             'duracion' => $duracion
                         );

                         if ($prioridadEx[1] < $date && $lenString> TAMANO_FILE_TEXT_VIDEO) {
                                 //si no existe
                                 $this->insertMaestro($data);
                             }
                         }
                     if ($maestroExiste->I_PRIORIDAD== 0 && $maestroExiste->V_PHASE== 0) {
                         $this->upDateMaestro(array('rutaCompleta'=>$maestro,'prioridad'=>1));
                     }
                     //$this->insertMaestro($rutaCompleta);
                     break;
                 }
             }
         }
     }
     if ($dh = opendir($dir_path)) {

         while (($file = readdir($dh)) !== false) {
             if( $file != "." && $file != ".." &&  $file != ".." &&  $file != "nasnetgear03" &&  $file != "disco_ssd"
                 &&  $file != "freenas" &&  $file != "imediahost" &&  $file != "custm" &&  $file != "serverclipping" &&  $file != "sc"
                 &&  $file != "nasnetgear02" &&  $file != "Backups" &&  $file != "freenas0" &&  $file != "kpiviejo"
                 &&  $file != "nasnetgear" &&  $file != "netgear" &&  $file != "serverbackup" &&  $file != "serverbackup2"
             ){
                 if (is_dir($dir_path . $file) && $file != "." ) {
                     //solo si el archivo es un directorio, distinto que "." y ".."
                     // echo "<br>Directorio: $dir_path.$file";
                     $filer = $this->getFile(  $dir_path . $file . "/",true);
                     $out = array_merge($out,$filer);
                 }  else {
                     $pos = strpos($dir_path . $file,EXTENSION_FORMATO_VIDEO_ORIGINAL);
                     if ($pos) {
                         $archivo = $dir_path . $file;
                         $trozos = explode(".", $archivo);
                         $extension = end($trozos);

                         if ($extension == "mpg") {
                             array_push($out, $dir_path . $file);
                         }
                     }
                 }
             }
         }
         closedir($dh);
     }
     array_merge($out);
     usort($out, function($a, $b) {
         return filemtime($a) < filemtime($b);
     });
     if( $limit !== false ) {
         $out = array_splice( $out, 0, $limit );
     }
     return $out;
}

    function insertMaestro($maestro){
        global $dbOwncloud;
        $sql = "INSERT INTO tb_maestro (v_ruta_completa, v_maestro, v_fecha_maestro, i_prioridad, v_phase, date_cr , i_peso,i_duracion)
                VALUES ('$maestro[rutaCompleta]', '$maestro[maestro]', '$maestro[fechaMaestro]', '$maestro[prioridad]',
                 '0', '$maestro[dateCreate]','$maestro[size]', '$maestro[duracion]')";
        $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar insertMaestro ");
    }

    function getMaestro($maestro) {
        global $dbOwncloud;
        $sql = "SELECT * FROM tb_maestro
                WHERE v_ruta_completa = '$maestro[rutaCompleta]'";
        $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar getMaestro ");
        return $rs->FetchObject();
    }

    function get() {
        $maestros = $this->getFile("/mnt/");
        $date = $hour =date("YmdHi", (strtotime ("-24 Hours")));
        foreach ($maestros as $maestro) {

            $maestroExiste = $this->getMaestro(array(
                                        'rutaCompleta'=>$maestro));

            if (empty($maestroExiste->V_RUTA_COMPLETA)) {
                $maestroEx = explode("/",$maestro);
                $idMaestro= end($maestroEx);
                $prioridadEx = explode("_",substr($idMaestro,0,17));
                if (empty($prioridadEx[1])){
                    continue;
                }
                $lenString=strlen($prioridadEx[1]);
                $prioridad = 0;
                $time =date('Y-m-d h:i:s', time());
                $duracion = $this->getDuration($maestro);
                $data = array(
                    'rutaCompleta'=>$maestro,
                    'maestro'=>substr($idMaestro,0,15),
                    'fechaMaestro'=>$prioridadEx[1],
                    'prioridad'=>$prioridad,
                    'dateCreate'=> $time,
                    'size' => round((filesize($maestro) / 1024 / 1024), 2),
                    'duracion' => $duracion
                );
                if ($prioridadEx[1] < $date && $lenString > TAMANO_FILE_TEXT_VIDEO) {
                    //si no existe
                    $this->insertMaestro($data);
                }
            }
        }
    }

    /**
     * getDuration
     *
     * @param string $video_file_path
     * @param string $content
     */
    public function getDuration( $video_file_path, $content = '' )
    {
        $duration = 0;
        if( $video_file_path && !$content ){
            $command = $this->config['ffmpeg_path'] . " -i \"{$video_file_path}\" 2>&1";
            $content = shell_exec( $command );
        }

        if( $content ){
            preg_match_all("/Duration: (.*?), start:/", $content, $matches);

            $rawDuration = $matches[1];
            if( is_array( $rawDuration ) && count( $rawDuration ) > 1 ){
                foreach( $rawDuration as $dur ){
                    $duration += $this->timeToSeconds( $dur );
                }
                $output= $duration;
            }else{
                $output = $this->timeToSeconds($rawDuration[0]);
            }
        }

        return $output;

    }

    /**
     * timeToSeconds
     *
     * @param string $time
     */
    public function timeToSeconds( $time )
    {
        $output = 0;
        $time_arr = explode(':',$time);
        $t = array(3600, 60, 1);

        foreach( $time_arr as $k => $tt ){
            $output += ( floatval( $tt ) * $t[$k] );
        }

        return $output;

    }

    /**
     * secondsToTime
     *
     * @param float $seconds
     */
    public function secondsToTime( $sec ){

        if( !is_float( $sec ) ) $seconds = floatval( $sec );
        $hours   = floor($sec / 3600);
        $minutes = floor(($sec - ($hours * 3600)) / 60);
        $seconds = $sec - ($hours * 3600) - ($minutes * 60);
        $seconds = floor( $seconds );

        if ( $hours < 10 ) { $hours   = "0".$hours; }
        if ( $minutes < 10 ) { $minutes = "0".$minutes; }
        if ( $seconds < 10 ) { $seconds = "0".$seconds; }
        $time = $hours.':'.$minutes.':'.$seconds;

        return $time;
    }

    public function backup()
    {
        $maestrosBackups = $this->getPhase(0);
        while ($maestro = $maestrosBackups->FetchNextObj()) {
            $dataMaestro[]= $maestro;
            if ($maestro->i_peso < PESO_MINIMO_VIDEO_ORIGINAL) {

                // no se realiza backup de estos clip se cambia el valor de i_phase = 8
                // tipo peso menor a lo permitido
                //i_backup 0
                $data= array("id"=>$maestro->i_mestro_id,"backup"=>NO_BACKUP,'phase'=>PHASE_INCIO, 'tipo'=>'PESO_MENOR_DE_LO_PERMITIDO');
                $this->upBackupPhasetipo($data);
                continue;
            }

            if ($maestro->i_peso > $maestro->i_duracion) {
                // no se realiza backup de estos clip se cambia el valor de i_phase = 9
                //tipo peso mayor a la duracion presenta desface
                //i_backup 0
                $data = array("id"=>$maestro->i_mestro_id,"backup"=>NO_BACKUP,'phase'=>PHASE_INCIO, 'tipo'=>'PESO_MAYOR_QUE_DURACION_DESFACE');
                $this->upBackupPhasetipo($data);
                continue;
            }

            $repetidoMaestro = $this->getReptidasVMaestro($maestro->v_maestro, true);
            //var_dump($repeitoMaestro['cantidad']->N); exit();
           // var_dump($repeitoMaestro['cantidad']->N);
            if($repetidoMaestro['cantidad']->N > 1) {
                //Analisis entre las repetidas
               // var_dump($repeitoMaestro['data']);
                while ($maestroRepetido = $repetidoMaestro['data']->FetchNextObj()) {
                    //buscara todas las repetidas de este maestro para analizarlo entre ellos

                    $susRepetidos = $this->getReptidasVMaestro($maestroRepetido->v_maestro);
                    $i=0;
                    while ($maestroSusRepetidos = $susRepetidos->FetchNextObj()) {
                       // $data[] = $maestroSusRepetidos;
                        if ($maestroSusRepetidos->v_phase !=0 ){
                            continue;
                        }
                        if ($maestroSusRepetidos->i_prioridad == 1 ) {
                            //se hace su backup
                            $data = array("id"=>$maestroSusRepetidos->i_mestro_id,"backup"=>DISPONIBLE_BACKUP,'phase'=>PHASE_PRIMER_FILTRO, 'tipo'=>'VIDEO_CON_PRIORIDAD_POR_SELECCION');
                            $this->upBackupPhasetipo($data);
                            $i++;
                            continue;
                        } else {
                            if ($i==0){
                                //el que dura más no ha sido usa para hacer clip
                                $data = array("id"=>$maestroSusRepetidos->i_mestro_id,"backup"=>DISPONIBLE_BACKUP,'phase'=>PHASE_PRIMER_FILTRO, 'tipo'=>'VIDEO_CON_MEJOR_CALIDAD_DE_LOS_REPETIDOS');
                            } else {
                                $data=array("id"=>$maestroSusRepetidos->i_mestro_id,"backup"=>NO_BACKUP,'phase'=>PHASE_PRIMER_FILTRO, 'tipo'=>'VIDEO_CON_MENOR_CALIDAD_DE_LOS_REPETIDOS');
                            }
                            $this->upBackupPhasetipo($data);
                        }
                        $i++;

                    }

                }

            } else {
                if ($maestro->v_phase !=0 ){
                    continue;
                }
                //video unico se tiene que hacer backup
                //tipo sin problemas
                //i_backup 1
                $data = array("id"=>$maestro->i_mestro_id,"backup"=>DISPONIBLE_BACKUP,'phase'=>PHASE_PRIMER_FILTRO, 'tipo'=>'SIN_DUPLICADOS');
                $this->upBackupPhasetipo($data);
            }
        }

        $this->ordenMaestro();
    }

    public function getReptidasVMaestro($vMaestro, $todos = false)
    {
        //TODO
        global $dbOwncloud;
        if ($todos){
            $sql = "SELECT COUNT(*) as n, v_maestro FROM tb_maestro
                    WHERE v_maestro='$vMaestro'
                    GROUP BY v_maestro
                    ORDER BY n DESC";
            $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar getReptidasVMaestro ");

            $sql2 = "SELECT * FROM tb_maestro
                    WHERE v_maestro='$vMaestro'
                    GROUP BY v_maestro
                    DESC";
            $rs2 = $dbOwncloud->Execute($sql2) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar getReptidasVMaestro ");


            return array("cantidad" => $rs->FetchObject(), "data"=>$rs2 );
        } else {
            $sql = "SELECT * FROM tb_maestro WHERE v_maestro = '$vMaestro' ORDER BY i_duracion DESC; ";
            $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar getReptidasVMaestro ");
            return $rs;
        }


    }
    public function getPhase($phase){
        global $dbOwncloud;
        $sql = "SELECT * FROM tb_maestro
                WHERE v_phase = '$phase'";
        $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar getPhase ");
        return $rs;
    }

    public function upBackupPhasetipo($data){
        //var_dump($data);
        global $dbOwncloud;
        $sql = "UPDATE tb_maestro SET v_phase='$data[phase]', i_backup='$data[backup]', i_tipo='$data[tipo]'
                WHERE (i_mestro_id='$data[id]')";
        $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar upBackupPhasetipo ");
    }

    public function ordenMaestro(){
        $mestroFechas = $this->getOrdenFechaMaestro(false);
        while ($mestroFecha = $mestroFechas->FetchNextObj()) {
            $maestroOrdens= $this->getOrdenFechaMaestro(true, $mestroFecha->video);
            $i=0;
            do {
                $maestroOrden = $maestroOrdens->FetchNextObj();
                if (empty($maestroOrden->i_mestro_id)){
                    continue;
                }

                $fechaUnix = strtotime($maestroOrden->v_fecha_maestro);
                $fechaUnixAdicional = $fechaUnix + $maestroOrden->i_duracion;
                $data[$i]= array("id"=> $maestroOrden->i_mestro_id,
                    "vFechaMaestro"=>$maestroOrden->v_fecha_maestro,
                    "fechaUnix"=>$fechaUnix,
                    "fechaDuracion"=>$fechaUnixAdicional,
                    "duracion"=>$maestroOrden->i_duracion,
                    "vMaestro"=>$maestroOrden->v_maestro);

                if ($i==0){
                    //primera fila que recien se analizara en la siguiente iteracion
                    $i++;
                } else {
                    //var_dump($data[$i-1]['vFechaMaestro'],$data[$i]['vFechaMaestro'] );exit();
                    if ($data[$i-1]['vFechaMaestro']==$data[$i]['vFechaMaestro']){
                        $up[] = array("id"=> $data[$i]['id'],
                                    "phase"=>PHASE_DUPLICADO_EXISTENTE,
                                    "vMaestro"=> $data[$i]['vMaestro'],
                                    "tiempo"=>'');
                        unset($data[$i]);
                        //$i--;
                        continue;
                    } else {
                       /* if ($i>2){
                            //var_dump($up);
                            continue;
                        }*/

                        if ( $data[$i-1]['fechaDuracion'] > $data[$i]['fechaUnix']){
                            $tiempo = ($data[$i]['fechaUnix'] - $data[$i-1]['fechaUnix']) + DURACION_ADICIONAL;
                            $up[] = array("id"=> $data[$i-1]['id'],
                                "phase"=>PHASE_SEGUNDO_FILTRO,
                                "vMaestro"=> $data[$i-1]['vMaestro'],
                                "tiempo"=>$tiempo);
                        } else {
                            $tiempo = $data[$i-1]['duracion'];
                            $up[] = array("id"=> $data[$i-1]['id'],
                                "phase"=>PHASE_SEGUNDO_FILTRO,
                                "vMaestro"=> $data[$i-1]['vMaestro'],
                                "tiempo"=>$tiempo);
                        }
                        $i++;
                    }
                }

            } while ($maestroOrden);
            $up[] = array("id"=> $data[$i-1]['id'],
                "phase"=>PHASE_SEGUNDO_FILTRO,
                "vMaestro"=> $data[$i-1]['vMaestro'],
                "tiempo"=>$data[$i-1]['duracion']);
        }
        $this->upTiempo($up);
    }

    public function upTiempo($datas){
        global $dbOwncloud;
        $i=0;
        //var_dump($datas);exit();
        foreach ($datas as $data)
        {
            $sql="UPDATE tb_maestro SET v_phase='$data[phase]', i_tiempo='$data[tiempo]'
                WHERE (i_mestro_id='$data[id]')";
            $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar upTiempo || ". $sql);
        $i++;
        }
    }

    public function getOrdenFechaMaestro($group=true, $maestro="") {

        global $dbOwncloud;
        if ($group){
        $sql = "SELECT * FROM tb_maestro
                WHERE v_maestro LIKE '%$maestro%' AND i_backup = '".DISPONIBLE_BACKUP."' AND v_phase='".PHASE_PRIMER_FILTRO."'
                ORDER BY `v_fecha_maestro`, i_prioridad DESC";
        $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar getOrdenFechaMaestro ");
            } else {
            $sql = "SELECT SUBSTRING(v_maestro,1,13) as video FROM `tb_maestro`
                    WHERE `i_backup` '".DISPONIBLE_BACKUP."' AND v_phase='".PHASE_PRIMER_FILTRO."'
                    GROUP BY video
                    ORDER BY `v_fecha_maestro` DESC";

            $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar getOrdenFechaMaestro ");
        }
        return $rs;
        }

    public function execute()
    {  $timeStampMinHour = exec('cat /var/www/html/backup/data/temp/backup.txt');

        if(empty($timeStampMinHour) || $timeStampMinHour <= MAX_BACKUP){
            $backups = $this->getBackup(LIMIT_EJECUCION);

            // var_dump($backups); exit();
          //  $j=1;
            while ($backup = $backups->FetchNextObj()) {

                if (file_exists($backup->v_ruta_completa)) {
                    $timeStampMinHour = exec('cat /var/www/html/backup/data/temp/backup.txt');
                    if(empty($timeStampMinHour) || $timeStampMinHour <= MAX_BACKUP){


                    $i = explode("/", $backup->v_ruta_completa);
                    $i = substr(end($i), 0, -4);
                        if (!file_exists(RUTA_BACKUP . $i. BACKUP_FORMATO) && !file_exists(RUTA_BACKUP_SECUNDARIO . $i . BACKUP_FORMATO)) {
                        //$i= substr($backup->v_ruta_completa,0,-3);
                        //var_dump($i);exit();
                        $data = array("ruta" => $backup->v_ruta_completa,
                            "tiempo" => $backup->i_tiempo,
                            "i" => $i
                        );
                        $mas = 1 + $timeStampMinHour;
                        //$ejecucion = new ejecutar($data);
                        //$ejecucion->start();
                        exec(COMANT_EJECUION . $backup->v_ruta_completa . " " . $backup->i_tiempo . " " . $i . " " . $backup->i_mestro_id . " 2>/dev/null >/dev/null &");
                        var_dump(COMANT_EJECUION . $backup->v_ruta_completa . " " . $backup->i_tiempo . " " . $i . " " . $backup->i_mestro_id . " 2>/dev/null >/dev/null &");
                        //exit();
                        $this->upEjecucionBackup(array('phase' => PHASE_ERROR, 'backup' => BACKUP_EN_PROCESO, 'id' => $backup->i_mestro_id));
                        exec("echo $mas > ". CONTROL_BACKUP);

                    }
                     else {
                        $this->upEjecucionBackup(array('phase' => PHASE_TERMINADO, 'backup' => BACKUP_TERMINADO_CON_EXSITO, 'id' => $backup->i_mestro_id));
                    }
                    } else {
                        exit();
                    }
                } else {
                    //video dejo de existir
                    echo "no existe";
                    $this->upEjecucionBackup(array('phase' => PHASE_EN_EJECUCION_NO_EXISTE, 'backup' => BACKUP_TERMINADO_CON_EXSITO, 'id' => $backup->i_mestro_id));
                }

            }
        } else {
            exit();
        }
    }

    public function run($rutaCompleta, $tiempo, $i, $id)
    {
        $temp = "/mnt/serverbackup/";
        //parent::run(); // TODO: Change the autogenerated stub
        $strComando = "/usr/bin/ffmpeg -i {$rutaCompleta} -r 15  -vcodec libx264   " .
            "-t {$tiempo} -b:v 700k -acodec libfdk_aac -ab 32k  -ar 44100 -s 720x480 -f mp4 -y {$temp}{$i}.mp4" .
            "  >>/tmp/{$i}_2.txt 2>>/tmp/{$i}_2.txt ";

        print $strComando . "\n";
        exec($strComando, $output, $rv);
        if ($rv) {
            //errores
            $this->upEjecucionBackup(array('phase'=>PHASE_TERMINADO,'backup'=>BACKUP_CON_ERROR,'id'=>$id));
            exit;
        } else {
            //sin errores
            $this->upEjecucionBackup(array('phase'=>PHASE_TERMINADO,'backup'=>BACKUP_TERMINADO_CON_EXSITO,'id'=>$id));
        }
        $timeStampMinHour = exec('cat '. CONTROL_BACKUP);
        $menos = $timeStampMinHour-1;
        exec("echo $menos > ". CONTROL_BACKUP);

    }
    public function upEjecucionBackup($data){
        global $dbOwncloud;
        $sql = "UPDATE tb_maestro SET v_phase = $data[phase], i_backup = '$data[backup]'
                WHERE i_mestro_id='$data[id]'";
        $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar upEjecucionBackup ");
    }

    public function getBackup($rango) {
        global $dbOwncloud;
        $sql = "SELECT * FROM tb_maestro
                WHERE i_backup = '".DISPONIBLE_BACKUP."' AND v_phase='".PHASE_SEGUNDO_FILTRO."'
                AND i_tiempo <> ''
                ORDER BY i_tiempo LIMIT 0, $rango";
        $rs = $dbOwncloud->Execute($sql) or die ($dbOwncloud->ErrorMsg() . " Error al ejecutar getOrdenFechaMaestro ");
    return $rs;
    }

}



//$a = new Maestro();
//$a->get();
//$a->backup();
//$a->ordenMaestro();
