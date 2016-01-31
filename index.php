<?php
/**
 * 演示文件
 *
 * #注# 此文件仅做功能演示使用,
 * 因未做任何数据验证/缓存等功能,
 * 切不可直接放到线上使用
 *
 * User: jerry
 * Date: 2015/8/16
 * Time: 8:04
 */

require dirname(__FILE__) . '/Jerrybendy/Favicon/Favicon.php';


$favicon = new \Jerrybendy\Favicon();


/**
 * 检测URL参数
 */
$url = $_GET['url'];


/**
 * 获取图标并显示在浏览器上
 */
$favicon->get_favicon($url, FALSE);
