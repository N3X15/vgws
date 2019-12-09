<?php

class Assets{
    public static $Assets = [];
    public static function Get($ID) {
        if(count(array_keys(self::$Assets))==0) {
            self::$Assets = PUBLIC_DIR . '/manifest.json';
        }
        if(!array_key_exists($ID, self::$Assets))
            return $ID;
        else
            return self::$Assets[$ID];
    }
}
