<?php
require_once('./lib/config.php');
require_once('./lib/mysql.php');
require_once('./lib/mysql2.php');
require_once('./lib/orm.php');
require_once('./lib/func.php');

$db = new mysql2();
$db->connectDB();

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
    echo "vc_noreward";
    die;
}


$user_id = $custom_id;
if($user_id == "")
{
    echo "vc_noreward";
    die;
}
//check for a valid user
$query = "select * from users where user_id = $user_id limit 1";

$qres = $db->queryForResult($query);
$row = $db->fetchArray($qres);
if(!$row)
{
    $db->closeDB();
    echo "vc_noreward";
    die;
}

//insert the new transaction
$query = "INSERT INTO adcolony_transactions(id, amount, user_id, time) ".
    "VALUES ($trans_id, $amt, $user_id, UTC_TIMESTAMP())";

$result = $db->queryForResult_($query);
if(!$result) {
    //check for duplicate on insertion. Transaction is valid but it is duplicated. don't process the transaction!

    if($db->getLastErrorNo() == 1062) {
        echo "vc_success";
        die;
    }
    //otherwise insert failed and AdColony should retry later
    else {
        echo "mysql error number".$db->getLastErrorNo();
        die;
    }

}

//TODO: award the user the appropriate amount and type of currency here
$query = "update users set cur_coin = cur_coin + $amt where user_id = $user_id";
$db->queryForResult($query);
$db->closeDB();
echo "vc_success";
?>