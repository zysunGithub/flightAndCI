<?php

//时区设置
date_default_timezone_set("Asia/Shanghai");

require_once 'flight/Flight.php';
require_once 'controllers/ObjContainer.php';
require_once 'tools/ObjContainer.php';
require_once 'log4php/Logger.php';
//require_once 'models/ObjContainer.php';

//日志配置
Logger::configure('config/logger_config.xml');

$route_logger = Logger::getLogger('Route');
$route_logger->debug($_SERVER);

// cross domain access
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $http_origin = $_SERVER['HTTP_ORIGIN'];
    if (Tools\ClsUtilsTools::isAllowCrossDomain($http_origin)) {
        header("Access-Control-Allow-Origin: $http_origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, X-HTTP-Method-Override, Cookie');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    }
}

//view
Flight::set('flight.views.path', 'views');

//error
Flight::set('flight.log_errors', true);
Flight::map('error', function(Exception $ex){
    echo $ex->getTraceAsString();
});

// 404
Flight::map('notFound', function(){
    Flight::sendRouteResult(array(
        "error_code"=>'40000',
    ));
});

// default
Flight::route('/', function(){
    $ip = Tools\ClsUtilsTools::getRealIp();
    echo '你的IP地址是:        ' . $ip;
});

// phpinfo
Flight::route('/cookie', function(){
    var_dump(Flight::request()->cookies);
});

// phpinfo
Flight::route('/phpinfo', function(){
    phpinfo();
});

Logger::getLogger('Route')->debug(Flight::request());

//assert option
//temop to org
//assert_options(ASSERT_CALLBACK, 'my_assert_handler');
//function my_assert_handler($file, $line, $code)
//{
//    Logger::getLogger('Route')->error(array($file, $line, $code));
//    Flight::sendRouteResult(array(
//        "error_code"=>'50000',
//    ));
//}

try {
    $token=Flight::request()->cookies->token;
    $access_result=Flight::checkAccessToken();

    if(!$access_result){//没有权限访问
        setcookie('token', '', 0, '/');
        Flight::sendRouteResult(array(
            "error_code" => '40001',
            "error_stack" => "token check Failed. input token: .$token",
        ));
    }

    Flight::start();
} catch(Exception $e) {
    Logger::getLogger('Route')->error($e);
    Flight::sendRouteResult(array(
        "error_code"=>'50000',
        "error_stack"=>$e->getTraceAsString(),
    ));

}
