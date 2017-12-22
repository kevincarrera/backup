#!/usr/bin/php

<?php

include "useMaestro.php";
include "Vpn.php";
include "config/constant.php";
//var_dump(count($argv));exit();
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
    case 7:
        $action = $argv[1];
        $ruta = $argv[2];
        $tiempo = $argv[3];
        $i = $argv[4];
        $id = $argv[5];
        $format = $argv[6];
        switch ($action) {
            case 'ejecutar':
                $feedObj = new Maestro();
                $feedObj->run($ruta,$tiempo, $i, $id, $format);
                break;
            default:
                echo "parametro incorecto";
                break;
        }

        break;
    case 2:
        $action = $argv[1];
        switch ($action) {
            case 'execute':
                //segundo proceso
                $feedObj = new Maestro();
                $feedObj->execute();
                break;
            case 'get':
                //primir proceso
                $feedObj = new Maestro();
                $feedObj->get();
                break;
            case 'drop':
                //ultimo proceeso
                $feedObj = new Maestro();
                $feedObj->borrarMaestro();
                break;
            case 'vpn':
                //ultimo proceeso
                $vpn = new Vpn();
                $vpn->getFileVpn();
                break;
            case 'reset-vpn':
                $vpn = new Vpn();
                $vpn->resetVpn();
                break;
            default:
                echo "parametro incorecto";
                break;
        }
        break;
    case 3:
        $action = $argv[1];
        $data = array('format'=>$argv[2]);
        switch ($action) {
            case 'execute':
                //segundo proceso
                $feedObj = new Maestro();
                $feedObj->execute($data);
                break;
            default:
                echo "parametro incorecto";
                break;
        }
        break;

    case 4:
        $action = $argv[1];
        $data = array('format'=>$argv[2],
            'raiz'=>$argv[3],'hora'=>120);
        switch ($action) {
            case 'execute':
                //segundo proceso
                $feedObj = new Maestro();
                $feedObj->execute($data);
                break;
            case 'get':
                //primir proceso
                $feedObj = new Maestro();
                $feedObj->get($data);
                break;
            case 'drop':
                //ultimo proceeso
                $feedObj = new Maestro();
                $feedObj->borrarMaestro($data);
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

