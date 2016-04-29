<?php
/**
 * 获取网站Favicon服务接口
 *
 * @author    Jerry Bendy (jerry@icewingcc.com)
 * @date      2014-09-10
 * @link      http://blog.icewingcc.com
 * @version   2.1.0
 */

namespace Jerrybendy\Favicon;


/**
 * Favicon 获取类
 *
 * ## 关于缓存:
 * 本来类库中是调用Redis来做缓存，后来想了下，还是让这个类只专注于自己的业
 * 务吧（获取图标）缓存这些操作交给外部来处理
 *
 * @author Jerry Bendy
 *
 * @link   http://blog.icewingcc.com
 *
 */

class Favicon
{

    /**
     * 保存传入的参数,其中:
     *
     *    origin_url:     保存传入的url参数的原始字符串信息
     *
     *
     *  以及一些额外的参数及暂存的数据
     */
    private $params = array();

    /**
     * 完整的形如  http://xxx.xxx.com:8888 这样的地址
     */
    private $full_host = '';

    /**
     * 包含获取到的最终的二进制数据
     *
     */
    private $data = NULL;

    /**
     * 最后一次请求花费的时间
     *
     * @var double
     */
    private $_last_time_spend = 0;

    /**
     * 最后一次请求消耗的内存
     *
     * @var string
     */
    private $_last_memory_usage = 0;


    /**
     * 获取网站Favicon并输出
     *
     * @param string $url    输入的网址
     *                       (The input URL)
     * @param bool   $return 是要求返回二进制内容还是直接显示它
     * @throws \InvalidArgumentException
     * @return string
     *
     */
    function get_favicon($url = '', $return = FALSE)
    {

        /**
         * 验证传入参数
         */
        if (!$url) {
            throw new \InvalidArgumentException(__CLASS__ . ': Url cannot be empty', E_ERROR);
        }

        //
        $this->params['origin_url'] = $url;

        //解析URL参数
        $ret = $this->parse_url_host($url);
        if (!$ret) {
            throw new \InvalidArgumentException(__CLASS__ . ': Invalided url', E_WARNING);
        }

        /**
         * 开始获取图标过程
         */
        $time_start = microtime(TRUE);

        error_log('Begin to get icon, ' . $url);


        /**
         * get the favicon bin data
         */
        $data = $this->get_data();

        /**
         * 获取过程结束
         * 计算时间及内存的占用信息
         */
        $time_end = microtime(TRUE);
        $this->_last_time_spend = $time_end - $time_start;

        $this->_last_memory_usage = ((!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 2)) . 'MB';

        error_log('Get icon complate, spent time ' . $this->_last_time_spend . 's, Memory_usage ' . $this->_last_memory_usage);

        /**
         * 设置输出Header信息
         * Output common header
         *
         * @since V2.1.4 2015-02-09
         */
        if ($return) {
            return $data;

        } else {

            if ($data) {

                foreach ($this->getHeader() as $header) {
                    @header($header);
                }

                echo $data;

            } else {
                header('Content-type: application/json');
                echo json_encode(array('status' => -1, 'msg' => 'Unknown Error'));
            }
        }

        return null;
    }


    /**
     * 获取输出 header
     *
     * @since v2.1
     * @return array
     */
    public function getHeader()
    {
        return array(
            'X-Robots-Tag: noindex, nofollow',
            'Content-type: image/x-icon'
        );
    }


    //-------------------------------------------------------------------------------------------------
    /**
     * 获取最终的Favicon图标数据
     * 此为该类获取图标的核心函数
     */
    protected function get_data()
    {

        //判断data中有没有来自插件写入的内容
        if ($this->data != NULL) {
            error_log('Get icon from static file cache, ' . $this->full_host);

            return $this->data;
        }

        //从网络获取图标

        //从源网址获取HTML内容并解析其中的LINK标签
        $html = $this->get_file($this->params['origin_url']);

        if ($html && $html['status'] == 'OK') {

            /*
             * 2016-01-31
             * FIX #1
             * 对取到的HTML内容进行删除换行符的处理,避免link信息折行导致的正则匹配失败
             */
            $html = str_replace(array("\n", "\r"), '', $html);

            //匹配完整的LINK标签，再从LINK标签中获取HREF的值
            if (@preg_match('/((<link[^>]+rel=.(icon|shortcut icon|alternate icon)[^>]+>))/i', $html['data'], $match_tag)) {

                if (isset($match_tag[1]) && $match_tag[1] && @preg_match('/href=(\'|\")(.*?)\1/i', $match_tag[1], $match_url)) {

                    if (isset($match_url[2]) && $match_url[2]) {

                        //解析HTML中的相对URL 路径
                        $match_url[2] = $this->filter_relative_url(trim($match_url[2]), $this->params['origin_url']);

                        $icon = $this->get_file($match_url[2]);

                        if ($icon && $icon['status'] == 'OK') {

                            error_log("Success get icon from {$this->params['origin_url']}, icon url is {$match_url[2]}");

                            $this->data = $icon['data'];
                        }
                    }
                }
            }
        }

        if ($this->data != NULL) {
            return $this->data;
        }

        //用来在第一次获取后保存可能的重定向后的地址
        $redirected_url = $html['real_url'];

        //未能从LINK标签中获取图标（可能是网址无法打开，或者指定的文件无法打开，或未定义图标地址）
        //将使用网站根目录的文件代替
        $data = $this->get_file($this->full_host . '/favicon.ico');

        if ($data && $data['status'] == 'OK') {
            error_log("Success get icon from website root: {$this->full_host}/favicon.ico");
            $this->data = $data['data'];

        } else {
            //如果直接取根目录文件返回了301或404，先读取重定向，再从重定向的网址获取
            $ret = $this->parse_url_host($redirected_url);

            if ($ret) {
                //最后的尝试，从重定向后的网址根目录获取favicon文件
                $data = $this->get_file($this->full_host . '/favicon.ico');

                if ($data && $data['status'] == 'OK') {
                    error_log("Success get icon from redirect file: {$this->full_host}/favicon.ico");
                    $this->data = $data['data'];
                }

            }
        }


        if ($this->data == NULL) {
            //各个方法都试过了，还是获取不到。。。
            // 返回默认文件
            error_log("Cannot get icon from {$this->params['origin_url']}");
            $this->data = @file_get_contents(dirname(__FILE__) . '/icons/default.png');
        }

        return $this->data;
    }





    //-------------------------------------------------------------------------------------------------

    /**
     * 解析一个完整的URL中并返回其中的协议、域名和端口部分
     * 同时会设置类中的parsed_url和full_host属性
     *
     * @param $url
     * @return bool|string
     */
    private function parse_url_host($url)
    {
        /**
         * 尝试解析URL参数，如果解析失败的话再加上http前缀重新尝试解析
         *
         */
        $parsed_url = parse_url($url);

        if (!isset($parsed_url['host']) || !$parsed_url['host']) {
            //在URL的前面加上http://
            // add the prefix
            if (!preg_match('/^https?:\/\/.*/', $url))
                $url = 'http://' . $url;
            //解析URL并将结果保存到 $this->url
            // save parsed result into $this->url
            $parsed_url = parse_url($url);

            if ($parsed_url == FALSE) {
                return FALSE;
            } else {
                /**
                 * 能成功解析的话就可以设置原始URL为这个添加过http://前缀的URL
                 */
                $this->params['origin_url'] = $url;
            }
        }

        $this->full_host = (isset($parsed_url['scheme']) ? $parsed_url['scheme'] : 'http') . '://' . $parsed_url['host'] . (isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '');

        return $this->full_host;
    }

    //-------------------------------------------------------------------------------------------------

    /**
     * 把从HTML源码中获取的相对路径转换成绝对路径
     *
     * @see 函数详情： http://blog.icewingcc.com/php-conv-addr-re-ab-2.html
     *
     * @param string $url HTML中获取的网址
     * @param string $URI 用来参考判断的原始地址
     * @return string 返回修改过的网址
     */
    private function filter_relative_url($url, $URI = '')
    {
        //STEP1: 先去判断URL中是否包含协议，如果包含说明是绝对地址则可以原样返回
        if (strpos($url, '://') !== FALSE) {
            return $url;
        }

        //STEP2: 解析传入的URI
        $URI_part = parse_url($URI);
        if ($URI_part == FALSE)
            return FALSE;
        $URI_root = $URI_part['scheme'] . '://' . $URI_part['host'] . (isset($URI_part['port']) ? ':' . $URI_part['port'] : '');

        //STEP3: 如果URL以左斜线开头，表示位于根目录
        if (strpos($url, '/') === 0) {
            return $URI_root . $url;
        }

        //STEP4: 不位于根目录，也不是绝对路径，考虑如果不包含'./'的话，需要把相对地址接在原URL的目录名上
        $URI_dir = (isset($URI_part['path']) && $URI_part['path']) ? '/' . ltrim(dirname($URI_part['path']), '/') : '';
        if (strpos($url, './') === FALSE) {
            if ($URI_dir != '') {
                return $URI_root . $URI_dir . '/' . $url;
            } else {
                return $URI_root . '/' . $url;
            }
        }

        //STEP5: 如果相对路径中包含'../'或'./'表示的目录，需要对路径进行解析并递归
        //STEP5.1: 把路径中所有的'./'改为'/'，'//'改为'/'
        $url = preg_replace('/[^\.]\.\/|\/\//', '/', $url);
        if (strpos($url, './') === 0)
            $url = substr($url, 2);

        //STEP5.2: 使用'/'分割URL字符串以获取目录的每一部分进行判断
        $URI_full_dir = ltrim($URI_dir . '/' . $url, '/');
        $URL_arr = explode('/', $URI_full_dir);

        if ($URL_arr[0] == '..')
            return FALSE;

        //因为数组的第一个元素不可能为'..'，所以这里从第二个元素可以循环
        $dst_arr = $URL_arr;  //拷贝一个副本，用于最后组合URL
        for ($i = 1; $i < count($URL_arr); $i++) {
            if ($URL_arr[$i] == '..') {
                $j = 1;
                while (TRUE) {
                    if (isset($dst_arr[$i - $j]) && $dst_arr[$i - $j] != FALSE) {
                        $dst_arr[$i - $j] = FALSE;
                        $dst_arr[$i] = FALSE;
                        break;
                    } else {
                        $j++;
                    }
                }
            }
        }

        //STEP6: 组合最后的URL并返回
        $dst_str = $URI_root;
        foreach ($dst_arr as $val) {
            if ($val != FALSE)
                $dst_str .= '/' . $val;
        }

        return $dst_str;
    }


    #----------------------------------------------------------------
    /**
     * 从指定URL获取文件
     *
     * @param string $url
     * @param int    $timeout 超时值，默认为10秒
     * @return string 成功返回获取到的内容，同时设置 $this->content，失败返回FALSE
     */
    private function get_file($url, $timeout = 10)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        //执行重定向获取
        $ret = $this->curl_exec_follow($ch, 7);

        if ($ret === FALSE) {
            $arr = array(
                'status'   => 'FAIL',
                'data'     => '',
                'real_url' => ''
            );

        } else {
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $arr = array(
                'status'   => ($status >= 200 && $status <= 299) ? TRUE : FALSE,
                'data'     => $ret,
                'real_url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL)
            );

        }
        curl_close($ch);

        return $arr;
    }

    /**
     * 使用跟综重定向的方式查找被301/302跳转后的实际地址，并执行curl_exec
     * 代码来自： http://php.net/manual/zh/function.curl-setopt.php#102121
     *
     * @param resource $ch          CURL资源句柄
     * @param int      $maxredirect 最大允许的重定向次数
     * @return string
     */
    private function curl_exec_follow(&$ch, $maxredirect = NULL)
    {
        $mr = $maxredirect === NULL ? 5 : intval($maxredirect);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
            if ($mr > 0) {
                $newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

                $rch = curl_copy_handle($ch);
                curl_setopt($rch, CURLOPT_HEADER, TRUE);
                curl_setopt($rch, CURLOPT_NOBODY, TRUE);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, FALSE);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, TRUE);
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
                    if ($maxredirect === NULL) {
                        trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
                    } else {
                        $maxredirect = 0;
                    }

                    return FALSE;
                }
                curl_setopt($ch, CURLOPT_URL, $newurl);
            }
        }

        return curl_exec($ch);
    }


}

