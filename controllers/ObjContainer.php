<?php
$dir = __DIR__;
//把控制器中的定义的路由信息加载到index.php文件中
foreach(glob($dir.'/*.php') as $file)
{
    if (file_exists($file)) {
        require_once $file;
		//controllers为包含控制器的文件夹名称，这个需要随着控制器文件夹名称的改变而改变
		$class = 'controllers\\' . basename($file, ".php");
		if(class_exists($class) && method_exists($class,'setRoute')) {
			$class::setRoute();
		}
    }
}

