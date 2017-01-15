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

    static public function resetPassword($token, $pass_word)
    {
        $sql = "update test_admin ta
                inner join admin_session ass on ta.admin_user_id = ass.user_id
                set ta.pass_word = '{$pass_word}'
                where 
                    ass.token = '{$token}'
                ";

        return Flight::db()->exec($sql);
    }

    /**
     * @param array $data 获取数据记录的条件
     * @return array 返回符合条件的记录
     */
    static public function getUserInfo($data)
    {
        $sql = "select admin_user_id,
                       admin_user_name 
                from test_admin 
                where 1 
                ";

        if(isset($data['user_name'])) $sql .= " and admin_user_name = '{$data['user_name']}' ";
        if(isset($data['pass_word'])) $sql .= " and pass_word = '{$data['pass_word']}' ";
        if(isset($data['email'])) $sql .= " and email = '{$data['email']}' ";

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

    static public function getToken($user_id)
    {

        $sql = "select token 
                from admin_session
                where 
                    user_id = {$user_id}
                order by expire_time DESC 
                limit 1
                 ";

        return Flight::db()->getOne($sql);
    }
}