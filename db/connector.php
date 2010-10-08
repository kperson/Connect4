<?php
class Connector{

    private static $instance;

    private function __construct(){}
    public function __clone(){}

    public static function getInstance(){
        if (!isset(self::$instance)) {
            if (USER_NAME && PASSWORD){
                self::$instance = new PDO(DSN, USER_NAME, PASSWORD);
            }
            else{
                self::$instance = new PDO(DSN);
            }
            self::$instance->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$instance;
    }
}
?>