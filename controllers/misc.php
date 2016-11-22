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

class Misc {
    public static function server_test( Request $req, Response $res ) {
//        return $res->withJson([]);
        return $res->write('Hello World!');
    }

    public static function verify_purchase ( Request $req, Response $res ) {

        $user = $req->getAttribute('user');
        $product_ids = array(
            "com.dakyuz.100coins" => 100,
            "com.dakyuz.250coins" => 250,
            "com.dakyuz.1000coins" => 1000,
            "com.dakyuz.2000coins" => 2000,
            "com.dakyuz.5000coins" => 5000,
            "com.dakyuz.10000coin" => 10000
        );
        $parsedBody = $req->getParsedBody();

        $receiptData    = $parsedBody['receipt-data'];
        $mode           = $parsedBody['mode'];
        $mode           = 1;	//Always Product mode. if set to 0, sandbox mode is activated.

        global $REST_RESPONSE;
        $res = $REST_RESPONSE;

        $postData = array(
            "receipt-data" => $receiptData
        );

        $ch = curl_init();
        if(!isset($mode) || $mode == 1)
            curl_setopt($ch, CURLOPT_URL, VERIFY_PRODUCT);
        else
            curl_setopt($ch, CURLOPT_URL, VERIFY_SANDBOX);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        $response = curl_exec($ch);
        curl_close($ch);
        $res_json = json_decode($response);
        if($res_json->status == 0)
        {
            $bPurchased = false;
            {
                $coin_cnt = $product_ids[$res_json->receipt->product_id];
                if(isset($coin_cnt))
                {
                    $pdo = QB::pdo();
                    $sql = "UPDATE gl_users SET coin = coin + ? WHERE id = ?;";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$coin_cnt, $user->id]);
                    $bPurchased = true;
                }
            }
            if($bPurchased)
            {
                return $res->withJson([
                    'success'   => true,
                    'data'      => [
                        'id'        => $user->id,
                        'username'  => $user->username,
                        'password'  => $user->password
                    ]
                ]);
            }
            else
            {
                return $res->withJson([
                    'success'   => false,
                    'data'      => $res_json,
                    'message'   => 'Not valid product'
                ]);
            }
        }
        else
        {
            return $res->withJson([
                'success'   => false,
                'data'      => $res_json,
                'message'   => 'Transaction is not valid'
            ]);
        }
    }

    public static function adcolony(Request $req, Response $res) {
        $SECRET_KEY = "v4vc91ea018f2c674396ac";
        $trans_id = mysql2::escape($_GET['id']);
        $dev_id = mysql2::escape($_GET['uid']);
        $amt = mysql2::escape($_GET['amount']);
        $currency = mysql2::escape($_GET['currency']);
        $open_udid = mysql2::escape($_GET['open_udid']);
        $udid = mysql2::escape($_GET['udid']);
        $odin1 = mysql2::escape($_GET['odin1']);
        $mac_sha1 = mysql2::escape($_GET['mac_sha1']);
        $custom_id = mysql2::escape($_GET['custom_id']);
        $verifier = mysql2::escape($_GET['verifier']);

        $test_string = "" . $trans_id . $dev_id . $amt . $currency . $SECRET_KEY .
            $open_udid . $udid . $odin1 . $mac_sha1 . $custom_id;
        $test_result = md5($test_string);
        if($test_result != $verifier) {
            $res->getBody()->write("vc_noreward");
            return;
        }


        $username = $custom_id;
        if($username == "")
        {
            $res->getBody()->write("vc_noreward");
            return;
        }
        //check for a valid user
        $user = QB::table('users')->where('username', '=', $username)->first();
        if(!$user)
        {
            $res->getBody()->write("vc_noreward");
            return;
        }

        //insert the new transaction

        $insertId = QB::table('adcolony_transactions')->insert(
            [
                'id'        => $trans_id,
                'amount'    => $amt,
                'username'  => $username,
                'time'      => date('Y-m-d H:i:s')
            ]
        );

        $pdo = QB::pdo();
        if(!$insertId) {
            //check for duplicate on insertion. Transaction is valid but it is duplicated. don't process the transaction!

            if($pdo->errorCode() == 1062) {
                $res->getBody()->write('vc_success');
                return;
            }
            //otherwise insert failed and AdColony should retry later
            else {
                $res->getBody()->write('mysql error number');
                $res->getBody()->write("mysql error number" . $pdo->errorCode());
                die;
            }

        }

        //TODO: award the user the appropriate amount and type of currency here
        QB::table('users')->where('id', '=', $user->id)->update(['coin' => $user->coin + $amt]);
        echo "vc_success";
    }
}