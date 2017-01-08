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
                from admin 
                where 
                  token = '{$token}'";

        return Flight::db()->getRow($sql);
    }
}