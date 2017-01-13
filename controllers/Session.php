<?php

namespace controllers;

use Flight;
use models\SessionModel;
use Exception;
use Logger;
/**
 * 有关登录的相关接口
 */

class Session
{
    static public function setRoute()
    {
        Flight::map('checkAccessToken',function()
        {
            return Session::checkAccessToken();
        });

        Flight::route('POST /admin/login',array(get_called_class(),'cbAdminLogin'));
    }

    /**
     * @return bool $return_value 判断是否是合法的用户调用
     */
    static public function checkAccessToken()
    {
        $return_value = false;
        $cookie_data = self::parseCookies(Flight::request()->cookies);

        //登录时生成的信息，用于验证用户身份
        $access_token = !empty($cookie_data['token']) ? $cookie_data['token'] : '';

        if(self::isFreeTokenUrl()){
            //判断请求的api是否为免验证的Url
            $return_value = true;
        }else{
            //通过token验证数据信息
            $data_from_db = SessionModel::checkUserByToken($access_token);
            if($data_from_db > 0){
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

        if(in_array($url,$free_token_url_arr)){
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
        $token = empty(Flight::request()->cookies->token) ? '' : Flight::request()->cookies->token;

        $return_value = array(
            'token' => $token,
        );

        return $return_value;
    }

    /**
     * #登录成功返回token
     * #登录失败返回错误信息
     * @return array
     */
    static public function cbAdminLogin()
    {
        $post_data = Flight::request()->data->getData();
        $admin_user = isset($post_data['user_name']) ? $post_data['user_name'] : '';
        $pass_word = isset($post_data['pass_word']) ? $post_data['pass_word'] : '';

        try{
            Flight::db()->start_transaction();

            //根据用户名和密码查询用户是否存在
            $user_data = SessionModel::getUserInfoByNamePassWord($admin_user,$pass_word);
            if(empty($user_data)){
                throw new Exception('用户名或密码错误');
            }

            if(count($user_data) > 1){
                throw new Exception('数据库中存有多个相同的用户');
            }

            //登录成功，则生成token写入数据库
            $token = self::createToken($admin_user);
            $save_result = SessionModel::saveToken($user_data[0]['admin_user_id'],$token);

            if(empty($save_result)){
                throw new Exception('token写入失败');
            }

            $return_value = array('token' => $token);
            Flight::db()->commit();
            Logger::getLogger('Session')->info('[info] admin user: '.$admin_user.' login time: '.date('Y-m-d H:i:s'));
        }catch (Exception $e){
            Flight::db()->rollback();
            Logger::getLogger('Session')->error('[error]login error '.$e->getMessage());
            Flight::sendRouteResult(array('error_code' => '', 'error_info' => $e->getMessage()));
        }

        Flight::sendRouteResult($return_value);  
    }

    /**
     * @param string $admin_user 登录用户名
     * @return mixed 生成token信息
     */
    static public function createToken($admin_user)
    {
        return uniqid(md5($admin_user).'-'.md5(rand()).'-',true);
    }
}