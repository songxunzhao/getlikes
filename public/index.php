<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 9/29/2016
 * Time: 2:06 AM
 */
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// app controllers
use App\Middleware\Authentication;

use App\Controller\Account;
use App\Controller\Media;
use App\Controller\Misc;
use App\Controller\Order;

use \Pixie\Connection;

require '../vendor/autoload.php';
require '../config.php';

require '../helpers/random_strings.php';
require '../helpers/glcache.php';
require '../helpers/instagram_json.php';

require '../middleware/authentication.php';

require '../controllers/account.php';
require '../controllers/media.php';
require '../controllers/misc.php';
require '../controllers/order.php';

error_reporting(E_ALL);

// db initialization
$db_config = include('../config.php');
$config = array(
    'driver'    => 'mysql', // Db driver
    'host'      => $db_config['DB_HOST'],
    'database'  => $db_config['DB_NAME'],
    'username'  => $db_config['DB_USER'],
    'password'  => $db_config['DB_PASS'],
    'charset'   => 'utf8', // Optional
    'collation' => 'utf8_unicode_ci', // Optional
    'prefix'    => 'gl_', // Table prefix, optional
);
new \Pixie\Connection('mysql', $config, 'App\QB');

$slim_config = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$container = new \Slim\Container($slim_config);
$app = new \Slim\App($container);

$app->post ( '/account/register', 'App\Controller\Account::register');
$app->get ( '/misc/adcolony', 'App\Controller\Misc::adcolony' );
$app->get ( '/misc/server_test', 'App\Controller\Misc::server_test' );

// Authentication group
$app->group('',  function () use($app) {
    $app->post ( '/account/add_coin',           'App\Controller\Account::add_coin'          );
    $app->post ( '/media/list_one_for_like',    'App\Controller\Media::list_one_for_like'   );
    $app->post ( '/media/liked_one',            'App\Controller\Media::liked_one'           );
    $app->post ( '/order/create',               'App\Controller\Order::create'              );
    $app->post ( '/order/process',              'App\Controller\Order::process'             );
    $app->post ( '/order/processed_one',        'App\Controller\Order::processed_one'       );
    $app->post ( '/misc/verify_purchase',       'App\Controller\Misc::verify_purchase'      );
})->add(new Authentication());

$app->run();