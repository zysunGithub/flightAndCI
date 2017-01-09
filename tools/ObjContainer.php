<?php

//database
require_once 'cls_mysql.php';
$config_data = parse_ini_file('config/database.ini');
Flight::register('db', 'cls_mysql', array($config_data['host'], $config_data['username'], $config_data['password'], $config_data['database']));
Flight::register('slave', 'cls_mysql', array($config_data['slave_host'], $config_data['slave_username'], $config_data['slave_password'], $config_data['slave_database']));

require_once 'cls_basic_data.php';
require_once 'HttpClient.php';
require_once 'config/master_config.php';
require_once 'cls_test_tool.php';
require_once 'cls_utils_tool.php';

Flight::set('master_config', $master_config);

Flight::map('sendRouteResult',array("Tools\\ClsUtilsTools","sendRouteResult"));
Flight::map('checkParamMatchRegex',array("Tools\\ClsUtilsTools","checkStringMatchRegex"));
Flight::map('generateBarCode',array("Tools\\ClsUtilsTools","generateBarCode"));
Flight::map('checkParamNotNull', array("Tools\\ClsUtilsTools", "checkStringNotNull"));
Flight::map('toolTest', array("Tools\\ClsTestTool", "cbTest"));
?>
