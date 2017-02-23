<?php
session_start();
error_reporting(E_ALL & ~(E_NOTICE));

require ('../config.php');
require ('../classes/classes.php');

Page::Register('web_forum', new ExternalLinkHandler('Forums', '/img/forum.png', 'http://vg13.undo.it/forum/index.php'));
Page::Register('web_wiki', new ExternalLinkHandler('Wiki', '/img/wiki.png', '/wiki/'));

$PI = array();
if(array_key_exists('PATH_INFO', $_SERVER))
	$PI = explode('/', $_SERVER['PATH_INFO']);

RouteRequest($PI);
