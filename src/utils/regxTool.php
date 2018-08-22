<?php
/**
 * Created by PhpStorm.
 * User: ADMIN
 * Date: 2018-08-21
 * Time: 13:23
 * 参考网址:https://blog.csdn.net/self_realian/article/details/78571820
 *
 * int preg_match($pattern, $subject);
 * preg_match_all($pattern, $subject, array &$matches);
 * preg_replace($pattern, $replacement, $subject);
 * preg_filter($pattern, $replacement, $subject);
 * preg_grep($pattern, array $input);
 * preg_split($pattern, $subject);
 * preg_quote($str);
 *
 */

class regxTool
{
    private $validate = array(
        'notnull' => '/^.+$/',
        'float' => '/^\d+\.\d{2}$/',
        'phonenumber' => '/^1(3|4|5|7|8)\d{9}$/',
        'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
        'url' => '/^(http(s)??:\/\/)?(\w+\.)+[a-zA-Z]+$/',
        'qq' => '/^\d{5,11}$/'
    );

    private $returnMatchResult = false;
    private $fixMode = null;
    private $matches = array();
    private $isMatch = false;
    public static $resultInfo = '';

    public function __construct($resturnMatchResult = false, $fixMode = null)
    {
        $this->returnMatchResult = $resturnMatchResult;
        $this->fixMode = $fixMode;
    }

    private function regex($pattern, $subject)
    {
        $pattern=$this->isHasRegex($pattern);
        $this->returnMatchResult ? preg_match_all($pattern, $subject, $this->matches) : $this->isMatch = preg_match($pattern, $subject) === 1;
        return $this->getRegexResult();
    }

    private function isHasRegex($pattern)
    {
        if(is_string($pattern)){
            if (array_key_exists(strtolower($pattern), $this->validate)) {
                $pattern = $this->validate[$pattern] . $this->fixMode;
            }
        }
        return $pattern;
    }


    private function getRegexResult($getRreulst=false,$content='')
    {
        if(!$getRreulst){
            if (  $this->returnMatchResult) {
                self::$resultInfo = $this->matches;
            } else {
                self::$resultInfo = $this->isMatch;
            }
        }else{
            self::$resultInfo=$content;
        }
        return self::$resultInfo;
    }

    public function toggleReturnType($bool = null)
    {
        if (empty($bool)) {
            $this->returnMatchResult = !$this->returnMatchResult;
        } else {
            $this->returnMatchResult = is_bool($bool) ? $bool : (bool)$bool;
        }
    }

    public function setFiexMax($fixMode)
    {
        $this->fixMode = $fixMode;
    }

    public function noEmpty($str)
    {
        return $this->regex('notnull', $str);
    }

    public function isMobile($mobile)
    {
        return $this->regex('phonenumber', $mobile);
    }

    public function isEmail($email)
    {
        return $this->regex('email', $email);
    }

    public function check($pattern, $subject)
    {
        return $this->regex($pattern, $subject);
    }

    /**
     * @param $pattern
     * @param $replacement
     * @param $subject
     * @param bool $is_filter
     * @return array|bool|string
     *
     * preg_replace将数组中没有与$pattern匹配的字符串也保存在数组中
     * preg_filter只将匹配到的字符串保存在数组中
     *
     */
    public function preg_replace($pattern, $replacement, $subject,$is_filter=false)
    {
        $pattern=$this->isHasRegex($pattern);
        if(!$is_filter){
            $rs=preg_replace($pattern,$replacement,$subject);
        }else{
            $rs=preg_filter($pattern,$replacement,$subject);
        }
        return self::getRegexResult(1,$rs);
    }

    /**
     * @param $pattern
     * @param $subject
     * @return array|bool|string
     *
     * 用于批量校验是否符合正则表达式，并批量返回数据
     */
    public function preg_grep($pattern, $subject)
    {
        $pattern=$this->isHasRegex($pattern);
        $rs=preg_grep($pattern,$subject);
        return self::getRegexResult(1,$rs);
    }

    public function preg_split($pattern, $subject,$limit = -1, $flags = 0)
    {
        $pattern=$this->isHasRegex($pattern);
        $rs=preg_split($pattern,$subject,$limit, $flags);
        return self::getRegexResult(1,$rs);
    }

    /**
     * @param $str
     *
     * 将一个字符串中的正则表达式运算符进行转义
     */
    public function preg_quote($str, $delimiter = null)
    {
        return self::getRegexResult(1,preg_quote($str,$delimiter));
    }


    public static function show($var = null, $isdump = false)
    {
        $func = $isdump ? 'var_dump' : 'print_r';
        if (empty($var) && regxTool::$resultInfo) {
            $var = regxTool::$resultInfo;
        } else {
            echo null;
        }
        if (is_array($var) || is_object($var)) {
            echo '<pre>';
            $func($var);
            echo '</pre>';
        } else {
            $func($var);
        }
    }

}