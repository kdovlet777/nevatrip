<?php
require 'vendor/autoload.php';

function modelLoader($class)
{
    // Replace namespace separators with directory separators
    $result = str_replace("\\", "/", $class);
    
    // Ensure the directory structure matches the namespace
    $filePath = $result . '.php';
    
    // Check if the file exists before including it
    if (file_exists($filePath)) {
        include $filePath;
    } else {
        // Optionally, throw an error or log the issue
        error_log("Autoload failed: Unable to find $filePath");
    }
}

// Register the autoload function
spl_autoload_register('modelLoader');