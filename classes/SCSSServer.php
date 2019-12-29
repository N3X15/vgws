<?php
// From ChanMan Web Services (A private project, thus far)
namespace VGWS;
use ScssPhp\ScssPhp\Server;
class SCSSServer extends Server
{
    protected $dir='style';
    /**
     * The old version of this only accepted the .scss
     * extension.
     *
     * This is slightly smarter and will accept .css.
     */
    protected function findInput()
    {
        if (($input = $this->inputName()) && strpos($input, '..') === false) {
            $ext_accepted = false;
            $extpos = strrpos($input, '.');
            if ($extpos === false)
                return false;
            //echo "/*$input*/\n";
            $ext = substr($input, $extpos - strlen($input));
            //print("\n//EXT: ".$ext);
            $stripped_fn = substr($input, 0, $extpos);
            //print("\n//SFN: ".$stripped_fn);

            switch($ext) {
                case '.scss' :
                case '.css' :
                    $ext_accepted = true;
                    break;
            }

            $name = $this->join(SCSS_DIR, $stripped_fn . '.scss');
            //echo "/*$name*/\n";
            if (is_file($name) && is_readable($name)) {
                return $name;
            }
        }

        return false;
    }

}
