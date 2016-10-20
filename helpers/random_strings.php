<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 10/4/2016
 * Time: 2:47 AM
 */
namespace App\Helpers;

class RandomString {
    public static function generate($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
