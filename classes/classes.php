<?php
/**
 * Class Loader
 *
 * Loads up all our requires and sets up paths.
 * @package vgstation13-web
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */
$sw_start['start'] = microtime(true);

define('PATH_ROOT', dirname(dirname(__FILE__)));
define('CORE_DIR', dirname(__FILE__));

// Inclusions and whatnot.
define('LIB_DIR',      PATH_ROOT . '/lib');
define('CACHE_DIR',    PATH_ROOT . "/cache");
define('TEMPLATE_DIR', PATH_ROOT . "/templates");
define('SCSS_DIR',     PATH_ROOT . '/style');
define('DATA_DIR',     PATH_ROOT . '/data');

// Publically-viewable crap.
define('PUBLIC_DIR', PATH_ROOT . '/htdocs');

// Overridable in config
if(!defined('API_PHP_URL')) define('API_PHP_URL', WEB_ROOT."/api.php");
if(!defined('INDEX_PHP_URL')) define('INDEX_PHP_URL', WEB_ROOT."/index.php");

// Libs
#require_once ("adodb/adodb-exceptions.inc.php");
#require_once ('adodb/adodb.inc.php');

require_once PATH_ROOT . '/vendor/autoload.php';

// Stupid fucking decision made by ScssPhp dev means it's not included in the autoload.
require_once PATH_ROOT . '/vendor/scssphp/scssphp/example/Server.php';

// Just loads all of the classes without screwing around with 50 includes.

require_once CORE_DIR . '/funcs.php';

$ACT_HANDLERS = array();

// WEBSITE HANDLERS
require_once CORE_DIR . '/handlers/web/web_home.class.php';
require_once CORE_DIR . '/handlers/web/web_admins.class.php';
require_once CORE_DIR . '/handlers/web/web_bans.class.php';
require_once CORE_DIR . '/handlers/web/web_rapsheet.class.php';
require_once CORE_DIR . '/handlers/web/web_poll.class.php';

require_once CORE_DIR . '/handlers/web/web_lobbyscreen.class.php';

// SERVER API HANDLERS
require_once CORE_DIR . '/handlers/api/api_chkban.class.php';
require_once CORE_DIR . '/handlers/api/api_findcid.class.php';

////////////////////////////////
// Setup database
////////////////////////////////
#if (!defined('DB_DSN'))
#    error('You forgot to set up DB_DSN in config.php.  {$driver}://{$username}:{$password}@{$hostname}/{$schema}[?persist] (use rawurlencode on the password if needed.)');

\VGWS\Content\Page::Initialize();
\Atera\DB::Initialize();
//$db->debug=true;
