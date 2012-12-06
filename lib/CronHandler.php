<?php
/** Crontask Handler
*
* Copyright (C) 2012 B Tasker
* Released under GNU GPL V2
* See LICENSE
*
*/
defined('_CREDLOCK') or die;

// Check the password has been specified
if ($_SERVER['CRON_PASS'] != BTMain::getConf()->cronPass){
echo "Access Denied\n\n";
ob_end_flush();
die;
}

if (empty(BTMain::getConf()->cronPass)){

echo "Error: Cron Pass not set in config. Aborting for security reasons\n\n";
ob_end_flush();
die;
}


require_once 'lib/db/cron.php';


$crondb = new CronDB;

// Clear any sessions
echo "Clearing old sessions\n";
$crondb->clearOldSessions();




echo "Checking Sessions files";
$time = time();
// Tidy up the sessions files
$dir = new DirectoryIterator(dirname(__FILE__)."/../sessions/");
foreach ($dir as $fileinfo) {
    if (!$fileinfo->isDot()) {
        

    $fn = $fileinfo->getFilename();

   
    if ($fn == "index.html"){ continue; }

    $fname = explode("-",$fn);
    
    if ($fname[1] < $time){
       echo "Removing $fn\n";
    unlink(dirname(__FILE__)."/../sessions/$fn");
    }


    }
}


// Pass off to any cron plugins
require_once 'lib/plugins.php';
$plgs = new Plugins;
$plgs->loadPlugins("Cron",ob_get_clean());


?>