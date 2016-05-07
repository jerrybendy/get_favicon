<?php
/**
 * 演示文件
 *
 * #注# 此文件仅做功能演示使用,
 * 因未做任何数据验证等功能,
 * 切不可直接放到线上使用
 *
 * Created by PhpStorm.
 * User: jerry
 * Date: 16/5/7
 * Time: 16:08
 */


require "../vendor/autoload.php";

$favicon = new \Jerrybendy\Favicon\Favicon;


/**
 * 检测URL参数
 */
$url = $_GET['url'];


/*
 * 格式化 URL, 并尝试读取缓存
 */
$formatUrl = $favicon->formatUrl($url);

if (Cache::get($formatUrl) !== NULL) {

    foreach ($favicon->getHeader() as $header) {
        @header($header);
    }

    echo Cache::get($formatUrl);
    exit;
}

/**
 * 缓存中没有指定的内容时, 重新获取内容并缓存起来
 */
$content = $favicon->getFavicon($formatUrl, TRUE);

Cache::set($formatUrl, $content, 86400);

foreach ($favicon->getHeader() as $header) {
    @header($header);
}

echo $content;
exit;



/**
 * 定义一个虚拟的缓存类,
 * 请自行实现缓存方法
 */
class Cache
{
    /**
     * 获取缓存的值, 不存在时返回 null
     *
     * @param $key
     * @return string
     */
    public static function get($key)
    {
        return null;
    }

    /**
     * 设置缓存
     *
     * @param $key
     * @param $value
     * @param $expire
     */
    public static function set($key, $value, $expire)
    {

    }

}