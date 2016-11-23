<?php
namespace App\Helpers;

use InstagramAPI\Caption;

class InstagramJson {
    public static function hdProfilePicUrlInfo2Json(\InstagramAPI\HdProfilePicUrlInfo $obj) {
        $json = [
            'url'   => $obj->getUrl(),
            'width' => $obj->getWidth(),
            'height'=> $obj->getHeight()
        ];
        return $json;
    }
    public static function comment2Json(\InstagramAPI\Comment $obj) {
        $json = [
            'media_id'  => $obj->getMediaId(),
            'comment'   => $obj->getComment(),
            'comment_id'=> $obj->getCommentId()
        ];
        return $json;
    }
    public static function caption2Json(Caption $obj) {
        $json = [
            'content_type'  => $obj->getContentType(),
            'text'          => $obj->getText(),
            'user_id'       => $obj->getUserId()
        ];

        return $json;
    }
}
?>
