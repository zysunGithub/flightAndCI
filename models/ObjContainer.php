<?php
require_once 'Model.php';

$dir = __DIR__;

/**
 * 把文件所在文件夹下的文件都包含包含进来
 */
foreach(glob($dir.'/*.php') as $file)
{
    if (file_exists($file)) {
        require_once $file;
    }
}


?>
