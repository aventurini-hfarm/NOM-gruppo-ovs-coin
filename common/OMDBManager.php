<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 05/05/15
 * Time: 08:27
 */

class OMDBManager {



 // PRODUZIONE
    private static $dbhost = "10.132.0.2";
    private static $dbname = "om_support";
    private static $dbusername="nomovsprod";
    private static $dbpassword = "xhzt4wUcosh7";

    private static $dbnameMagento = "magento";
    private static $dbusernameMagento="nomovsprod";
    private static $dbpasswordMagento = "xhzt4wUcosh7";


/*
    private static $dbhost = "10.132.0.5";
    private static $dbname = "om_support";
    private static $dbusername="nomovsdevelop";
    private static $dbpassword = "nomovsdevelop";


    private static $dbnameMagento = "magento";
    private static $dbusernameMagento="nomovsdevelop";
    private static $dbpasswordMagento = "nomovsdevelop";
*/

    public static function getConnection() {
        //watchdog("TEAM_LOGIN","CONNECTION ".self::$dbhost, array(), WATCHDOG_DEBUG);
        $con = mysql_pconnect(self::$dbhost, self::$dbusername, self::$dbpassword) or die(mysql_error());
        mysql_select_db(self::$dbname, $con) or die(mysql_error());
        $sql = "SET NAMES 'utf8'";
        mysql_query($sql);

        return $con;
    }


    public static function closeConnection($con) {
        if ($con != null) {
            mysql_close($con);
        }
    }

    public static function getMagentoConnection() {
        //watchdog("TEAM_LOGIN","CONNECTION ".self::$dbhost, array(), WATCHDOG_DEBUG);
        $con = mysql_connect(self::$dbhost, self::$dbusernameMagento, self::$dbpasswordMagento) or die(mysql_error());
        mysql_select_db(self::$dbnameMagento, $con) or die(mysql_error());
        $sql = "SET NAMES 'utf8'";
        mysql_query($sql);

        return $con;
    }

}


