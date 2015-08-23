<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2015/8/16
 * Time: 0:30
 */

namespace WS;


class Log {

    /**
     * 定义部分日志等级，可以使用 | 运算符设定多个等级
     */
    const LOG_NONE      = 0;  // 不记录任何日志

    const LOG_INFO      = 1;  // 记录消息提示

    const LOG_DEBUG     = 2;  // 记录调试信息（啰嗦）

    const LOG_ERROR     = 4;  // 记录错误信息

    /**
     * 对应各个等级用于显示在日志中的字符串
     * @var array
     */
    private static $_log_level_str_map = array(
        1   => 'INFO',
        2   => 'DEBUG',
        4   => 'ERROR'
    );

    /**
     * 暂存记录到的日志文件的路径
     * @var string
     */
    private static $_log_file = null;

    /**
     * 保存设定的日志记录等级
     * @var int
     */
    protected static $_log_level = 7;



    /**
     * 设定记录日志的等级
     * 仅有被设定等级的日志会被记录到文件
     *
     * @param $level
     */
    public static function set_log_level($level){
        self::$_log_level = $level;
    }


    /**
     * Write something into file log
     * @param int $level 记录日志的等级
     * @param string $msg 日志的内容
     * @throws \Exception
     */
    public static function log_message($level, $msg){

        if($level === 0){
            throw new \Exception('\WS\Log: Log level must be a valid integer value');
        }

        /**
         * 仅当日志记录等级为0（0代表不记录）并且当前需要记录的日志等级不在
         * 设定的等级内时，直接返回
         */
        if(self::$_log_level === 0 || self::$_log_level & $level != $level){
            return;
        }

        if(self::$_log_file == null){
            self::$_log_file = dirname(__FILE__) . '/logs/log-' . date('Y-m-d') . '.php';
        }

        if(! file_exists(self::$_log_file)){
            @file_put_contents(self::$_log_file, '<' . '?php ' . "\n", FILE_APPEND);
        }

        $msg = '# ' . date('h:i:s') . '  ' .
            (isset(self::$_log_level_str_map[$level]) ? self::$_log_level_str_map[$level] : '') . '  ' .
            $msg . "\n";

        @file_put_contents(self::$_log_file, $msg, FILE_APPEND);

    }

}