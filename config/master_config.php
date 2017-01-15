<?php

//调用其他系统接口的配置文件
$master_config = array(
	"test_config" => [
		"test_url" => "待调用的接口",
	],
	"free_token_url_arr" => [//不用token验证就可访问的url
		'/admin/login',
		'/admin/checkEmail',
		'/admin/resetPassword',
	],
	"free_token_url_prefix_arr" => [//url前缀在数组里,也可免token登录
	],
	
);    