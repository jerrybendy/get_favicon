<?php
/**
 * 入口文件
 *
 * User: jerry
 * Date: 2015/8/16
 * Time: 8:04
 */

require dirname(__FILE__) . '/Favicon/Favicon.php';


//error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

$favicon = new \WS\Favicon();

/**
 * 检测URL参数
 */
$url = $_GET['url'];


/**
 * 检测过期时间参数，如果过期时间被设置为任何小于10分钟的值，
 * 或无法解析成整数的值，默认会将过期时间改为10分钟
 */
$expire = isset($_GET['expire']) ? intval($_GET['expire']) : 864000;

if($expire < 600)
    $expire = 600;

$favicon->get_favicon($url, 60);