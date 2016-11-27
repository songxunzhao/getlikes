<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 10/1/2016
 * Time: 10:17 PM
 */
namespace App\Controller;

use \InstagramAPI\Instagram;

use Pixie\Exception;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use App\Helpers\RandomString;
use App\Helpers\GLCache;
use App\QB;

define('INIT_COIN', 60);

class Account {
    public static function register (Request $req, Response $res) {
        $parsed_body = $req->getParsedBody();
        $username = $parsed_body['username'];
        $password = $parsed_body['password'];

        try{
            $instagram = new Instagram($username, $password);
            $instagram->login();

            $profile        = $instagram->getProfileData();

            $data = [
                'username'              => $username,
                'password'              => $password,
                'instagram_user_id'     => $profile->getUsernameId(),
                'coin'                  => INIT_COIN,
                'membership'            => 0
            ];

            QB::table(
                'users'
            )->onDuplicateKeyUpdate(
                [
                    'instagram_user_id' => $data['instagram_user_id'],
                    'password'          => $password
                ]
            )->insert($data);

            $user = QB::table('users')->where('username', '=', $username)->first();

            // Save session
            $token = RandomString::generate(32);
            QB::transaction(function($qb) use($user, $token){
                //Expire all existing tokens
                $pdo = $qb->pdo();

                $sql = 'UPDATE gl_user_sessions SET `expires_at` = NOW() WHERE `expires_at` > NOW() AND user_id = ?;';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user->id]);

                $sql = 'INSERT INTO gl_user_sessions (`user_id`, `token`,`created_at`, `expires_at`) VALUES(?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY));';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user->id, md5($token)]);
            });

            // Cache account status
            $is_private = $profile->isPrivate() ? 1 : 0;
            GLCache::cache_data($user, 3, $is_private);

            // Check order exists
            $order = QB::table(
                'orders'
            )->where(
                'user_id', '=', $user->id
            )->where(
                QB::raw('amount > processed_amount')
            )->first();

            $res_data = [
                'success'   => true,
                'token'     => $token,
                'data'      => [
                    'user' => [
                        'id'            => $user->id,
                        'username'      => $user->username,
                        'coin'          => $user->coin,
                        'membership'    => $user->membership
                    ],
                    'order_exists' => $order ? true : false,
                    'rated' => false
                ],
            ];
        }
        catch(Exception $ex) {
            $res_data = [
                'success' => false,
                'message' => 'Instagram authentication was failed'
            ];
        }
//        var_dump($instagram->explore()->getItems());
        return $res->withJson($res_data);
    }

    public static function add_coin (Request $req, Response $res) {

        $user = $req->getAttribute('user');
        $parsed_body = $req->getParsedBody();

        $coin = $parsed_body['coin'];
        $user->coin = $user->coin + $coin;

        $query = 'UPDATE `gl_users` SET `coin` = ? WHERE `id` = ?';
        $pdo = QB::pdo();
        $stmt = $pdo->prepare($query);
        $stmt->execute([ $user->coin, $user->id ]);

        $res_data = [
            'success' => true,
            'data' => [
                'id'        => $user->id,
                'username'  => $user->username,
                'coin'      => $user->coin,
                'membership'=> $user->membership
            ]
        ];

        return $res->withJson($res_data);
    }
};