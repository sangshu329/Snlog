<?php
/**
 * Created by PhpStorm.
 * User: ADMIN
 * Date: 2018-08-01
 * Time: 15:07
 */

class MyPDO
{
    protected static $_instance = null;
    protected $dbName = null;
    protected $dsn;
    protected $dbh;

    private function __construct($dbHost, $dbUser, $dbPwd, $dbName, $dbCharset)
    {
        try {
            $this->dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName;
            $this->dbh = new PDO($this->dsn, $dbUser, $dbPwd);
            $this->dbh->exec('SET character_set_connection=' . $dbCharset . ', character_set_results=' . $dbCharset . ', character_set_client=binary');
        } catch (PDOException $e) {
            $this->outputError($e->getMessage());
        }
    }

    private function __clone()
    {
    }

    public static function getInstance($dbHost, $dbUser, $dbPwd, $dbName, $dbCharset)
    {
        if (self::$_instance === null) {
            self::$_instance = new self($dbHost, $dbUser, $dbPwd, $dbName, $dbCharset);
        }
        return self::$_instance;
    }

    public function query($strSql, $queryMode = 'All', $debug = false)
    {
        if ($debug == true) {
            $this->debug($strSql);
        }
        $recordset = $this->dbh->query($strSql);
        $this->getPDOError();
        if ($recordset) {
            $recordset->setFetchMode(PDO::FETCH_ASSOC);
            if ($queryMode == 'All') {
                $result = $recordset->fetchAll();
            } elseif ($queryMode == 'Row') {
                $result = $recordset->fetch();
            }
        } else {
            $result = null;
        }
        return $result;
    }

    public function update($table, $arrayDataValue, $where = '', $debug = false)
    {
        $this->checkFields($table, $arrayDataValue);
        if ($where) {
            $strSql = '';
            foreach ($arrayDataValue as $key => $value) {
                $strSql .= ", `$key`='$value'";
            }
            $strSql = substr($strSql, 1);
            $strSql = "update `$table` set $strSql where $where";
        } else {
            $strSql = "REPLACE INTO `$table` (`" . implode('`,`', array_keys($arrayDataValue)) . "`) VALUES ('" . implode("','", $arrayDataValue) . "')";
        }
        if ($debug === ture) {
            $this->debug($strSql);
        }
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    public function insert($table, $arrayDataValue, $debug = false)
    {
        $this->checkFields($table, $arrayDataValue);
        $strSql = "INSERT INTO `$table` (`" . implode('`,`', array_keys($arrayDataValue)) . "`) VALUES ('" . implode("','", $arrayDataValue) . "')";
        if($debug === true){
            $this->debug($strSql);
        }
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    public function replace($table, $arrayDataValue, $debug = false)
    {
        $this->checkFields($table,$arrayDataValue);
        $strSql = "REPLACE INTO `$table`(`".implode('`,`', array_keys($arrayDataValue))."`) VALUES ('".implode("','", $arrayDataValue)."')";
        if($debug === true) $this->debug($strSql);
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    public function delete($table, $where = '', $debug = false)
    {
        if($where ==''){
            $this->outputError("'WHERE' is null");
        }else{
            $strSql = "Delete from `$table` where $where";
            if($debug === true) $this->debug($strSql);
            $result = $this->dbh->exec($strSql);
            $this->getPDOError();
            return $result;
        }
    }

    public function execSql($strSql, $debug = false)
    {
        if($debug === true) $this->debug($strSql);
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    public function getMaxValue($table, $field_name, $where = '',$debug=false)
    {
        $strSql = "select max(".$field_name.") as max_value from $table";
        if($where != '') $strSql .= " where $where";
        if($debug === true) $this->debug($strSql);
        $arrTemp = $this->query($strSql,'Row');
        $maxValue = $arrTemp['max_value'];
        if($maxValue == "" || $maxValue == null) {
            $maxValue =0;
        }
        return $maxValue;
    }

    public function getCount($table, $field_name, $where = '', $debug = false)
    {
        $strSql= "select count($field_name) as num from $table";
        if($where!='') $strSql .= " where $where";
        if($debug === true ) $this->debug($strSql);
        $arrTemp  =  $this->query($strSql,'Row');
        return $arrTemp['num'];
    }

    public function getTableEngine($dbName, $tableName)
    {

    }


    private function checkFields($table, $arrayFields)
    {
        $fields = $this->getFields($table);
        foreach ($arrayFields as $key => $value) {
            if (!in_array($key, $fields)) {
                $this->outputError("Unknown column `$key` in field list.");
            }
        }
    }

    private function getFields($table)
    {
        $fields = array();
        $recordset = $this->dbh->query("SHOW COLUMNS FROM $table");
        $this->getPDOError();
        $recordset->setFetchMode(PDO::FETCH_ASSOC);
        $result = $recordset->fetchAll();
        foreach ($result as $rows) {
            $fields[] = $rows['Field'];
        }
        return $fields;
    }

    public function getPDOError()
    {
        if($this->dbh->errorCode()!='00000'){
            $arrayErro = $this->dbh->errorInfo();
            $this->outputError($arrayErro[2]);
        }
    }

    public function debug($debuginfo)
    {
        var_dump($debuginfo);
        die();
    }

    public function outputError($strError)
    {
        throw new Exception('MySQL Error: '.$strError);
    }

    public function destruct()
    {
        $this->dbh = null;
    }
}
