<?php
/* ------------------------------------------------------------
 ██╗  ██╗███████╗██╗    ██╗   ██╗██╗     ██╗███╗   ██╗███████╗
 ██║ ██╔╝██╔════╝██║    ██║   ██║██║     ██║████╗  ██║██╔════╝ 
 █████╔╝ █████╗  ██║    ██║   ██║██║     ██║██╔██╗ ██║█████╗   
 ██╔═██╗ ██╔══╝  ██║    ╚██╗ ██╔╝██║     ██║██║╚██╗██║██╔══╝   
 ██║  ██╗███████╗███████╗╚████╔╝ ███████╗██║██║ ╚████║███████╗ 
 ╚═╝  ╚═╝╚══════╝╚══════╝ ╚═══╝  ╚══════╝╚═╝╚═╝  ╚═══╝╚══════╝ 
 Author: Kelvline | One line of code at a time. 
 GitHub: https://github.com/kelvline 
 --------------------------------------------------------------*/
define("APP_NAME", 'your_app_name');
define("DB_USER", 'your_db_user');
define("DB_PASSWORD", 'your_db_password');
define("DB_NAME", 'your_db_name');
define("BACKUP_DIR", 'backup-files'); // Comment this line to use same script's directory ('.')

include "backup.php";
/**
 * Instantiate Backup_Database and perform backup
 */
// Report all errors
error_reporting(E_ALL);
// Set script max execution time
set_time_limit(900); // 15 minutes

if (php_sapi_name() != "cli") {
    echo '<div style="font-family: monospace;">';
}

$backupDatabase = new Backup_Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, CHARSET);

// Option-1: Backup tables already defined above
$result = $backupDatabase->backupTables(TABLES) ? 'OK' : 'KO';

// Option-2: Backup changed tables only - uncomment block below
/*
$since = '1 day';
$changed = $backupDatabase->getChangedTables($since);
if(!$changed){
  $backupDatabase->obfPrint('No tables modified since last ' . $since . '! Quitting..', 1);
  die();
}
$result = $backupDatabase->backupTables($changed) ? 'OK' : 'KO';
*/

$backupDatabase->obfPrint('Backup result: ' . $result, 1);

// Use $output variable for further processing, for example to send it by email
$output = $backupDatabase->getOutput();

if (php_sapi_name() != "cli") {
    echo '</div>';
}
