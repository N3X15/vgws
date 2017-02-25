<?php
define('ERROR_HANDLER_SET',1);
set_error_handler(function ($code, $errstr, $errfile, $errline) {
    echo "/* $code -> $errstr */";
});
require ('../config.php');
require ('../classes/classes.php');

$scss = new \Leafo\ScssPhp\Compiler();
new scss_compass($scss);
$scss->setFormatter("Leafo\ScssPhp\Formatter\Crunched");
$scss->setLineNumberStyle(\Leafo\ScssPhp\Compiler::LINE_COMMENTS);
$scss->addImportPath(SCSS_DIR);
$server = new CMW_SCSS_Server(SCSS_DIR, null, $scss);
$server->serve();
