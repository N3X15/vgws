<?php
/**
 * Miscellaneous functions.
 *
 * @package vgstation13-web
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */

function fmtUserLink($user, $content = null) {
    $userName = $user;
    if (is_a($user, 'User'))
        $userName = $user->Name;
    if ($content == null)
        $content = htmlentities($userName);
    return new Element('a', array('href' => fmtURL('user', $userName)), $content);
}

function replace_extension($filename, $new_extension) {
    $info = pathinfo($filename);
    return $info['filename'] . '.' . $new_extension;
}

function RouteRequest($PI, $key_prefix = 'web_') {
    array_shift($PI);
    if (count($PI) == 0) {
        $PI = array('home');
    }
    switch($PI[0]) {
        // DEBUGGING:  Show all registered ACT HANDLERS
        case 'showact' :
            echo "<h1>Registered Action Handlers</h1><ul>";
            foreach (Page::$registeredPages as $name => $handler) {
                echo "<li>{$name} - {$handler->description} ({$handler->version})</li>";
            }
            echo '</ul>';
            break;

        // Find a handler and handle it.
        default :
            $handlerkey = $key_prefix . $PI[0];
            if ($handlerkey == $key_prefix)
                $handlerkey = $key_prefix . 'home';
            Page::HandleRequest($handlerkey, $PI);
    }
}

function parseTags($tags) {
    $tagCollection = array();
    $ctag = "";
    $inQuotes = false;

    foreach (str_split($tags) as $c) {
        switch ($c) {
            case ' ' :
                if ($inQuotes)
                    $ctag .= ' ';
                else {
                    $tagCollection[] = $ctag;
                    $ctag = "";
                }
                break;
            case '"' :
                if ($inQuotes) {
                    $tagCollection[] = $ctag;
                    $ctag = "";
                    $inQuotes = false;
                } else {
                    $inQuotes = true;
                }
                break;
            default :
                $ctag .= $c;
                break;
        }
    }
    if (!empty($ctag)) {
        $tagCollection[] = $ctag;
    }
    return $tagCollection;
}

function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function urlFmtTag($tag) {
    if (is_array($tag)) {
        $o = array();
        foreach ($tag as $t) {
            $o[] = urlFmtTag($t);
        }
        return implode(' ', $o);
    }
    return str_replace(' ', '_', $tag);
}

function unescapeTag($tag) {
    return str_replace('_', ' ', $tag);
}

# remove by key:
function array_remove_key() {
    $args = func_get_args();
    return array_diff_key($args[0], array_flip(array_slice($args, 1)));
}

# remove by value:
function array_remove_value() {
    $args = func_get_args();
    return array_diff($args[0], array_slice($args, 1));
}

/**
 * Format URL.
 *
 * @param act The action to invoke
 * @param ... Additional "directories" to append to the URL.
 */
function fmtURL() {
    $args = func_get_args();
    if (is_array($args[0]))
        $args = $args[0];
    $o = WEB_ROOT . '/index.php';
    if (count($args) > 0) {
        foreach ($args as $arg) {
            if(!empty($arg))
              $o .= '/' . $arg;
        }
    }
    return $o;
}

/**
 * Format APIURL.
 *
 * @param act The action to invoke
 * @param ...
 */
function fmtAPIURL() {
    $args = func_get_args();
    $o = WEB_ROOT . '/api.php/' . $args[0];
    array_shift($args);
    foreach ($args as $arg) {
        $o .= '/' . $arg;
    }
    return $o;
}

/**
 * Start a profiling stopwatch.
 */
function startwatch($category) {
    global $sw_start, $sw_elapsed;
    if (!isset($sw_elapsed))
        $sw_elapsed = array();
    if (!isset($sw_start))
        $sw_start = array();
    $sw_start[$category] = microtime(true);
}

/**
 * Stop a profiling stopwatch.
 */
function stopwatch($category) {
    global $sw_start, $sw_elapsed;
    $elapsed = microtime(true) - $sw_start[$category];
    if (!isset($sw_elapsed))
        $sw_elapsed = array();
    if (!isset($sw_start))
        $sw_start = array();
    if (!array_key_exists($category, $sw_elapsed))
        $sw_elapsed[$category] = $elapsed;
    else
        $sw_elapsed[$category] += $elapsed;
    return $elapsed;
}

/**
 * File MIME type.
 */
function getMime($file) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    // return mime type ala mimetype extension
    $o = finfo_file($finfo, $file);
    finfo_close($finfo);
    return $o;
}

/**
 * Indents a flat JSON string to make it more human-readable.
 *
 * @param string $json The original JSON string to process.
 *
 * @return string Indented version of the original JSON string.
 */
function indentJSON($json) {

    $result = '';
    $pos = 0;
    $strLen = strlen($json);
    $indentStr = '  ';
    $newLine = "\n";
    $prevChar = '';
    $outOfQuotes = true;

    for ($i = 0; $i <= $strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
        } else if (($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos--;
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element,
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos++;
            }

            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        $prevChar = $char;
    }

    return $result;
}

function doInsertSQL($table, $row) {
    global $db;
    $col = join(',', array_keys($row));
    $qmarks = join(',', array_fill(0, count($row), '?'));
    $sql = "INSERT INTO `$table` ($col) VALUES ($qmarks)";
    $db->Execute($sql, array_values($row));
}

function doMultInsertSQL($table, $rows, $replace = false) {
    global $db;
    $col = join(',', array_keys($rows[0]));
    $vals = array();
    $valblocks = array();
    foreach ($rows as $row) {
        $valblocks[] = '(' . join(',', array_fill(0, count($row), '?')) . ')';
        foreach (array_values($row) as $val) {
            $vals[] = $val;
        }
    }
    $sql = 'INSERT';
    if ($replace)
        $sql = 'REPLACE';
    $sql .= " INTO `$table` ($col) VALUES " . join(',', $valblocks);
    $db->Execute($sql, $vals);
}

function doReplaceSQL($table, $row) {
    global $db;
    $col = join(',', array_keys($row));
    $qmarks = join(',', array_fill(0, count($row), '?'));
    $sql = "REPLACE INTO `$table` ($col) VALUES ($qmarks)";
    $db->Execute($sql, array_values($row));
}

function doUpdateSQL($table, $row, $where) {
    global $db;
    $sset = array();
    foreach ($row as $key => $val)
        $sset[] = "`$key`=?";
    $set = join(', ', $sset);
    $sql = "UPDATE `$table` SET $set WHERE $where";
    $db->Execute($sql, array_values($row));
}

function file_ext_strip($filename) {
    return preg_replace('/\.[^.]*$/', '', $filename);
}

define('ERROR_CUSTOM_MESSAGE', 1);
define('ERROR_NO_STACKTRACE', 2);

/**
 * Throw an error
 * @param msg Message to send to the client.
 */
function error($msg, $flags = 0) {
    require (TEMPLATE_DIR . '/error.tpl.php');
    exit(1);
}

/**
 * Throw an error. Does NOT show a stacktrace!
 * @param msg Message to send to the client.
 */
function UserError($msg, $flags = 0) {
    error($msg, ERROR_NO_STACKTRACE | $flags);
}

/**
 * Throw a SQL error
 * @param msg Message to send to the client.
 */
function SQLError($msg, $flags = 0, $additional_info = '') {
    if ($additional_info != '') {
        $additional_info = "<h2>Additional Information</h2>{$additional_info}";
    }
    $msg = htmlentities($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $err = <<<ERR
<h1>MySQL Error</h1>
<p>Our database has reported an error, either because of overloading, or because of erroneous coding.  Please notify our administrators if this continues.</p>
{$additional_info}
<h2>Error Message</h2>
<pre>{$msg}</pre>
ERR;
    error($err, ERROR_CUSTOM_MESSAGE | $flags);
}

function subclasses($obj, $class) {
    return is_subclass_of($obj, $class) || get_class($obj) == $class;
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1<<(10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

function parseFileSize($str) {
    $KB_FACTOR = 1024;
    $MB_FACTOR = 1024 * $KB_FACTOR;
    $GB_FACTOR = 1024 * $MB_FACTOR;
    $numpart = '';
    $extpart = '';
    if (endsWith($str, 'KB') || endsWith($str, 'MB') || endsWith($str, 'GB')) {
        $numpart = substr($str, 0, -2);
        $extpart = substr($str, -2, 2);
    } elseif (endsWith($str, 'K') || endsWith($str, 'M') || endsWith($str, 'G')) {
        $numpart = substr($str, 0, -1);
        $extpart = substr($str, -1, 1) . 'B';
    } else {
        error('Unable to identify ' . $str);
    }
    $inumpart = intval($numpart);
    Debug::AssertNot("Failed to parse {$numpart} to integer.", $inumpart, 0);
    switch($extpart) {
        case 'KB' :
            return $inumpart * $KB_FACTOR;
        case 'MB' :
            return $inumpart * $MB_FACTOR;
        case 'GB' :
            return $inumpart * $GB_FACTOR;
        default :
            error($extpart . ' is an unknown filesize suffix.');
    }
}

// Stolen from Space Station 13's english_list proc.
//Returns a list in plain english as a string
function join_english($input, $nothing_text = "nothing", $and_text = " and ", $comma_text = ", ", $final_comma_text = ", ") {
    $total = count($input);
    if ($total == 0)
        return $nothing_text;
    elseif ($total == 1)
        return $input[0];
    elseif ($total == 2)
        return $input[0] . $and_text . $input[1];
    else {
        $output = '';
        for ($index = 0; $index < $total; $index++) {
            if ($index == $total - 2) {
                $comma_text = $final_comma_text;
            }
            $output .= $input[$index] . $comma_text;
            $index++;
        }
        return $output . $and_text . $input[$index];
    }
}

/// XDEBUG FALLBACKS
if (!function_exists('xdebug_dump_superglobals')) {
    function xdebug_dump_superglobals() {
        return '';
        // Teehee.
    }

}

function getFile($file, $filename = '') {
    if (!file_exists($file)) {
        // Throw a 404.
        header("HTTP/1.1 404 Not Found");
        die("NOPE");
    }
    //Gather relevent info about file
    $size = filesize($file);
    $fileinfo = pathinfo($file);

    //workaround for IE filename bug with multiple periods / multiple dots in
    // filename
    //that adds square brackets to filename - eg. setup.abc.exe becomes
    // setup[1].abc.exe
    if ($filename == '')
        $filename = $fileinfo['basename'];
    $filename = (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) ? preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1) : $filename;

    $file_extension = strtolower($fileinfo['extension']);

    $seek_start = '';
    $seek_end = '';

    //check if http_range is sent by browser (or download manager)
    if (isset($_SERVER['HTTP_RANGE'])) {
        list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

        if ($size_unit == 'bytes') {
            //multiple ranges could be specified at the same time, but for
            // simplicity only serve the first range
            //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
            list($range, $extra_ranges) = explode(',', $range_orig, 2);
        } else {
            $range = '';
        }

        //figure out download piece from range (if set)
        list($seek_start, $seek_end) = explode('-', $range, 2);
    } else {
        $range = '';
    }

    //set start and end based on range (if set), else set defaults
    //also check for invalid ranges.
    $seek_end = (empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)), ($size - 1));
    $seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);

    //open the file
    $fp = fopen($file, 'rb');
    if ($fp === FALSE) {
        header('HTTP/1.1 500 Internal Server Error');
        die('ERROR');
    }
    //add headers if resumable
    //Only send partial content header if downloading a piece of the file (IE
    // workaround)
    if ($seek_start > 0 || $seek_end < ($size - 1)) {
        header('HTTP/1.1 206 Partial Content');
    }

    header('Accept-Ranges: bytes');
    header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $size);

    header('Content-disposition: attachment; filename="' . $filename . '"');
    header('Content-type: application/zip');
    header('Content-Length: ' . ($seek_end - $seek_start + 1));

    //seek to start of missing part
    fseek($fp, $seek_start);

    //start buffered download
    while (!feof($fp)) {
        //reset time limit for big files
        set_time_limit(0);
        print(fread($fp, 1024 * 8));
        flush();
        ob_flush();
    }

    fclose($fp);
}

function shortEncode(string $str) {
  $out = [];
  for($i=0;$i<strlen($str);) {
    $ab=ord($str[$i++]);
    if($i<strlen($str)) {
      $ab|=ord($str[$i++])<<16;
    }
    $out[]=$ab;
  }
  return $out;
}
