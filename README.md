# MySQL Backup Script - Automate Your Database Backup Process
  
<p><strong>INTRODUCTION</strong></p>
<p>The DATABASE BACKUP SCRIPT is a PHP script that allows you to create backups of your database, store them on your local machine&nbsp;and send a copy to your email address. With this script, you can automate the backup process and ensure that your data is safe and secure.</p>
<p>The script is designed to work with MySQL databases, and it can be easily configured to match your database credentials and backup preferences. Once the script is installed, you can run it manually or set it up to run automatically using cron jobs in Windows or Linux.</p>
<p>The backup files are stored in a folder on your local machine, which can be accessed and downloaded at any time. With regular backups, you can ensure that your data is protected in case of hardware failure, data corruption, or other unforeseen events.</p>
<p>In the following sections, we'll walk through the steps to install and configure the script, as well as how to run it manually and using cron jobs in Windows and Linux.</p>
<p><strong>SCRIPT STRUCTURE</strong></p>
<ol>
<li>The "scr" directory: Contains the PHPMailer classes, a third-party PHP library that provides an easy way to send emails in PHP.</li>
<li>The "index.php" file: Lists all the files in the directory when accessed.</li>
<li>The "backup.php" file: Backs up the actual database and sends the database to an email of your choice. This file also contains various settings that can be customized, including SMTP settings and backup preferences.</li>
</ol>
<p>Some of the important settings in the "backup.php" file include:</p>
<ul>
<li><strong>SEND_HOST</strong>: Specifies the email server to use for sending the backup file.</li>
<li><strong>SEND_TO</strong>: Specifies the email address to send the backup file to.</li>
<li><strong>SMTP_USERNAME</strong>: Specifies the username for SMTP authentication.</li>
<li><strong>SMTP_PASSWORD</strong>: Specifies the password for SMTP authentication.</li>
<li><strong>SMTP_PORT</strong>: Specifies the SMTP port to use.</li>
<li><strong>DB_HOST</strong>: Specifies the hostname for the database server.</li>
<li><strong>TABLES</strong>: Specifies the tables to include in the backup. "*" backs up all tables, while a comma-separated list can be used for partial backups.</li>
<li><strong>IGNORE_TABLES</strong>: Specifies any tables to ignore in the backup.</li>
<li><strong>CHARSET</strong>: Specifies the character set for the backup file.</li>
<li><strong>GZIP_BACKUP_FILE</strong>: Specifies whether to compress the backup file using gzip.</li>
<li><strong>DISABLE_FOREIGN_KEY_CHECKS</strong>: Specifies whether to disable foreign key checks during the backup.</li>
<li><strong>BATCH_SIZE</strong>: Specifies the batch size for selecting rows from the database, in order to avoid exhausting system memory.</li>
</ul>
<ol start="4">
<li>The "system_example.php" file: Contains settings for a particular system that you want to back up. This file can be duplicated and renamed for multiple systems, with the necessary settings provided inside.</li>
</ol>
<p>Some of the important settings in the "system_example.php" file include:</p>
<ul>
<li><strong>APP_NAME</strong>: Specifies the name of the system being backed up.</li>
<li><strong>DB_USER</strong>: Specifies the username for the database user.</li>
<li><strong>DB_PASSWORD</strong>: Specifies the password for the database user.</li>
<li><strong>DB_NAME</strong>: Specifies the name of the database to back up.</li>
<li><strong>BACKUP_DIR</strong>: Specifies the directory to store the backup file in. If not specified, the default directory will be the same as the location of the backup script.</li>
</ul>
<p>These settings will vary depending on the specific system being backed up. You can duplicate and rename the "system_example.php" file for each system you want to back up, and customize the settings accordingly.</p>
<p><strong>INSTALLING AND CONFIGURING THE SCRIPT</strong></p>
<ol>
<li>Download the script:
<ol>
<li>Clone the repository using Git by running the following command in your terminal or command prompt:</li>
</ol>
</li>
</ol>
<p><strong>git clone https://github.com/kelvline/mysql-backup-script.git</strong></p>
<ol>
<li>Alternatively, you can download a ZIP file of the repository from the GitHub website. To do this, go to your repository's main page, click on the "Code" button, and select "<strong>Download ZIP</strong>".</li>
</ol>
<ol start="2">
<li>Extract the script to a folder on your computer.</li>
<li>Open the script in a text editor, such as Notepad or Sublime Text.</li>
<li>Configure the settings at the top of the file to match your database credentials and backup preferences. For example, you might need to change the database name, username, password, and hostname to match your own database setup.</li>
<li>Save the changes to the script and close the text editor.</li>
<li>Create a new folder on your computer to store the backup files. You can name this folder anything you like, but it's a good idea to choose a name that's easy to remember and descriptive of its contents.</li>
<li>Ensure that the web server user (usually "www-data" or "apache") has write access to the backup folder. This is necessary to allow the script to save backup files to the folder. The process for setting file permissions depends on your operating system, but you can typically do this by right-clicking on the folder, selecting "Properties" or "Get Info", and adjusting the permissions under the "Security" or "Permissions" tab</li>
</ol>
<p><strong>RUNNING THE SCRIPT MANUALLY</strong></p>
<ol>
<li>Open a command prompt or terminal window.</li>
<li>Navigate to the directory where the backup script is located.</li>
<li>Enter the command to run the script, which may vary depending on your operating system and PHP installation. For example:</li>
</ol>
<ul>
<li>Windows: php.exe backup.php</li>
<li>Linux: php backup.php</li>
</ul>
<ol start="4">
<li>Press Enter to execute the command.</li>
<li>Wait for the script to finish running, which may take several minutes depending on the size of your database.</li>
</ol>
<p><strong>RUNNING THE SCRIPT USING CRON JOBS</strong></p>
<p><strong>WINDOWS</strong></p>
<ol>
<li>Open the Task Scheduler app in Windows.</li>
<li>Click "Create Task" in the sidebar.</li>
<li>Name the task and configure the settings as follows:
<ol>
<li>General tab: Select "Run whether user is logged on or not" and "Run with highest privileges".</li>
<li>Triggers tab: Click "New" and select "Daily". Set the time and recurrence pattern as desired.</li>
<li>Actions tab: Click "New" and select "Start a program". Enter the path to the PHP executable and the backup script, separated by a space. For example: <strong>C:\xampp\php\php.exe</strong><strong>C:\xampp\htdocs\backup\backup.ph</strong>p</li>
</ol>
</li>
<li>Click "OK" to save the task.</li>
</ol>
<p><strong>LINUX</strong></p>
<ol>
<li>Open a terminal window and enter the following command to open the cron configuration file: crontab -e</li>
<li>Add a new line to the file with the following syntax: * * * * * /usr/bin/php /path/to/backup.php &gt; /dev/null 2&gt;&amp;1</li>
<li>Replace the asterisks with the desired schedule for the backup job. For example, the following line would run the backup script every day at 1:00 AM: 0 1 * * * /usr/bin/php /path/to/backup.php &gt; /dev/null 2&gt;&amp;1</li>
<li>Save the file and exit the text editor.</li>
</ol>
<p>That's it! Your DATABASE BACKUP SCRIPT is now installed and scheduled to run automatically.</p>
