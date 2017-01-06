<?php

require_once 'flight/Flight.php';
require_once 'controller/ObjContainer.php';
require_once 'tools/ObjContainer.php';
require_once 'tools/cls_utils_tools.php';
require_once('log4php/Logger.php');

// require_once 'controller/ObjContainer.php';
require_once 'model/ObjContainer.php';

Logger::getLogger('Route')->debug($_SERVER);

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

Logger::configure('config/logger_config.xml');
date_default_timezone_set("Asia/Shanghai");

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

//tools for route

/**
 * Usage as following: (Sinri 2015-05-08 Afternoon)
 *
 * # Normal response
 * Flight::sendRouteResult(array(
 *     // 'error_code'=>'200',// [Optional] This is by default as NoError.
 *     'data'=>'XXX', // Any response(s) needed.
 * ));
 *
 * # Error response
 * Flight::sendRouteResult(array(
 *     'error_code'=>'500', // Or Other error code, other than 200.
 *     // 'error_info'=>'Customized Info', // [Optional] It could be set by default with config.
 * ));
 **/
Flight::map('sendRouteResult', function($data){
    $data = is_object($data)? get_object_vars($data) :$data;

    if(isset($data['error_code'])){
        if(!isset($data['error_stack'])){
            $data['error_stack'] = $data;
        }
        if(!isset($data['error_code'])){
            $data['error_code'] = '50000';
        }
        if(!isset($data['error_info'])){
            $data['error_info'] = Tools\ClsUtilsTools::getErrorInfo($data['error_code']);
        }

        $result = array(
            'result' => 'fail',
            'error_code' => $data['error_code'], // For Error Type. See predefined config
            'error_info' => $data['error_info']  // For Error Shotting. Could be auto set with config by default
        );

        Logger::getLogger("Route")->error(Flight::request());
        Logger::getLogger("Route")->error($result);

        if($data['error_code'] == 50000) {
//             $ts = @file_get_contents("config/alert.ts");
//             if(empty($ts) || time() - intval($ts) > 600) {
//                 $config = parse_ini_file('config/alert.ini');
//                 $recipients = explode(',', $config['recipients']);
//                 foreach($recipients as $r) {
//                     $domain = Tools\ClsUtilsTools::$domain_array['allow']['api_domain'];
//                     Tools\ClsMessageTools::sendSMS(
//                         $r, 'juhe', 'message_alert_tpl',
//                         array('error_code'=>$data['error_code'],
//                               'url'=>"http://$domain:8080/?name=basic&lines=100&date="));
//                 }
//                 $handle = @fopen('config/alert.ts', 'w');
//                 if(!empty($handle)) {
//                     fprintf($handle, '%d', time());
//                     fclose($handle);
//                 }
//             }
        }
    }else{
        $data['result'] = 'ok';
        Tools\ClsUtilsTools::array2string($data);
        $result = $data;
        Logger::getLogger("Route")->debug(Flight::request());
        Logger::getLogger("Route")->debug($result);
    }

    Flight::json($result);
});

Logger::getLogger('Route')->debug(Flight::request());


//temop to org
assert_options(ASSERT_CALLBACK, 'my_assert_handler');
function my_assert_handler($file, $line, $code)
{
    Logger::getLogger('Route')->error(array($file, $line, $code));
    Flight::sendRouteResult(array(
        "error_code"=>'50000',
    ));
}

try{
    $access_token=Flight::request()->cookies->AccessToken;
    $access_type=empty(Flight::request()->cookies->AccessType)?"weishop":Flight::request()->cookies->AccessType;
    $request_app_id=Flight::request()->query->app_id;

    $CAT_Result=Flight::checkAccessToken();
    if($CAT_Result==0){
        setcookie('AccessToken', '', 0, '/');
        Flight::sendRouteResult(array(
            "error_code"=>'40001',
            "error_stack"=>'AccessToken Check Failed. Input AccessToken: '.$access_token." and type is ".$access_type." for AppId ".$request_app_id,
        ));
        //Flight::halt(400, 'AccessToken Check Failed. Input AccessToken: '.$access_token." and type is ".$access_type);
        //echo 'AccessToken Check Failed. Input AccessToken: '.$access_token." and type is ".$access_type;
    }

    Flight::start();
} catch(Exception $e){
    Logger::getLogger('Route')->error($e);
    Flight::sendRouteResult(array(
        "error_code"=>'50000',
        "error_stack"=>$e->getTraceAsString(),
    ));

}
