<?php
namespace models;

use Flight;

//与用户登录相关的model
class SessionModel
{
    /**
     * @param string $token 用户登录时生成的验证信息
     * @return int mixed 查询出的符合条件的记录条数
     */
    static public function checkUserByToken($token)
    {
        $sql = "select 
                  count(*) 
                from admin_session 
                where 
                  token = '{$token}' and 
                  expire_time > now()
                  ";

        return Flight::db()->getRow($sql);
    }

    /**
     * @param $user_name 登录用户名称
     * @param $pass_word 登录用户密码
     * @return mixed    返回符合条件的用户记录
     */
    static public function getUserInfoByNamePassWord($user_name, $pass_word)
    {
        $sql = "select admin_user_id,
                       admin_user_name 
                from test_admin 
                where admin_user_name = '{$user_name}' and 
                      pass_word = '{$pass_word}'
                ";

        return Flight::db()->getAll($sql);
    }

    /**
     * @param $user_id 用户id
     * @param $token   用户登录验证成功时生成的token
     * @return mixed   返回sql执行的结果
     */
    static public function saveToken($user_id, $token)
    {
        $ip = Flight::request()->ip;

        $sql = "insert into admin_session (
                            user_id,
                            token,
                            expire_time,
                            created_time,
                            last_updated_time,
                            ip
                            ) 
                 values(
                       {$user_id},
                       '{$token}',
                       date_add(now(), INTERVAL 6 HOUR),
                       now(),
                       now(),
                       '{$ip}'
                 )";

        return Flight::db()->exec($sql);
    }
}