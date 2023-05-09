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
$dir = __DIR__; // Use the current directory as the base directory

// Get current URL
$current_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// List all PHP files in the directory
$files = array_diff(scandir($dir), array('..', '.'));

foreach ($files as $file) {
  if (pathinfo($file, PATHINFO_EXTENSION) == "php") {
    echo "<br><br><a href='$current_url$file'>$file</a><br>";
  }
}
