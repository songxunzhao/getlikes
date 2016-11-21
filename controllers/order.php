<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 10/1/2016
 * Time: 10:19 PM
 */
namespace App\Controller;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use App\QB;
use App\Helpers\GLCache;

use \InstagramAPI\Instagram;
class Order {
    public static function create( Request $req, Response $res ) {

        $user = $req->getAttribute('user');
        $is_private     = GLCache::get_cache_data($user, 3);

        if($is_private)
        {
            return $res->withJson([
                'success' => false,
                'message' => 'Your account is private. Please make it public'
            ]);
        }

        //TODO: Create order
        $parsed_body = $req->getParsedBody();
        $coin_needed = intval($parsed_body['amount']) * 2;
        if($user->coin < $coin_needed)
        {
            return $res->withJson([
                'success'   => false,
                'message'   => 'Not enough coin'
            ]);
        }

        $user->coin -= $coin_needed;
        $user->save();

        $data = [
            'media_id'          => $parsed_body['media_id'],
            'user_id'           => $user->id,
            'amount'            => $parsed_body['amount'],
            'processed_amount'  => 0
        ];

        QB::table('orders')->insert($data);

        return $res->withJson([
            'success'   => true,
            'data'      => [
                'cur_coin' => $user->coin
            ]
        ]);
    }
    public static function process( Request $req, Response $res ) {

        $user = $req->getAttribute('user');
        $is_private = GLCache::get_cache_data($user, 3);

        $instagram  = new Instagram($user->username, $user->password);
        if($is_private === false)
        {
            $instagram->login();
            $profile    = $instagram->getProfileData();
            $is_private = $profile->isPrivate() ? 1 : 0;
            GLCache::cache_data($user, 3, $is_private);
        }

        if($is_private)
        {
            return $res->withJson([
                'success' => false,
                'message' => 'Your account is private. Please make it public'
            ]);
        }
        $order = QB::table(
            'orders'
        )->where(
            'user_id', '=', $user->id
        )->where(
            QB::raw('amount > processed_amount')
        )->first();

        if(!$order)
        {
            return $res->withJson([
                'success' => false,
                'message' => "Order was not found"
            ]);
        }

        $num_rows = QB::table('users')->count();
        if($num_rows <= 1)
        {
            return $res->withJson([
                'success'   => false,
                'message'   => "Candidate doesn't exist"
            ]);
        }
        else {
            $num_try = 2;
            while($num_try > 0) {
                $random_offset = rand(0, $num_rows - 2);
                $candidate_user = QB::table('users')->where('id', '!=', $user->id)->limit(1)->offset($random_offset)->first();
                $liked_media_list = GLCache::get_cache_data($candidate_user, 1);
                if ($liked_media_list === false) {
                    $instagram->setUser($candidate_user->username, $candidate_user->password);
                    $instagram->login();
                    $media = $instagram->getLikedMedia($order->media_id);

                    $liked_media_list = [];
                    if($media['status'] === 'ok') {
                        foreach ($media['items'] as $item) {
                            $liked_media_list[] = $item['id'];
                        }
                    }
                }

                if (!in_array($order->media_id, $liked_media_list)) {
                    return $res->withJson([
                        'success' => true,
                        'data' => [
                            'order' => [
                                'id'                => $order->id,
                                'media_id'          => $order->media_id,
                                'amount'            => $order->amount,
                                'processed_amount'  => $order->processed_amount
                            ],
                            'user' => [
                                'username' => $candidate_user->username,
                                'password' => $candidate_user->password
                            ]
                        ]
                    ]);
                }
                $num_try--;
            }
        }
        return $res->withJson([
            'success'   => false,
            'message'   => 'Candidate was not found'
        ]);
    }

    public static function processed_one( Request $req, Response $res ) {

        $user = $req->getAttribute('user');
        $parsedBody = $req->getParsedBody();

        $sql = 'UPDATE gl_orders SET processed_amount = processed_amount + 1 WHERE id = ?;';
        $pdo = QB::pdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$parsedBody['order_id']]);
        return $res->withJson([
            'success'   => true,
            'message'   => 'Order was processed'
        ]);
    }
}