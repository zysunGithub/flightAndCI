<?php

namespace controllers;

use Flight;
use models\SessionModel;

/**
 * 有关登录的相关接口
 */

class Session
{
    static function setRoute()
    {
       Flight::map('checkAccessToken',function(){
           return Session::checkAccessToken();
       });
    }

    /**
     * @return bool $return_value 判断是否是合法的用户调用
     */
    static public function checkAccessToken()
    {
        $return_value = false;

        $cookie_data = self::parseCookies(Flight::request()->cookies);

        //登录时生成的信息，用于验证用户身份
        $access_token = isset($cookie_data['token']) ? $cookie_data['token'] : '';

        if(empty($access_token)){
            //$access_token为空时，判断请求的api是否为免验证的Url
            if(self::isFreeTokenUrl()) {
                $return_value = true;
            }
        }else{
            //通过token验证数据信息
            $data_from_db = SessionModel::checkUserByToken($access_token);
            if(1 == $data_from_db){
                $return_value = true;
            }
        }

        return $return_value;
     }

    /**
     * @return bool $return_value 是否为免Token登录的url
     */
    static public function isFreeTokenUrl()
    {
        $return_value = false;
        $url = Flight::request()->url;

        $master_config_data = Flight::get('master_config');
        $free_token_url_arr = $master_config_data['free_token_url_arr'];
        $free_token_url_prefix_arr = $master_config_data['free_token_url_prefix_arr'];

        if(in_arr($url,$free_token_url_arr)){
            $return_value = true;
        }else{
            foreach ($free_token_url_prefix_arr as $item){
                if(strpos($url,$item) === 0){
                    $return_value = true;
                    break;
                }
            }
        }

        return $return_value;
    }

    /**
     * @return array $return_value 需要解析的cookies的信息
     */
    static public function parseCookies()
    {
        $token = Flight::request()->token;

        $return_value = array(
            'token' => $token,
        );

        return $return_value;
    }
}