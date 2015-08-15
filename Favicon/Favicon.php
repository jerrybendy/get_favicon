<?php
/**
 * 获取网站Favicon服务接口
 * @author 	Jerry Bendy (jerry@icewingcc.com)
 * @date    2014-09-10
 * @link 	http://blog.icewingcc.com
 * @version 2.0
 */

namespace WS;

// Load log class
require_once dirname(__FILE__) . 'Log.php';

// Load cache adapter
require_once dirname(__FILE__) . 'Cache.php';


/**
 * Favicon操作类
 *
 * ## 关于缓存:
 * 程序中同时使用文件缓存和Memcache缓存两种方案,程序获取图标时
 * ### Memcache缓存
 * 会优先从Memcache缓存中获取数据, 缓存Key的格式为 HOST[:PORT],
 * 带有尺寸信息的缓存格式为 HOST[:PORT]__{s|f}SIZE
 *  (尺寸的数字前面加两个下划线,还有一个表示size或fzoom的字母,即s或f)
 *  ### 文件缓存
 * 文件缓存用来永久保存那些被墙或打开速度奇慢的网站的图标,并在HOOK中维
 * 护一个这些网站的列表和映射关系
 *
 *
 * @author jerry
 *
 */

class Favicon {

    /**
     * 保存传入的参数,其中:
     * Save all input params, include:
     *
     * 	origin_url:  	 保存传入的url参数的原始字符串信息
     *                  (the origin input url)
     *  	expire:		项目的过期时间,经int转化后的expire参数
     *                  (Cache expired time)
     *
     *  以及一些额外的参数及暂存的数据
     *  and other extra params if needed
     */
    private $params = array();


    /**
     * 经parse_url解析后的URL数组
     * the url array after parse_url()
     */
    private $parsed_url = array();

    /**
     * 完整的形如  http://xxx.xxx.com:8888 这样的地址
     * the full host, such as http://xxx.xxx.com:8888
     */
    private $full_host = '';

    /**
     * 保存图标在缓存中保存的KEY的名称
     * 一般应等于full_host
     * cache KEY name of memcache or redis
     */
    private $cache_key = '';

    /**
     * 包含获取到的最终的二进制数据
     * the final binary icon data
     *
     */
    private $data = NULL;

    /**
     * cache
     */
    private $_cache;

    /**
     * 预定义的网址匹配与图标文件的映射关系
     */
    private static $_static_icon_list = array(
        // Google
        'play\.google\.com'							=> 'play.google.com.ico',
        'plus\.google\.com'							=> 'plus.google.com.png',
        'mail\.google\.com'							=> 'mail.google.com.ico',
        'books\.google\.com'						=> 'books.google.com.png',
        'drive\.google\.com'						=> 'drive.google.com.ico',
        'google\.com' 								=> 'google.com.ico',
        'goo\.gl'									=> 'goo.gl.ico',
        'youtube\.com'								=> 'youtube.com.ico',
        'blogger\.com'								=> 'blogger.com.ico',

        // Microsoft
        'bing\.com'									=> 'bing.com.ico',
        'live\.com'									=> 'live.com.ico',
        'twitter\.com'								=> 'twitter.com.ico',

        // Others
        'facebook\.com'								=> 'facebook.com.ico',
        'dropbox\.com'								=> 'dropbox.com.ico',
        'fliker\.com'								=> 'fliker.com.ico',
        'github\.com'								=> 'github.com.ico',
        'leagueoflegends\.com'						=> 'leagueoflegends.com.ico',
        'php\.net'									=> 'php.net.ico',
        'wikipedia\.org'							=> 'wikipedia.org.ico',
        'wordpress\.com'							=> 'wordpress.com.ico',

    );


    public function __construct(){
        $this->_cache = &Cache::get_instance();
    }


    /**
     * 获取网站Favicon并输出
     * Get websit favicon and output it
     *
     * @param string $url 输入的网址
     *                  (The input URL)
     * @param int $expire 缓存过期的时间，默认是10天
     *                  (Cache expire time, 10 days default)
     *
     */
    function get_favicon($url = '', $expire = 8640000){

        /**
         * 验证传入参数
         * Validate the input params
         */
        if( ! $url){
            trigger_error('\WS\Favicon: Url cannot be empty', E_ERROR);
        }

        //
        $this->params['origin_url'] = $url;

        //解析URL参数
        $ret = $this->parse_url_host($url);
        if(! $ret){
            trigger_error('WS\Favicon: Invalided url', E_WARNING);
        }


        //额外保存一份cache_key的值，用于被插件重写以获得准确的缓存数据
        $this->cache_key = $this->full_host;

        /**
         * 过期时间
         * 如果是通URL传参的形式设定过期时间，可能需要自行在入口函数
         * 内检测过期时间是否小于等于0（0值将意味着缓存永久有效）
         * 以及缓存时间是否过大
         *
         * expired time
         * if you let users set the expire by url params, may you will
         * need to check the expire time by youselves.
         * such as if expire time <= 0 ( 0 means cache will save forever),
         * and if expire time so long ~ (999 years or longer~ )
         *
         */
        $this->params['expire'] = $expire;


        /**
         * get the favicon bin data
         */
        $data = $this->get_data();

        /**
         * 设置输出Header信息
         * Output common header
         *
         * @since V2.1.4 2015-02-09
         */
        header('X-Powered-By: jerry@icewingcc.com', TRUE);
        header('X-Robots-Tag: noindex, nofollow');
//        header('X-Total-Time: ' . $this->benchmark->elapsed_time('total_execution_time_start', 'mark_now'));
        header('X-Memory-Usage: ' . (( ! function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2)) .'MB');

        if($data){
            header('Content-type: image/x-icon');
            echo $data;
        } else {
            header('Content-type: application/json');
            echo json_encode(array('status'=>-1, 'msg'=>'Unknown Error'));
        }

    }



    //-------------------------------------------------------------------------------------------------
    /**
     * 获取最终的Favicon图标数据
     * 此为该类获取图标的核心函数
     */
    function get_data(){

        //从缓存中获取保存的内容,如果缓存中有指定的内容就返回
        //否则需要执行从网络获取的过程

        if( $this->_get_data_from_cache())
            return $this->data;


        /**
         * 插件入口: favicon_after_get_cache
         * 用于通过Filter方式获取data,以覆盖后面获取的内容
         * @since Version 2.0; Date 2014-10-05
         */
        //  处理被墙网址
        $this->favicon_x_static_icons();

        //判断data中有没有来自插件写入的内容
        if( $this->data != NULL){
            return $this->data;
        }

        //从网络获取图标

        //从源网址获取HTML内容并解析其中的LINK标签
        $html = $this->get_file($this->params['origin_url']);

        if($html && $html['status'] == 'OK'){

            //匹配完整的LINK标签，再从LINK标签中获取HREF的值
            if(@preg_match('/(<link.*?rel=.(icon|shortcut icon|alternate icon).*?>)/i', $html['data'], $match_tag)){
                if(isset($match_tag[1]) && $match_tag[1] && @preg_match('/href=(\'|\")(.*?)\1/i', $match_tag[1], $match_url)){
                    if(isset($match_url[2]) && $match_url[2]){
                        //解析HTML中的相对URL 路径
                        $match_url[2] = $this->filter_relative_url(trim($match_url[2]), $this->params['origin_url']);

                        $icon = $this->get_file($match_url[2]);
                        if($icon && $icon['status'] == 'OK'){
                            Log::log_message("Success get icon from {$this->params['origin_url']}, icon url is {$match_url[2]}");
                            $this->data = $icon['data'];
                        }
                    }
                }
            }
        }

        if($this->data != NULL){
            return $this->data;
        }

        //用来在第一次获取后保存可能的重定向后的地址
        $redirected_url = $html['real_url'];

        //未能从LINK标签中获取图标（可能是网址无法打开，或者指定的文件无法打开，或未定义图标地址）
        //将使用网站根目录的文件代替
        $data = $this->get_file($this->full_host . '/favicon.ico');
        if($data && $data['status'] == 'OK'){
            Log::log_message("Success get icon from website root: {$this->full_host}/favicon.ico");
            $this->data = $data['data'];
        } else {
            //如果直接取根目录文件返回了301或404，先读取重定向，再从重定向的网址获取
            $ret = $this->parse_url_host($redirected_url);
            if($ret){
                //最后的尝试，从重定向后的网址根目录获取favicon文件
                $data = $this->get_file($this->full_host . '/favicon.ico');
                if($data && $data['status'] == 'OK'){
                    Log::log_message("Success get icon from redirect file: {$this->full_host}/favicon.ico");
                    $this->data = $data['data'];
                }

            }
        }


        if($this->data == NULL){
            //各个方法都试过了，还是获取不到。。。
            Log::log_message("Cannot get icon from {$this->params['origin_url']}");
            $this->data = @file_get_contents(FCPATH . 'static/favicon/default.png');
        }

        $this->_save_data_into_cache();

        return $this->data;
    }

    //-------------------------------------------------------------------------------------------------

    /**
     * 从memcache缓存中获取图标资源
     * 并在成功获取后设置$this->data的值,并返回TRUE
     * 否则返回FALSE
     * @return boolean
     */
    private function _get_data_from_cache(){
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

    //-------------------------------------------------------------------------------------------------

    /**
     * 把当前的内容保存到缓存中
     */
    private function _save_data_into_cache(){
        //保存新数据
        return $this->_cache->save($this->cache_key, $this->data, $this->params['expire']);
    }


    //-------------------------------------------------------------------------------------------------

    /**
     * 解析一个完整的URL中并返回其中的协议、域名和端口部分
     * 同时会设置类中的parsed_url和full_host属性
     *
     * Parse a full URL and return their parts,
     * and will set $this->parsed_url And $this->full_host at same time
     */
    private function parse_url_host($url){
        /**
         * 尝试解析URL参数，如果解析失败的话再加上http前缀重新尝试解析
         *
         * Try to parse url params,
         * if failed, append the http prefix and retry
         */
        $this->parsed_url = parse_url($url);

        if( ! isset($this->parsed_url['host']) || !$this->parsed_url['host']){
            //在URL的前面加上http://
            // add the prefix
            if ( ! preg_match('/^https?:\/\/.*/', $url))
                $url = 'http://' . $url;
            //解析URL并将结果保存到 $this->url
            // save parsed result into $this->url
            $this->parsed_url  = parse_url($url);

            if($this->parsed_url == FALSE){
                return FALSE;
            } else {
                /**
                 * 能成功解析的话就可以设置原始URL为这个添加过http://前缀的URL
                 *
                 * if we can parse the url, then we can set $this->params['origin_url']
                 * to the full url
                 */
                $this->params['origin_url'] = $url;
            }
        }

        $this->full_host = $this->parsed_url['scheme'] . '://' . $this->parsed_url['host'] . (isset($this->parsed_url['port']) ? ':' . $this->parsed_url['port'] : '');
        return $this->full_host;
    }

    //-------------------------------------------------------------------------------------------------

    /**
     * 把从HTML源码中获取的相对路径转换成绝对路径
     * @param string $url HTML中获取的网址
     * @param string $URI 用来参考判断的原始地址
     * @return 返回修改过的网址
     */
    private function filter_relative_url($url, $URI = ''){
        //STEP1: 先去判断URL中是否包含协议，如果包含说明是绝对地址则可以原样返回
        if(strpos($url, '://') !== FALSE){
            return $url;
        }

        //STEP2: 解析传入的URI
        $URI_part = parse_url($URI);
        if($URI_part == FALSE)
            return FALSE;
        $URI_root = $URI_part['scheme'] . '://' . $URI_part['host'] . (isset($URI_part['port']) ? ':' . $URI_part['port'] : '');

        //STEP3: 如果URL以左斜线开头，表示位于根目录
        if(strpos($url, '/') === 0){
            return $URI_root . $url;
        }

        //STEP4: 不位于根目录，也不是绝对路径，考虑如果不包含'./'的话，需要把相对地址接在原URL的目录名上
        $URI_dir = (isset($URI_part['path']) && $URI_part['path']) ? '/' . ltrim(dirname($URI_part['path']), '/')  : '';
        if(strpos($url, './') === FALSE){
            if($URI_dir != ''){
                return $URI_root . $URI_dir . '/' . $url;
            } else {
                return $URI_root . '/' . $url;
            }
        }

        //STEP5: 如果相对路径中包含'../'或'./'表示的目录，需要对路径进行解析并递归
        //STEP5.1: 把路径中所有的'./'改为'/'，'//'改为'/'
        $url = preg_replace('/[^\.]\.\/|\/\//', '/', $url);
        if(strpos($url, './') === 0)
            $url = substr($url, 2);

        //STEP5.2: 使用'/'分割URL字符串以获取目录的每一部分进行判断
        $URI_full_dir = ltrim($URI_dir . '/' . $url, '/');
        $URL_arr = explode('/', $URI_full_dir);

        if($URL_arr[0] == '..')
            return FALSE;

        //因为数组的第一个元素不可能为'..'，所以这里从第二个元素可以循环
        $dst_arr = $URL_arr;  //拷贝一个副本，用于最后组合URL
        for($i = 1; $i < count($URL_arr); $i ++){
            if($URL_arr[$i] == '..'){
                $j = 1;
                while(TRUE){
                    if(isset($dst_arr[$i - $j]) && $dst_arr[$i - $j] != FALSE){
                        $dst_arr[$i - $j] = FALSE;
                        $dst_arr[$i] = FALSE;
                        break;
                    } else {
                        $j ++;
                    }
                }
            }
        }

        //STEP6: 组合最后的URL并返回
        $dst_str = $URI_root;
        foreach($dst_arr as $val){
            if($val != FALSE)
                $dst_str .= '/' . $val;
        }

        return $dst_str;
    }


    #----------------------------------------------------------------
    /**
     * 从指定URL获取文件
     * @param string $url
     * @param int $timeout 超时值，默认为10秒
     * @return string 成功返回获取到的内容，同时设置 $this->content，失败返回FALSE
     */
    private function get_file($url, $timeout = 10){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        //执行重定向获取
        $ret = $this->curl_exec_follow($ch, 7);

        if($ret === FALSE){
            $arr = array(
                'status'    => 'FAIL',
                'data'      => '',
                'real_url'  => ''
            );

        } else {
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $arr = array(
                'status'    => ($status >= 200 && $status <= 299) ? TRUE : FALSE,
                'data'      => $ret,
                'real_url'  => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL)
            );

        }
        curl_close($ch);

        return $arr;
    }

    /**
     * 使用跟综重定向的方式查找被301/302跳转后的实际地址，并执行curl_exec
     * 代码来自： http://php.net/manual/zh/function.curl-setopt.php#102121
     * @param resource $ch CURL资源句柄
     * @param int $maxredirect  最大允许的重定向次数
     *
     */
    private function curl_exec_follow(/*resource*/ &$ch, /*int*/ $maxredirect = null) {
        $mr = $maxredirect === null ? 5 : intval($maxredirect);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($mr > 0) {
                $newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

                $rch = curl_copy_handle($ch);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
                do {
                    curl_setopt($rch, CURLOPT_URL, $newurl);
                    $header = curl_exec($rch);
                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/i', $header, $matches);
                            $newurl = trim(array_pop($matches));
                        } else {
                            $code = 0;
                        }
                    }
                } while ($code && --$mr);
                curl_close($rch);
                if (!$mr) {
                    if ($maxredirect === null) {
                        trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
                    } else {
                        $maxredirect = 0;
                    }
                    return false;
                }
                curl_setopt($ch, CURLOPT_URL, $newurl);
            }
        }
        return curl_exec($ch);
    }


    /**
     * 获取固定的被墙网站的图标
     * @return mixed
     */
    protected function favicon_x_static_icons(){
        //用当前Full_host循环匹配上面的网址，并在成功匹配后返回该网址的图标
        foreach (self::$_static_icon_list as $key => $val){
            if(preg_match('/' . $key . '(:\d+)?$/i', $this->full_host)){
                $path = dirname(__FILE__) . 'icons/' . $val;
                if(file_exists($path)){
                    $this->data = @file_get_contents($path);
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

}

