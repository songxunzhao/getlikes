<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 9/29/2016
 * Time: 1:15 AM
 */
define('FILE_DB_DEPLOY', './db_deploy.rc');

class DBInstaller
{

    public function run($db_host, $db_user, $db_pass, $db_name)
    {
        $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        // works regardless of statements emulation
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);

        $db_deploy_file = file_get_contents(FILE_DB_DEPLOY);
        $sql_file_list = explode("\n", $db_deploy_file);

        foreach($sql_file_list as $path) {
            $path = trim($path);
            if($path === '')
                continue;

            $sql = file_get_contents($path, FILE_USE_INCLUDE_PATH);
            try {
                $db->exec($sql);
            }
            catch (PDOException $e)
            {
                echo $e->getMessage();
                die();
            }
        }
    }
}

$db_config = include('./config.php');
$db_installer = new DBInstaller();
$db_installer->run($db_config['DB_HOST'], $db_config['DB_USER'], $db_config['DB_PASS'], $db_config['DB_NAME']);
