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


define("SEND_HOST", 'your_smtp');
define("SEND_TO", 'your_email_to_send_back_up_to');
define("SMTP_USERNAME", 'your_username');
define("SMTP_PASSWORD", 'your_password');
define("SMTP_PORT", "your_port");

define("DB_HOST", 'localhost');
define("TABLES", '*'); // Full backup
//define("TABLES", 'table1, table2, table3'); // Partial backup
define('IGNORE_TABLES', array(
    'tbl_token_auth',
    'token_auth'
));
define("CHARSET", 'utf8');
define("GZIP_BACKUP_FILE", true); // Set to false if you want plain SQL backup files (not gzipped)
define("DISABLE_FOREIGN_KEY_CHECKS", true); // Set to true if you are having foreign key constraint fails
define("BATCH_SIZE", 1000); // Batch size when selecting rows from database in order to not exhaust system memory
// Also number of rows per INSERT statement in backup file


require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';
/**
 * This file contains the Backup_Database class wich performs
 * a partial or complete backup of any given MySQL database
 * @author Daniel López Azaña <daniloaz@gmail.com>
 * @version 1.0
 */
/**
 * The Backup_Database class
 */
class Backup_Database
{
    /**
     * Host where the database is located
     */
    var $host;

    /**
     * Username used to connect to database
     */
    var $username;

    /**
     * Password used to connect to database
     */
    var $passwd;

    /**
     * Database to backup
     */
    var $dbName;

    /**
     * Database charset
     */
    var $charset;

    /**
     * Database connection
     */
    var $conn;

    /**
     * Backup directory where backup files are stored 
     */
    var $backupDir;

    /**
     * Output backup file
     */
    var $backupFile;

    /**
     * Use gzip compression on backup file
     */
    var $gzipBackupFile;

    /**
     * Content of standard output
     */
    var $output;

    /**
     * Disable foreign key checks
     */
    var $disableForeignKeyChecks;

    /**
     * Batch size, number of rows to process per iteration
     */
    var $batchSize;

    /**
     * Constructor initializes database
     */
    public function __construct($host, $username, $passwd, $dbName, $charset = 'utf8')
    {
        $this->host                    = $host;
        $this->username                = $username;
        $this->passwd                  = $passwd;
        $this->dbName                  = $dbName;
        $this->charset                 = $charset;
        $this->conn                    = $this->initializeDatabase();
        $this->backupDir               = BACKUP_DIR ? BACKUP_DIR : '.';
        $this->backupFile              = 'backup-' . $this->dbName . '-' . date("Ymd_His", time()) . '.sql';
        $this->gzipBackupFile          = defined('GZIP_BACKUP_FILE') ? GZIP_BACKUP_FILE : true;
        $this->disableForeignKeyChecks = defined('DISABLE_FOREIGN_KEY_CHECKS') ? DISABLE_FOREIGN_KEY_CHECKS : true;
        $this->batchSize               = defined('BATCH_SIZE') ? BATCH_SIZE : 1000; // default 1000 rows
        $this->output                  = '';
    }

    protected function initializeDatabase()
    {
        try {
            $conn = mysqli_connect($this->host, $this->username, $this->passwd, $this->dbName);
            if (mysqli_connect_errno()) {
                throw new Exception('ERROR connecting database: ' . mysqli_connect_error());
                die();
            }
            if (!mysqli_set_charset($conn, $this->charset)) {
                mysqli_query($conn, 'SET NAMES ' . $this->charset);
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            die();
        }

        return $conn;
    }

    /**
     * Backup the whole database or just some tables
     * Use '*' for whole database or 'table1 table2 table3...'
     * @param string $tables
     */
    public function backupTables($tables = '*')
    {
        try {
            /**
             * Tables to export
             */
            if ($tables == '*') {
                $tables = array();
                $result = mysqli_query($this->conn, 'SHOW TABLES');
                while ($row = mysqli_fetch_row($result)) {
                    $tables[] = $row[0];
                }
            } else {
                $tables = is_array($tables) ? $tables : explode(',', str_replace(' ', '', $tables));
            }

            $sql = 'CREATE DATABASE IF NOT EXISTS `' . $this->dbName . '`' . ";\n\n";
            $sql .= 'USE `' . $this->dbName . "`;\n\n";

            /**
             * Disable foreign key checks 
             */
            if ($this->disableForeignKeyChecks === true) {
                $sql .= "SET foreign_key_checks = 0;\n\n";
            }

            /**
             * Iterate tables
             */
            foreach ($tables as $table) {
                if (in_array($table, IGNORE_TABLES))
                    continue;
                $this->obfPrint("Backing up `" . $table . "` table..." . str_repeat('.', 50 - strlen($table)), 0, 0);

                /**
                 * CREATE TABLE
                 */
                $sql .= 'DROP TABLE IF EXISTS `' . $table . '`;';
                $row = mysqli_fetch_row(mysqli_query($this->conn, 'SHOW CREATE TABLE `' . $table . '`'));
                $sql .= "\n\n" . $row[1] . ";\n\n";

                /**
                 * INSERT INTO
                 */

                $row = mysqli_fetch_row(mysqli_query($this->conn, 'SELECT COUNT(*) FROM `' . $table . '`'));
                $numRows = $row[0];

                // Split table in batches in order to not exhaust system memory 
                $numBatches = intval($numRows / $this->batchSize) + 1; // Number of while-loop calls to perform

                for ($b = 1; $b <= $numBatches; $b++) {

                    $query = 'SELECT * FROM `' . $table . '` LIMIT ' . ($b * $this->batchSize - $this->batchSize) . ',' . $this->batchSize;
                    $result = mysqli_query($this->conn, $query);
                    $realBatchSize = mysqli_num_rows($result); // Last batch size can be different from $this->batchSize
                    $numFields = mysqli_num_fields($result);

                    if ($realBatchSize !== 0) {
                        $sql .= 'INSERT INTO `' . $table . '` VALUES ';

                        for ($i = 0; $i < $numFields; $i++) {
                            $rowCount = 1;
                            while ($row = mysqli_fetch_row($result)) {
                                $sql .= '(';
                                for ($j = 0; $j < $numFields; $j++) {
                                    if (isset($row[$j])) {
                                        $row[$j] = addslashes($row[$j]);
                                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                                        $row[$j] = str_replace("\r", "\\r", $row[$j]);
                                        $row[$j] = str_replace("\f", "\\f", $row[$j]);
                                        $row[$j] = str_replace("\t", "\\t", $row[$j]);
                                        $row[$j] = str_replace("\v", "\\v", $row[$j]);
                                        $row[$j] = str_replace("\a", "\\a", $row[$j]);
                                        $row[$j] = str_replace("\b", "\\b", $row[$j]);
                                        if ($row[$j] == 'true' or $row[$j] == 'false' or preg_match('/^-?[1-9][0-9]*$/', $row[$j]) or $row[$j] == 'NULL' or $row[$j] == 'null') {
                                            $sql .= $row[$j];
                                        } else {
                                            $sql .= '"' . $row[$j] . '"';
                                        }
                                    } else {
                                        $sql .= 'NULL';
                                    }

                                    if ($j < ($numFields - 1)) {
                                        $sql .= ',';
                                    }
                                }

                                if ($rowCount == $realBatchSize) {
                                    $rowCount = 0;
                                    $sql .= ");\n"; //close the insert statement
                                } else {
                                    $sql .= "),\n"; //close the row
                                }

                                $rowCount++;
                            }
                        }

                        $this->saveFile($sql);
                        $sql = '';
                    }
                }

                /**
                 * CREATE TRIGGER
                 */

                // Check if there are some TRIGGERS associated to the table
                /*$query = "SHOW TRIGGERS LIKE '" . $table . "%'";
                $result = mysqli_query ($this->conn, $query);
                if ($result) {
                    $triggers = array();
                    while ($trigger = mysqli_fetch_row ($result)) {
                        $triggers[] = $trigger[0];
                    }
                    
                    // Iterate through triggers of the table
                    foreach ( $triggers as $trigger ) {
                        $query= 'SHOW CREATE TRIGGER `' . $trigger . '`';
                        $result = mysqli_fetch_array (mysqli_query ($this->conn, $query));
                        $sql.= "\nDROP TRIGGER IF EXISTS `" . $trigger . "`;\n";
                        $sql.= "DELIMITER $$\n" . $result[2] . "$$\n\nDELIMITER ;\n";
                    }

                    $sql.= "\n";

                    $this->saveFile($sql);
                    $sql = '';
                }*/

                $sql .= "\n\n";

                $this->obfPrint('OK');
            }

            /**
             * Re-enable foreign key checks 
             */
            if ($this->disableForeignKeyChecks === true) {
                $sql .= "SET foreign_key_checks = 1;\n";
            }

            $this->saveFile($sql);

            if ($this->gzipBackupFile) {
                $this->gzipBackupFile();
            } else {
                $this->obfPrint('Backup file succesfully saved to ' . $this->backupDir . '/' . $this->backupFile, 1, 1);
            }

            $this->sendEmail();
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Save SQL to file
     * @param string $sql
     */
    protected function saveFile(&$sql)
    {
        if (!$sql) return false;

        try {

            if (!file_exists($this->backupDir)) {
                mkdir($this->backupDir, 0777, true);
            }

            file_put_contents($this->backupDir . '/' . $this->backupFile, $sql, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }

        return true;
    }

    /*
     * Gzip backup file
     *
     * @param integer $level GZIP compression level (default: 9)
     * @return string New filename (with .gz appended) if success, or false if operation fails
     */
    protected function gzipBackupFile($level = 9)
    {
        if (!$this->gzipBackupFile) {
            return true;
        }

        $source = $this->backupDir . '/' . $this->backupFile;
        $dest =  $source . '.gz';

        $this->obfPrint('Gzipping backup file to ' . $dest . '... ', 1, 0);

        $mode = 'wb' . $level;
        if ($fpOut = gzopen($dest, $mode)) {
            if ($fpIn = fopen($source, 'rb')) {
                while (!feof($fpIn)) {
                    gzwrite($fpOut, fread($fpIn, 1024 * 256));
                }
                fclose($fpIn);
            } else {
                return false;
            }
            gzclose($fpOut);
            if (!unlink($source)) {
                return false;
            }
        } else {
            return false;
        }

        $this->obfPrint('OK');
        return $dest;
    }

    /**
     * Prints message forcing output buffer flush
     *
     */
    public function obfPrint($msg = '', $lineBreaksBefore = 0, $lineBreaksAfter = 1)
    {
        if (!$msg) {
            return false;
        }

        if ($msg != 'OK' and $msg != 'KO') {
            $msg = date("Y-m-d H:i:s") . ' - ' . $msg;
        }
        $output = '';

        if (php_sapi_name() != "cli") {
            $lineBreak = "<br />";
        } else {
            $lineBreak = "\n";
        }

        if ($lineBreaksBefore > 0) {
            for ($i = 1; $i <= $lineBreaksBefore; $i++) {
                $output .= $lineBreak;
            }
        }

        $output .= $msg;

        if ($lineBreaksAfter > 0) {
            for ($i = 1; $i <= $lineBreaksAfter; $i++) {
                $output .= $lineBreak;
            }
        }


        // Save output for later use
        $this->output .= str_replace('<br />', '\n', $output);

        echo $output;


        if (php_sapi_name() != "cli") {
            if (ob_get_level() > 0) {
                ob_flush();
            }
        }

        $this->output .= " ";

        flush();
    }

    /**
     * Returns full execution output
     *
     */
    public function getOutput()
    {
        return $this->output;
    }
    /**
     * Returns name of backup file
     *
     */
    public function getBackupFile()
    {
        if ($this->gzipBackupFile) {
            return $this->backupDir . '/' . $this->backupFile . '.gz';
        } else
            return $this->backupDir . '/' . $this->backupFile;
    }

    /**
     * Returns backup directory path
     *
     */
    public function getBackupDir()
    {
        return $this->backupDir;
    }

    /**
     * Returns array of changed tables since duration
     *
     */
    public function getChangedTables($since = '1 day')
    {
        $query = "SELECT TABLE_NAME,update_time FROM information_schema.tables WHERE table_schema='" . $this->dbName . "'";

        $result = mysqli_query($this->conn, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $resultset[] = $row;
        }
        if (empty($resultset))
            return false;
        $tables = [];
        for ($i = 0; $i < count($resultset); $i++) {
            if (in_array($resultset[$i]['TABLE_NAME'], IGNORE_TABLES)) // ignore this table
                continue;
            if (strtotime('-' . $since) < strtotime($resultset[$i]['update_time']))
                $tables[] = $resultset[$i]['TABLE_NAME'];
        }
        return ($tables) ? $tables : false;
    }

    /**
     * Send File to Email
     * @param string $sql
     */
    public function sendEmail()
    {
        $file = $this->getBackupFile();

        $subject = 'Backup for ' . APP_NAME;
        $message = $subject;

        // set the mail headers
        $headers = 'From: ' . SMTP_USERNAME . "\r\n";
        $headers .= 'Reply-To: ' . SMTP_USERNAME . "\r\n";
        $headers .= 'Content-Type: text/plain; charset=utf-8' . "\r\n";

        // check the size of the file
        $size = filesize($file);

        // if the file is more than 20mb, split it into 15mb files
        if ($size > 20 * 1024 * 1024) {

            // create an array to store the split files
            $split_files = array();

            // get the number of chunks
            $chunks = ceil($size / 15 * 1024 * 1024);

            // iterate through the chunks
            for ($i = 0; $i < $chunks; $i++) {

                // create a new file
                $new_file = fopen('split_' . $i . '.gz', 'w');

                // read the current chunk from the original file
                $chunk = fread($file, 15 * 1024 * 1024);

                // write the chunk to the new file
                fwrite($new_file, $chunk);

                // close the file
                fclose($new_file);

                // add the split file to the array
                $split_files[] = $file . '_split_' . $i . '.gz';
            }
            // set the file variable to the first split file
            // $file = $split_files[0];
            $split_files = glob($file . '_split_*.gz');
        }

        // $mail = new PHPMailer();
        $mail = new PHPMailer\PHPMailer\PHPMailer();

        // set the mail properties
        $mail->isSMTP();
        $mail->Host = SEND_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->Port = 465;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->setFrom(SMTP_USERNAME);
        $mail->addAddress(SEND_TO);
        $mail->Subject = $subject;

        $message .= "\n";
        if (isset($split_files)) {
            foreach ($split_files as $split_file) {
                $message .= $split_file . "\n";
                $mail->addAttachment($split_file);
                $this->obfPrint('split_file : ' . $split_file);
                echo 'split_file : ' . $split_file;
            }
        } else {
            $this->obfPrint('file : ' . $file);
            echo 'DB_NAME : ' . SMTP_PORT . "\n";
            $message .= $file . "\n";
            $mail->addAttachment($file);
        }
        $mail->Body = $message;

        // if the email was sent successfully, print a success message
        if ($mail->send()) {
            echo 'Email sent successfully!';
        } else {
            echo 'Email failed to send.';
        }
    }
}
