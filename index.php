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

 echo $favicon->get_favicon($url,false);


//-------------------------------------------------------------------------------------------------

/**
 * 把当前的内容保存到缓存中
 */
function _save_data_into_cache(){
    //保存新数据
    return $this->_cache->save($this->cache_key, $this->data, $this->params['expire']);
}

//-------------------------------------------------------------------------------------------------

/**
 * 从memcache缓存中获取图标资源
 * 并在成功获取后设置$this->data的值,并返回TRUE
 * 否则返回FALSE
 * @return boolean
 */
function _get_data_from_cache(){
    //从缓存中获取保存的内容
    $data = $this->_cache->get($this->cache_key);
    if($data){
        $this->data = $data;
        return TRUE;
    } else{
        if($this->cache_key != $this->full_host){
            //在找不到指定的图标缓存时尝试获取原始图标的缓存副本
            //但是这里取得的缓存内容可能需要加以处理才可以使用
            $data = $this->_cache->get($this->full_host);
            if($data){
                $this->data = $data;

                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
}