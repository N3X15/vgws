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

// Publically-viewable crap.
define('PUBLIC_DIR', PATH_ROOT . '/htdocs');

// Libs
require_once ('Savant3.php');
require_once ("adodb/adodb-exceptions.inc.php");
require_once ('adodb/adodb.inc.php');

require_once PATH_ROOT . '/vendor/autoload.php';

// Just loads all of the classes without screwing around with 50 includes.

require_once CORE_DIR . '/funcs.php';
require_once CORE_DIR . '/Debug.class.php';
require_once CORE_DIR . '/SCSS.class.php';
require_once CORE_DIR . '/DB.class.php';
require_once CORE_DIR . '/HTML/Element.class.php';
require_once CORE_DIR . '/HTML/PForm.class.php';
#require_once CORE_DIR . '/HTML/KuForm.class.php';
require_once CORE_DIR . '/DBTable.class.php';
require_once CORE_DIR . '/Page.class.php';
require_once CORE_DIR . '/Admin.class.php';
require_once CORE_DIR . '/QF.class.php';
require_once CORE_DIR . '/Jobs.class.php';
require_once CORE_DIR . '/Poll.class.php';

$ACT_HANDLERS = array();

// WEBSITE HANDLERS
require_once CORE_DIR . '/handlers/web/web_home.class.php';
require_once CORE_DIR . '/handlers/web/web_admins.class.php';
require_once CORE_DIR . '/handlers/web/web_bans.class.php';
require_once CORE_DIR . '/handlers/web/web_rapsheet.class.php';
require_once CORE_DIR . '/handlers/web/web_poll.class.php';

// SERVER API HANDLERS
require_once CORE_DIR . '/handlers/api/api_chkban.class.php';
require_once CORE_DIR . '/handlers/api/api_findcid.class.php';

////////////////////////////////
// Setup database
////////////////////////////////
if (!defined('DB_DSN'))
    error('You forgot to set up DB_DSN in config.php.  {$driver}://{$username}:{$password}@{$hostname}/{$schema}[?persist] (use rawurlencode on the password if needed.)');

DB::Initialize();
//$db->debug=true;

////////////////////////////////
// Set up Savant 3
////////////////////////////////
$tpl = new Savant3();
$tpl->addPath('template', CORE_DIR . '/../templates');
