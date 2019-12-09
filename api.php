<?php
session_start();

require ('../config.php');
require ('../classes/classes.php');

$PI = array();
if(array_key_exists('PATH_INFO', $_SERVER))
    $PI = explode('/', $_SERVER['PATH_INFO']);

RouteRequest($PI,'api_');