<?php
/**
 * Created by PhpStorm.
 * User: ADMIN
 * Date: 2018-08-10
 * Time: 09:39
 */
require '../vendor/autoload.php';
$snlog = new snlg\Snlog('','');
$log = $snlog->molog('test','','');
$log->warning('foo');
$info=$snlog->molog('nameinfo','',$snlog->mohandler('','INFO'));
$info->info('fooinfo');
