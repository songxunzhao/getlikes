<?php
    require_once('./vendor/autoload.php');
    require_once('./lib/config.php');
    require_once('./lib/mysql.php');
    require_once('./lib/mysql2.php');
    require_once('./lib/orm.php');
    require_once('./lib/func.php');

    //Error codes
    define('INVALID_TOKEN', 400);
    define('NOT_ALLOWED', 401);
    define('INVALID_PARAM', 402);
    define('NORESULT', 403);
    define('DATABASE_ERROR', 404);
    define('NOT_ENOUGH_COIN', 405);
    define('REACH_LIMIT', 406);
    define('SUCCESS', 200);

    //Limit
    define('LIKE_LIMIT', 15);

    //Define actions.
    define('LIKE_ACTION', 1);

    $REST_RESPONSE = array(
        'IsSuccess' => false,
        'Message' => '',
        'Response' => ''
    );

    $api = $_GET['c'];
    switch($api)
    {

        case 'login':
            login();
            break;
        case 'like':
            like();
        case 'get_candidate':
            get_candidate();

        case 'getPhotoCandidate':
            api_getPhotoCandidate();
            break;
        case 'processOrder' :
            processOrder();
            break;
        case 'madeOrder' :
            madeOrder();
            break;

        case 'addCoin' :
            addCoin();
            break;
        case 'serverTest' :
            serverTest();
            break;
        case 'verifyPurchase':
            verifyPurchase();
            break;
        case 'likedPhoto':
            likedPhoto();
            break;
    }

    function processOrder()
    {
        global $REST_RESPONSE;

        $response = $REST_RESPONSE;
        $user_id = $_POST['userid'];
        $token = $_POST['token'];

        $db = new mysql2();
        $db->connectDB();

        $prd_order = $_POST['processed_id'];
        $prd_amount = $_POST['processed_amount'];

        if(isset($prd_order))
        {
            //get current status of order.
            $e_prd_order = mysql2::escapeWithQuote($prd_order);
            $query = "select * from orders where `order_id` = $e_prd_order";
            $qres = $db->queryForResult($query);
            while($row = $db->fetchArray($qres))
            {
                if(intval($row['order_amount']) <= $prd_amount)
                {
                    //remove order
                    $query = "delete from orders where `order_id` = $e_prd_order";
                    $db->queryForResult($query);
                }
                else
                {
                    //update order
                    $remaining = intval($row['order_amount']) - $prd_amount;
                    $query = "update orders set `order_amount` = $remaining where `order_id` = $e_prd_order";
                    $db->queryForResult($query);
                }
                break;
            }
        }

        //Check user's validity and prepare new order.
        $e_user_id = mysql2::escapeWithQuote($user_id);
        $uinfo = _getUserInfo($user_id);
        if($uinfo['access_token'] != $token)
        {
            $response['IsSuccess'] = false;
            $response['Code'] = INVALID_TOKEN;
            $response['Message'] = "Access token is not valid";
            _renderJSON($response);
            return;
        }
        $bPrivateUser = false;
        if($uinfo['private_user'] == 0)
        {
            if(_isUserPrivate($user_id) == 1)
            {
                $query = "update users set private_user = 1 where user_id = $e_user_id";
                $db->queryForResult($query);
                $bPrivateUser = true;
            }
        }
        else
            $bPrivateUser = true;

        if($bPrivateUser)
        {
            $response['IsSuccess'] = false;
            $response['Message'] = "User is private, can't promote his photos";
            $response['Code'] = NOT_ALLOWED;
            _renderJSON($response);
            return;
        }

        //Pick one order from table.
        $query = "select * from orders where `user_id` = $e_user_id limit 1";
        $qres = $db->queryForResult($query);

        $oamount = 0;
        while($row = $db->fetchArray($qres))
        {
            $oamount = $row['order_amount'];
            $orderid = $row['order_id'];
            $photo_id = $row['photo_id'];
            break;
        }

        if($oamount == 0)
        {
            $response['IsSuccess'] = false;
            $response['Message'] = "Order was not found for user";
            $response['Code'] = NORESULT;
            echo json_encode($response);
            return;
        }
        else if($oamount > 40)
            $oamount = 40;	//Limit traffic load.

        $mem_key = "likes" . $user_id . "ord";
        $mc = new Memcached();
        $mc->addServer("localhost",11211);
        $idx = $mc->get($mem_key);
        if($idx == false)
        {
            $idx = 0;
        }
        $e_photo_id = mysql2::escapeWithQuote($photo_id);
        $like_limit = LIKE_LIMIT;
        $where = " access_token IS NOT NULL and action_count < $like_limit "
            . " and not exists(select * from liked where `user_id` = users.user_id and `photo_id` = $e_photo_id)";

        $query = "select * from users where" . $where;
        $query = $query . " limit $idx, $oamount";

        $candidates = array();
        $qres = $db->queryForResult($query);
        while($row = mysql2::fetchArray($qres))
        {
            $candidates[] = array(
            "user_id" => $row["user_id"],
            "access_token" => $row["access_token"]
            );
            $idx ++;
        }
        if(count($candidates) == 0) 	//No candidate was found, current index is invalid.
            $idx = 0;
        $mc->set($mem_key, $idx);

        $response['IsSuccess'] = true;
        $response['Code'] = SUCCESS;
        $response['Response'] = array(
            'order_id' => $orderid,
            'photo_id' => $photo_id,
            'candidates' => $candidates
        );
        _renderJSON($response);
    }


    function madeOrder()
    {
        global $REST_RESPONSE;

        $response = $REST_RESPONSE;

        $user_id = $_POST['userid'];
        $token = $_POST['token'];
        $oamount = $_POST['order_amount'];
        $photo_id = $_POST['photoid'];

        $coin_cnt = intval($oamount) * 2;

        $u_info = _getUserInfo($user_id);
        $e_user_id = mysql2::escapeWithQuote($user_id);
        $e_photo_id = mysql2::escapeWithQuote($photo_id);

        if($u_info['access_token'] != $token)
        {
            //Not valid token provided. Maybe malicious user.
            $response['IsSuccess'] = false;
            $response['Code'] = INVALID_TOKEN;
            $response['Message'] = "Access token is not valid";

            _renderJSON($response);
            return;
        }
        if($u_info['private_user'] == 1)
        {
            $response['IsSuccess'] = false;
            $response['Code'] = NOT_ALLOWED;
            $response['Message'] = "User is private, can't promote his photos";

            _renderJSON($response);
            return;
        }

        if($u_info['cur_coin'] < $coin_cnt)
        {
            $response['IsSuccess'] = false;
            $response['Code'] = NOT_ENOUGH_COIN;
            $response['Message'] = "Not enough coin to process order";

            _renderJSON($response);
            return;
        }
        if(!isset($oamount) || $oamount == 0)
        {
            $response['IsSuccess'] = false;
            $response['Message'] = "Please specify valid amount of order";
            $response['Code'] = INVALID_PARAM;
            _renderJSON($response);
            return;
        }
        $db = new mysql2();
        $db->connectDB();

        $where = " and not exists(select * from liked where `user_id` = `users`.`user_id` and `photo_id` = $e_photo_id)";
        $query = "select count(*) from users where 1" . $where;

        $qres = $db->queryForResult($query);
        $avail_likes = 0;
        while($row = $db->fetchArray($qres))
        {
            $avail_likes = $row[0];
            break;
        }
        if($oamount > 25 && $avail_likes < $oamount)
        {
            $response['IsSuccess'] = false;
            $response['Message'] = "This service is not available now, please try again later";
            $response['Response'] = REACH_LIMIT;
            _renderJSON($response);
            return;
        }

        //Register order

        $query = "insert into orders (`user_id`, `photo_id`, `order_amount`) values($e_user_id, $e_photo_id, $oamount)";
        $db->queryForResult($query);

        $query = "update users set cur_coin = cur_coin - $coin_cnt where user_id = $e_user_id";
        $db->queryForResult($query);
        $db->closeDB();

        $response['IsSuccess'] = true;
        $response['Message'] = 'Order amount was processed';
        $response['Response'] = _getUserInfo($user_id);
        $response['Code'] = SUCCESS;
        _renderJSON($response);
    }

    function getPhotoCandidate()
    {
        /*
            TODO:
            1.get liked photos of user.
            2.get candidate from db where photo is not in liked list.
            3.check validation of photo id.
        */

        global $REST_RESPONSE;
        $response = $REST_RESPONSE;

        $response['IsSuccess'] = false;
        $response['Message'] = "Photo was not found";

        $user_id = $_POST['userid'];
        $token = $_POST['token'];

        $uinfo = _getUserInfo($user_id);
        if(!$uinfo || $uinfo['access_token'] != $token)
        {
            $response['Code'] = INVALID_TOKEN;
            $response['Message'] = "Access token is not valid";
            _renderJSON($response);
            return;
        }

        $db = new mysql2();
        $db->connectDB();

        /*
        * Calculate like limit per hour to warn user about current calling cycle.
        */
        $e_user_id = mysql2::escapeWithQuote($user_id);
        $number_call = $uinfo['action_count'];

        /**********************************/
        $mem_key = "likes" . $user_id . "cand";
        $mc = new Memcached();
        $mc->addServer("localhost",11211);
        $idx = $mc->get($mem_key);
        if($idx == false)
            $idx = 0;

        $query = "select * from pub_photos where not exists(select * from `liked` where `user_id` = $e_user_id and `photo_id` = pub_photos.photo_id)";

        while(true)
        {
            $l_query = $query . " limit $idx, 5";
            $qres = $db->queryForResult($l_query);

            while($row = mysql2::fetchArray($qres))
            {
                $api = INSTAGRAM_API . "/media/{$row['photo_id']}?access_token=$token";
                $inst_res = _callInstagramAPI($api);
                if($inst_res->meta->code == 200)
                {
                    $response['IsSuccess'] = true;
                    $response['Code'] = SUCCESS;
                    $response['Message'] = "Photo candidate was found";
                    $response['Response'] = array(
                        'photo_data' => $inst_res,
                        'number_call' => $number_call
                    );

                    break;
                }
                else if($inst_res->meta->error_type == "APINotFoundError" || $inst_res->meta->error_type == "APINotAllowedError" )	//This response comes from Instagram when photo id was not found
                {
                    //Remove photo from list.
                    $rm_query  = "delete from pub_photos where photo_id = '{$row['photo_id']}'";
                    $db->queryForResult($rm_query);
                }
                else
                {
                    $response['Code'] = INVALID_TOKEN;
                    $response['Message'] = "Access token is not valid";
                    _rendeJSON($response);
                    return;
                }
            }

            if($response['IsSuccess'] == true)
            {
                $idx = $idx + rand(1,5);
                $mc->set($mem_key, $idx);
                break;
            }
            else if(mysql2::getNumRows($qres) == 0)
            {
                if($idx == 0)
                {
                    break;
                }
                else
                {
                    $idx  = 0;
                }

                $mc->set($mem_key, 0);
            }
        }
        _renderJSON($response);
    }

    function verifyPurchase()
    {
        $product_ids = array(
            "com.dakyuz.100coins" => 100,
            "com.dakyuz.250coins" => 250,
            "com.dakyuz.1000coins" => 1000,
            "com.dakyuz.2000coins" => 2000,
            "com.dakyuz.5000coins" => 5000,
            "com.dakyuz.10000coin" => 10000
        );

        $user_id = $_POST['userid'];
        $receiptData = $_POST['receipt-data'];
        $mode = $_POST['mode'];
        $mode = 1;	//Always Product mode. if set to 0, sandbox mode is activated.

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
                    $db = new mysql2();
                    $db->connectDB();
                    $e_user_id = mysql2::escapeWithQuote($user_id);
                    $query = "update users set cur_coin=cur_coin + $coin_cnt where user_id = $e_user_id";
                    $db->queryForResult($query);
                    $db->closeDB();

                    $bPurchased = true;

                    $res['Message'] = "Successfully added coin";
                }
            }
            if($bPurchased)
            {
                $res['IsSuccess'] = true;
                $res['Code'] = SUCCESS;
                $res['Response'] = _getUserInfo($user_id);
            }
            else
            {
                $res['IsSuccess'] = false;
                $res['Code'] = INVALID_PARAM;
                $res['Message'] = "Not valid product";
                $res['Response'] = $res_json;
            }
        }
        else
        {
            $res['IsSuccess'] = false;
            $res['Code'] = INVALID_PARAM;
            $res['Message'] = "Transaction is not valid";
            $res['Response'] = $res_json;
        }
        _renderJSON($res);
    }

    function likedPhoto(){
        global $REST_RESPONSE;
        $res = $REST_RESPONSE;
        $user_id = $_POST['userid'];
        $photo_id = $_POST['photoid'];
        $bSuccess = $_POST['success'];
    //	$bSuccess = 1;

        _saveActionHistory($user_id, $photo_id, LIKE_ACTION);
        $e_user_id = mysql2::escapeWithQuote($user_id);
        $e_photo_id = mysql2::escapeWithQuote($photo_id);

        $db = new mysql2();
        $db->connectDB();

        //Record not exist
        if(isset($bSuccess) && $bSuccess == 1)
        {
            $query = "select * from liked where user_id = $e_user_id and photo_id = $e_photo_id";
            $qres = $db->queryForResult($query);
            $row = $db->fetchArray($qres);

            if(count($row) == 0)
            {
                $query = "insert into `liked` (`user_id`, `photo_id`) values ($e_user_id, $e_photo_id)";
                $db->queryForResult($query);
            }
        }
        $db->closeDB();
        $res['IsSuccess'] = true;
        _renderJSON($res);
    }
    //Add specified amount of coin.
    function addCoin()
    {
        global $REST_RESPONSE;
        $user_id = $_POST['userid'];
        $token = $_POST['token'];
        $add_count = $_POST['coin'];
        $res = $REST_RESPONSE;

        if(!_checkAccessToken($user_id, $token))
        {
            $res['IsSuccess'] = false;
            $res['Code'] = INVALID_TOKEN;
            $res['Message'] = "Access Token is not valid";
            _renderJSON($res);
            return;
        }

        $db = new mysql2();
        $db->connectDB();

        $query = "update users set cur_coin=cur_coin + $add_count where user_id = $user_id";
        $db->queryForResult($query);
        $db->closeDB();


        $res['IsSuccess'] = true;
        $res['Code'] = SUCCESS;
        $res['Message'] = "Successfully added $add_count coin";
        $res['Response'] = _getUserInfo($user_id);

        _renderJSON($res);
    }

    function login()
    {
        global $REST_RESPONSE;
        $res = $REST_RESPONSE;

        //TODO : follow client; need client info



        _renderJSON($res);
    }
    function _isUserPrivate($userid)
    {
        $mem_key="liked" . "login";
        $mc = new Memcached();
        $mc->addServer("localhost",11211);
        $idx = $mc->get($mem_key);
        if($idx == false)
            $idx = 0;

        $db = new mysql2();
        $db->connectDB();

        $e_userid = mysql2::escapeWithQuote($userid);

        while(true)
        {
            $query = "select * from users where access_token is not null and user_id != $e_userid limit $idx,1";
            $qres = $db->queryForResult($query);
            if($row = $db->fetchArray($qres))
            {
                $idx ++;
                $mc->set($mem_key, $idx);
                $inst_api = INSTAGRAM_API . "/users/$userid/?access_token={$row['access_token']}";
                $response = _callInstagramAPI($inst_api);

                if(!$response)
                {
                    return 1;
                }
                else if($response->meta->code != 200)
                {
                    if($response->meta->error_type == "APINotAllowedError")
                    {
                        return 1;
                    }
                }
                else
                {
                    return 0;
                }
            }
            else if($idx == 0)
            {
                return 0;
            }
            else
            {
                $idx = 0;
                $mc->set($mem_key, $idx);
            }
        }
        $db->closeDB();
    }
    function _checkAccessToken($user_id, $token)
    {
        $inst_api = INSTAGRAM_API . "/users/$user_id?access_token=$token";
        $inst_res = _callInstagramAPI($inst_api);
        if($inst_res->meta->code == 200)
            return true;
        return false;
    }
    function _getUserInfo($user_id)
    {
        $e_user_id = mysql2::escapeWithQuote($user_id);
        $query = "select * from users where user_id = $e_user_id";
        $result = mysql2::queryWithConnection($query);
        $result = mysql2::fetchArray($result);
        if($result['cur_coin'] < 0)
        {
            $result['cur_coin'] = '0';
        }
        return $result;
    }

    function serverTest()
    {
        echo "ServerTest";
    }

    function _renderJSON($data)
    {
        global $api;
        echo json_encode($data);
        _saveCallHistory($_POST, $data, $api);
    }
?>