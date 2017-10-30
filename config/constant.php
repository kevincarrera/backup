<?php
/**
 * Created by PhpStorm.
 * User: sistema03
 * Date: 30/10/2017
 * Time: 11:56 AM
 */
define("PHASE_ERROR", 4);
define("PHASE_INCIO", 1);
define("PHASE_PRIMER_FILTRO", 2);
define("PHASE_SEGUNDO_FILTRO", 3);
define("PHASE_TERMINADO", 5);
define("PHASE_EN_EJECUCION_NO_EXISTE", 7);
define("PHASE_DUPLICADO_EXISTENTE", 10);
define("EXTENSION_FORMATO_VIDEO_ORIGINAL", ".mpg");
define("DURACION_ADICIONAL", 300);
define("MAX_BACKUP", 10);
define("LIMIT_EJECUCION", 50);
define("RUTA_BACKUP", "/mnt/serverbackup/");
define("RUTA_BACKUP_SECUNDARIO", "/mnt/serverbackup2/");
define("BACKUP_FORMATO", ".mp4");
define("COMANT_EJECUION", "cd /var/www/html/backup; ./do.php ejecutar ");
define("CONTROL_BACKUP","/var/www/html/backup/data/temp/backup.txt");
define("TAMANO_FILE_TEXT_VIDEO",11);
define("PESO_MINIMO_VIDEO_ORIGINAL",750);
define("NO_BACKUP",0);
define("DISPONIBLE_BACKUP",1);
define("BACKUP_EN_PROCESO",2);
define("BACKUP_TERMINADO_CON_EXSITO",3);
define("BACKUP_CON_ERROR",4);