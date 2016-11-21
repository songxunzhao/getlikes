<?php
namespace App\Helpers;

class InstagramJson {
    public static function hdProfilePicUrlInfo2Json(\InstagramAPI\HdProfilePicUrlInfo $obj) {
        $json = [
            'url'   => $obj->getUrl(),
            'width' => $obj->getWidth(),
            'height'=> $obj->getHeight()
        ];
        return $json;
    }
    public static function commentToJson(\InstagramAPI\Comment $obj) {
        $json = [
            'media_id'  => $obj->getMediaId(),
            'comment'   => $obj->getComment(),
            'comment_id'=> $obj->getCommentId()
        ];
        return $json;
    }
}
?>
