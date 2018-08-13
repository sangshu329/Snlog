<?php

/**
 * Created by PhpStorm.
 * User: ADMIN
 * Date: 2018-08-06
 * Time: 13:56
 */

namespace snlg;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

Class Snlog
{
    private $seaslog_tag = 'appLog';
    private $path = '';
    private $debugInfo;
    private $seaslogEable = 0;
    private $recodeMode = 0;  // 是否启用双模式记录日志

    /**
     * Snlog constructor.
     * @param string $tag
     * @param string $path
     * @param int $mode 1为启用单模式记录，2为启用双模式日志记录
     * @param int $islocal_log
     */
    public function __construct($tag = '', $path = '', $mode = 0, $islocal_log = 0)
    {
        is_string($tag) && !empty($tag) ? $this->seaslog_tag = $tag : '';
        is_dir($path) && !empty($path) ? $this->path = $path : '';
        $mode == 'double' || $mode === 1 ? $this->recodeMode = $mode : '';
        $this->seaslogEable = $this->checkSeaslog();
        $backtrace = debug_backtrace();
        $this->debugInfo = array_pop($backtrace);
    }

    /**
     * @日志记录主方法
     * @param string $key
     * @param string $value
     * @param int $isurl
     * @param string $tag
     * @param int $is_local
     */
    public function resnLog($key = '', $value = '', $isurl = 0, $tag = 'debug', $is_local = 0)
    {
        if ($isurl && $this->getRuntime() != 'cli') {
            if ($this->seaslogEable) {
                $this->seaslogLog($key, $value, 1);
                if ($this->recodeMode) {
                    $this->localLog($key, $value, 1);
                }
            } else {
                $this->localLog($key, $value, 1);
            }
        } else {
            if ($this->seaslogEable) {
                $this->seaslogLog($key, $value);
                if ($this->recodeMode) {
                    $this->localLog($key, $value);
                }
            } else {
                $this->localLog($key, $value);
            }
        }
    }


    /**
     * @param $name
     * @param string $path
     * @param string $handler
     * @return Logger
     */
    public function molog($name, $path = '', $handler = '')
    {
        $log = new Logger($name);
        $path = $path === '' ? 'applog' : str_replace('\\', '/', $path);
        if (strpos($path, '/') === false) {
            $curPath = $this->getCurPath() . 'logs/' . $path;
        } else {
            $curPath = $path;
        }

        $handler = $handler === '' ? new StreamHandler($curPath, Logger::WARNING) : $handler;
        $log->pushHandler($handler);
        return $log;
    }

    /**
     * mongolog的handler设置
     * @param string $path
     * @param string $modevalue
     * @return StreamHandler
     */
    public function mohandler($path = '', $modevalue = 'WARNING')
    {
        switch ($modevalue) {
            case 'DEBUG':
                $mode = Logger::DEBUG;
                break;
            case 'INFO':
                $mode = Logger::INFO;
                break;
            case 'NOTICE':
                $mode = Logger::NOTICE;
                break;
            case 'WARNING':
                $mode = Logger::WARNING;
                break;
            case 'ERROR':
                $mode = Logger::ERROR;
                break;
            case 'CRITICAL':
                $mode = Logger::CRITICAL;
                break;
            case 'ALERT':
                $mode = Logger::ALERT;
                break;
            case 'EMERGENCY':
                $mode = Logger::EMERGENCY;
                break;
            default:
                $mode = Logger::DEBUG;
                break;
        }
        $path = $path === '' ? 'applog_' . $modevalue : str_replace('\\', '/', $path);
        if (strpos($path, '/') === false) {
            $curPath = $this->getCurPath() . 'logs/' . $path;
        } else {
            $curPath = $path;
        }
        return new StreamHandler($curPath, $mode);
    }

    private function checkSeaslog()
    {
        if (class_exists('SeasLog')) {
            return true;
        }
        return false;
    }

    private function localLog($key, $value, $isurl = '', $fun = 0)
    {
        $filePath = $this->debugInfo['file'];
        $path = str_replace('\\', '/', str_replace(basename($filePath), '', $filePath));

        if (is_dir($path) && $this->isWritable($path)) {
        } else {
            if ($this->getOS() == 'Windows') {
                $path = './';
            } else {
                $path = '/tmp/';
            }
//            echo '日志指定路径不可写，'
        }
        if (!$fun) {
            $user_fun = 'serialize';
        } else {
            $user_fun = 'json_encode';
        }
        if (is_array($value) || is_object($value)) {
//            $value = call_user_func($user_fun, $value);
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if (!is_dir($path . 'logs')) @mkdir($path . 'logs', 0777, true);

        if ($isurl) {
            $url = $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];

            $log_data = implode(PHP_EOL, array(
                '#####',
                '[' . date('Y-m-d H:i:s') . '] ',
                'post_data-    ' . stripslashes(json_encode($_POST, JSON_UNESCAPED_UNICODE)),
                'get_data-   ' . json_encode($_GET, JSON_UNESCAPED_UNICODE),
                'request_url-  ' . $url,
                '#####',
            ));
        } else {
            $log_data = '[' . date('Y-m-d H:i:s') . ']  ' . $key . '->' . $value . "\r\n";
        }
        file_put_contents($path . 'logs/' . $this->seaslog_tag . date('Ymd') . '.log', $log_data, FILE_APPEND | LOCK_EX);
    }

    private function seaslogLog($key = '', $data = '', $isurl = '')
    {
        SeasLog::setLogger($this->seaslog_tag);
        if ($isurl) {
            $url = $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
            SeasLog::info('request_url' . "\t" .
                implode("\t", array(
                    'post_data-    ' . stripslashes(json_encode($_POST, JSON_UNESCAPED_UNICODE)),
                    'get_data-   ' . json_encode($_GET, JSON_UNESCAPED_UNICODE),
                    'request_url-  ' . $url,
                )));
        } else {
            SeasLog::info('log_data' . "\t" .
                implode("\t", array(
                    "$key  => " . stripslashes(json_encode($data, JSON_UNESCAPED_UNICODE)),
                ))
            );
        }
    }

    /**
     * 获取当前运行文件的目录路径
     * @return mixed
     */
    private function getCurPath()
    {
        $filePath = $this->debugInfo['file'];
        $curPath = str_replace('\\', '/', str_replace(basename($filePath), '', $filePath));
        return $curPath;
    }

    public static function isWritable($filename)
    {
        if (preg_match('/\/$/', $filename)) {
            $tmp_file = sprintf('%s%s.tmp', $filename, uniqid(mt_rand()));
            return self::isWritable($tmp_file);
        }
        if (file_exists($filename)) {
            $fp = @fopen($filename, 'r+');
            if ($fp) {
                fclose($fp);
                return true;
            } else {
                return false;
            }
        } else {
            $fp = @fopen($filename, 'w');
            if ($fp) {
                fclose($fp);
                unlink($filename);
                return true;
            } else {
                return false;
            }
        }
    }

    public static function getOS()
    {
        if (PATH_SEPARATOR == ':') {
            return 'Linux';
        } else {
            return 'Windows';
        }
    }

    public static function getRuntime()
    {
        $runMode = php_sapi_name();
        $isHaveQuery = isset($_SERVER['QUERY_STRING']);
        switch ($runMode) {
            case 'fpm-fcgi':
                return $isHaveQuery ? 'web' : 'cli';
            case 'apache2handler':
                return $isHaveQuery ? 'web' : 'cli';
            case 'cli':
                return 'cli';
        }
    }


}