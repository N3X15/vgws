<?php
namespace VGWS\Content;
class Assets {
    public static $Assets = null;
    public static function Get($ID) {
        if(self::$Assets == null) {
            self::$Assets = json_decode(file_get_contents(PUBLIC_DIR . '/manifest.json'), true);
        }
        #var_dump(self::$Assets);
        if(!array_key_exists($ID, self::$Assets))
            return $ID;
        else
            return self::$Assets[$ID];
    }
}
