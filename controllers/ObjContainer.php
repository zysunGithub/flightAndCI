<?php
use Exception;
$dir = __DIR__;
//把控制器中的定义的路由信息加载到index.php文件中
foreach(glob($dir.'/*.php') as $file)
{
    if (file_exists($file)) {
        require_once $file;
		$class = 'controller\\' . basename($file, ".php");
		if(class_exists($class) && method_exists($class,'setRoute')) {
			$class::setRoute();
		}
    }
}

?>
