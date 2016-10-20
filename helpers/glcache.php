<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 10/5/2016
 * Time: 11:15 PM
 */
namespace App\Helpers;

use App\QB;
class GLCache {
    public static function cache_data($user, $typecode, $data)
    {
        $sql =
            'INSERT INTO `gl_user_cache_data`
              (`user_id`, `data_type`, `data`, `created_on`, `modified_on`)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE data=?, modified_on=NOW();';
        $pdo = QB::pdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user->id,
            $typecode,
            json_encode($data),
            json_encode($data)
        ]);
    }
    public static function get_cache_data($user, $typecode)
    {
        $record = QB::table(
            'user_cache_data'
        )->where(
            'user_id', '=', $user->id
        )->where(
            'data_type', '=', $typecode
        )->where(
            QB::raw('modified_on < DATE_ADD(NOW(), INTERVAL 1 DAY)')
        )->first();

        if($record)
        {
            return json_decode($record->data);
        }
        else{
            return false;
        }
    }

}