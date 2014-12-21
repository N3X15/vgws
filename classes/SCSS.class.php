<?php
// From ChanMan Web Services (A private project, thus far)

class CMW_SCSS_Server extends scss_server {
    /**
     * The old version of this only accepted the .scss
     * extension.
     *
     * This is slightly smarter and will accept .css.
     */
    function findInput() {
        if (($input = $this->inputName()) && strpos($input, '..') === false) {
            $ext_accepted = false;
            $extpos = strrpos($input, '.');
            if ($extpos === false)
                return false;
            $ext = substr($input, $extpos-strlen($input));
            $stripped_fn = substr($input, 0, $extpos);

            switch($ext) {
                case '.scss' :
                case '.css' :
                    $ext_accepted = true;
                    break;
            }

            $name = $this->join($this->dir, $stripped_fn.'.scss');
            if (is_file($name) && is_readable($name)) {
                return $name;
            }
        }

        return false;
    }

}
