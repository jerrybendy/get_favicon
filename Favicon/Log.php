<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2015/8/16
 * Time: 0:30
 */

namespace WS;


class Log {

    private static $_log_file = null;

    /**
     * Write something into file log
     * @param $msg
     */
    public static function log_message($msg){

        if(self::$_log_file == null){
            self::$_log_file = dirname(__FILE__) . 'logs/log-' . date('Y-m-d') . '.php';
        }

        if(! file_exists(self::$_log_file)){
            @file_put_contents(self::$_log_file, '<' . '?php ' . "\n");
        }

        $msg = '# ' . date('h:i:s') . '  ' . $msg;
        @file_put_contents(self::$_log_file, $msg, FILE_APPEND);

    }

}