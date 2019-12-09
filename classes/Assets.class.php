<?php

class Assets{
    public static $Assets = [];
    public static function Get($ID) {
        if(count(array_keys(self::$Assets))==0) {
            self::$Assets = json_decode(file_get_contents(PUBLIC_DIR . '/manifest.json'), true);
        }
        if(!array_key_exists($ID, self::$Assets))
            return $ID;
        else
            return self::$Assets[$ID];
    }
}
