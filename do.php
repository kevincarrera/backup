#!/usr/bin/php

<?php

include "useMaestro.php";
switch (count($argv)) {
    case 6:
            $action = $argv[1];
            $ruta = $argv[2];
            $tiempo = $argv[3];
            $i = $argv[4];
            $id = $argv[5];
            switch ($action) {
                case 'ejecutar':
                    $feedObj = new Maestro();
                    $feedObj->run($ruta,$tiempo, $i, $id);
                    break;
            }

        break;
    case 2:
        $action = $argv[1];
        switch ($action) {
            case 'execute':
                $feedObj = new Maestro();
                $feedObj->execute();
                break;
            case 'get':
                //primir proceso
                $feedObj = new Maestro();
                $feedObj->get();
                break;
            default:
                echo "parametro incorecto";
                break;
        }
        break;
    default:
        echo "error";
        break;
}

