<?php
define('ERROR_HANDLER_SET',1);
set_error_handler(function ($code, $errstr, $errfile, $errline) {
    echo "\n/* $code -> $errstr */";
});
require ('../config.php');
require ('../classes/classes.php');

function scss_string($input){
  return sprintf('"%s"',$input);
}

$scss = new \ScssPhp\ScssPhp\Compiler();
#new scss_compass($scss);
$scss->setFormatter('\ScssPhp\ScssPhp\Formatter\Crunched');
$scss->setLineNumberStyle(\ScssPhp\ScssPhp\Compiler::LINE_COMMENTS);
$scss->addImportPath(SCSS_DIR);
$scss->setVariables([
  'base_image_uri' => scss_string(WEB_ROOT.'/img'),
  'base_uri' => scss_string(WEB_ROOT),
  'icon-font-path'=> scss_string(WEB_ROOT.'/fonts/'),
]);
$server = new CMW_SCSS_Server(SCSS_DIR, null, $scss);
$server->serve();
