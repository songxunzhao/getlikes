<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 10/1/2016
 * Time: 10:20 PM
 */
namespace App\Controller;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \InstagramAPI\Instagram;
use App\Helpers\GLCache;
use App\QB;

class Media {
    public static function list_one_for_like ( Request $req, Response $res ) {
        $instagram = NULL;
        $user = $req->getAttribute('user');

        $num_rows = QB::table('users')->count();
        if($num_rows <= 1)
        {
            return $res->withJson([
                'success'   => false,
                'message'   => "Next media doesn't exist"
            ]);
        }
        else {
            $random_offset = rand(0, $num_rows - 2);
            $candidate_user = QB::table('users')->where('id', '!=', $user->id)->limit(1)->offset($random_offset)->first();

            $media_ids  = GLCache::get_cache_data($user, 1);
            if($media_ids === false)
            {
                $instagram  = new Instagram($user->username, $user->password);
                $instagram->login();
                $media_list = $instagram->getLikedMedia();
                $media_ids  = [];
                if($media_list['status'] == 'ok')
                {
                    foreach($media_list['items'] as $item)
                    {
                        if($item['media_type'] == 1){
                            $media_ids[] =  $item['id'];
                        }
                    }
                    GLCache::cache_data($user, 1, $media_ids);
                }
            }

            $feed_media_list = GLCache::get_cache_data($candidate_user, 2);
            if($feed_media_list === false)
            {
                $feed_media_list = [];
                if($instagram == NULL) {
                    $instagram = new Instagram($user->username, $user->password);
                    $instagram->login();
                }
                $media = $instagram->getUserFeed($candidate_user->instagram_user_id);
                $items = $media->getItems();
                foreach($items as $item) {
                    if($item->isPhoto())
                    {
                        $feed_media_list[] = $item->getMediaId();
                    }
                }
                GLCache::cache_data($candidate_user, 2, $feed_media_list);
            }
//
            foreach($feed_media_list as $feed_media_id)
            {
                if(!in_array($feed_media_id, $media_ids)){
                    return $res->withJson([
                        'success'   => true,
                        'data'      => [
                            'media_id'  => $feed_media_id
                        ]
                    ]);
                }
            }
        }
        return $res->withJson([
            'success'   => false,
            'message'   => 'Feed was not found'
        ]);
    }
    public static function liked_one ( Request $req, Response $res ) {

        $user = $req->getAttribute('user');
        $parsedBody = $req->getParsedBody();

        $sql = 'UPDATE gl_users SET coin = coin + 1 WHERE id = ?;';
        $pdo = QB::pdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user->id]);
        return $res->withJson([
            'success'   => true,
            'message'   => 'Added coin'
        ]);
    }
}