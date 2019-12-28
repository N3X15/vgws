<?php
/**
 * Base Handler
 *
 * Defines the basic features of a URL handler.
 *
 * @package vgstation-13
 * @subpackage Pages
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */

/**
 * Validation/error message passed to a field or the entire page.
 * @package vgstation-13
 * @subpackage Pages
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */
class Message
{
    /**
     * Message itself.
     */
    public $message = '';

    /**
     * Severity of the message.
     * error, warning, or generic
     */
    public $severity = '';

    public function __construct($severity, $message)
    {
        $this->message = $message;
        $this->severity = $severity;
    }

}

/**
 * Used to add external links to the navigation bar.
 */
class ExternalLinkHandler extends Page
{
    public $url = '';
    public $parent = '/';
    public function __construct($label, $img, $uri)
    {
        $this->description = $label;
        $this->image = $img;
        $this->url = $uri;
    }

}

// Handles messages sent from AJAX (ajax=1)
class ActionHandler
{
    public $page;
    public $handleAjaxRequests;
    public $path = array();
    /**
     *  Clone of the request ($_REQUEST)
     */
    public $request = array();
    public $response = '';
    public $error = '';
    public $tpl;

    /**
     * Hide all links on the page?
     */
    public $hideLinks = false;

    public function __construct($page, $ajax = false)
    {
        //$this->tpl = Page::GetSavant();
        //var_dump($handler);
        $this->page = $page;
        $this->handleAjaxRequests = $ajax;
    }

    public function OnRequest()
    {
        // Override
    }

    public function CanAccess() {
        return true;
    }

}

class AdminActionHandler extends ActionHandler {
    protected $requiredFlags = R_ADMIN;
    protected function RequireFlags($flags) {
        if($this->page->sess!=false)
            return false;
        return ($this->page->sess->flags & $flags) == $flags;
    }

    public function CanAccess() {
        return $this->RequireFlags($this->requiredFlags);
    }
}

/**
 * Base Page class.
 *
 * The class from which all pages are born or whatever.
 * @package vgstation-13
 * @subpackage Pages
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */
class Page
{
    public static $SiteLinks=[];

    /**
     * Pages registered for routing.  key => class
     */
    public static $registeredPages = array();

    /**
     * Messages queued to be displayed. [fieldname|global][severity]=message
     */
    private static $messages = array();

    /**
     * What will be appended to the title.
     */
    public $title = "";

    /**
     * URL path components.
     *
     * 0 is the page and so on.
     */
    public $path = array();

    /**
     * Twig Engine
     */
    public static $tpl = null;


    /**
     * Admin
     * @type Admin
     */
    public $user = null;

    /**
     * ADODB Connection
     * (Older code had global $db everywhere)
     */
    public $db = NULL;

    /**
     * Registered actions (for AJAX etc)
     */
    private $actions = array();

    public $scripts = [];
    public $js_assignments=[];

    public $image = null;

    public $description = 'N/A';

    public $sess = null;

    public $adminOnly = false;

    /**
     * Twig variables.
     */
    private $tmplVars = array();

    public $wrapper = 'wrapper'; // wrapper template ID

    public static function Register($route, $handler)
    {
        self::$registeredPages[$route] = $handler;
    }

    public static function HandleRequest($route, $pathInfo)
    {
        //var_dump(self::$registeredPages);
        if (key_exists($route, self::$registeredPages)) {
            self::$registeredPages[$route]->handle($pathInfo);
        } else {
            //header('HTTP/1.1 404 Not found');
            error(sprintf('Unhandled route "%s".  Are you trying to access a page that doesn\'t exist?', htmlentities($route)));
        }
    }

    public function __construct()
    {
      $this->scripts []= Assets::Get('js/core.min.js');
    }

    public function getURL() {
      if(!empty($this->url))
        return $this->url;
      return fmtURL(explode('/',$this->relurl));
    }

    public function RegisterAction($actionName, $handler)
    {
        $this->actions[$actionName] = $handler;
    }

    public function assignTemplateUserVars()
    {
        $this->setTemplateVar('session', $this->sess);
        $this->setTemplateVar('user', $this->user);
        /*
         $arr = array('user' => Authentication::GetUser(), 'expires' => Authentication::GetExpireTime(), 'sessid' => Authentication::GetSessionID());
         foreach ($arr as $k => $v)
         $this->setTemplateVar($k, $v);
         */
    }

    /**
     * Assign a template variable, accessible with $this->$k.
     */
    public function setTemplateVar($k, $v)
    {
        //$this->tpl->assign($k, $v);
        $this->tmplVars[$k] = $v;
    }

    /**
     * Debugging method for dumping page and field messages.
     */
    public static function DumpMessages()
    {
        var_dump(Page::$messages);
    }

    public static function Initialize()
    {
        $loader = new \Twig\Loader\FilesystemLoader(TEMPLATE_DIR);
        self::$tpl = new \Twig\Environment($loader, array('cache' => CACHE_DIR . '/cache', 'strict' => true, 'debug' => true));
        //self::$tpl->addExtension(new KuTwigExtension());
        self::$tpl->addExtension(new VGWSExtension());
    }

    /**
     * Add an error to the page or a field.
     *
     * @param message Message itself
     * @param fieldName Field to attach to
     */
    public function addError($message, $fieldName = '__GLOBAL__')
    {
        Page::Message('error', $message, $fieldName);
    }

    /**
     * Add a warning to the page or a field.
     *
     * @param message Message itself
     * @param fieldName Field to attach to
     */
    public function addWarning($message, $fieldName = '__GLOBAL__')
    {
        Page::Message('warning', $message, $fieldName);
    }

    /**
     * Add a generic message to the page or a field.
     *
     * @param message Message itself
     * @param fieldName Field to attach to
     */
    public function addMessage($message, $fieldName = '__GLOBAL__')
    {
        Page::Message('generic', $message, $fieldName);
    }

    /**
     * Add a message to the page or a field.
     *
     * @param message Message itself
     * @param fieldName Field to attach to
     */
    public static function Message($severity, $message, $fieldName = '__GLOBAL__')
    {
        if (!array_key_exists($fieldName, self::$messages))
            self::$messages[$fieldName] = array();
        self::$messages[$fieldName][] = new Message($severity, $message);
    }

    /**
     * Retrieve messages for a field or globally.
     * @param fieldName field to get messages for.
     */
    public static function GetMessages($fieldName = '__GLOBAL__')
    {
        if (!array_key_exists($fieldName, self::$messages))
            return array();
        return self::$messages[$fieldName];
    }

    /**
     * Retrieve messages for a field or globally.
     * @param fieldName field to get messages for.
     */
    public static function HaveMessages($fieldName = '__GLOBAL__')
    {
        return array_key_exists($fieldName, self::$messages);
    }

    /**
     * Convenience function that assigns a value from, say, $_POST to a QuickHTML field.
     * @param $_input Either $_POST or $_GET
     * @param $fieldName Name of the field
     * @param $fieldArray name => field array
     */
    public function setFormValue(array $_input, $fieldName, &$fieldArray)
    {
        if (!array_key_exists($fieldName, $fieldArray))
            error('PROGRAMMING SCREWUP: Someone didn\'t feed Page::setFormValue a $fieldArray with ' . $fieldName . ' as a key somewhere.');
        $value = '';
        if (array_key_exists($fieldName, $_input))
            $value = $_input[$fieldName];
        $fieldArray[$fieldName]->setValue($value);
    }

    /**
     * Set up an internal redirect.
     */
    public function setRedirect(array $to, $seconds)
    {
        header("Refresh: {$seconds}," . fmtURL($to));
    }

    /**
     * Display something with Twig.
     */
    public function displayTemplate($id)
    {
        $file = TEMPLATE_DIR . '/' . $id . '.twig';
        if (!file_exists($file)) {
            error('Template ' . $file . ' cannot be found.');
        }
        //Kint::dump($file,$this->tmplVars);
        //return self::$tpl->fetch($id . '.tpl.php');
        try {
            return self::$tpl->render($id . '.twig', $this->tmplVars);
        } catch (Exception $e) {
            #error($e->getMessage());
            error('<pre>'.strval($e).'</pre>');
            error('<pre>'.strval(self::$tpl->__toString()).'</pre>');
        }
    }

    /**
     * Wrapper around OnBody and friends.
     */
    public function handle()
    {
        //$this->path = $pi;
        $user = null;

        if (array_key_exists('s', $_REQUEST)) {
            $this->sess = AdminSession::FetchSessionFor($_REQUEST['s']);
            if ($this->sess != false)
                $_SESSION['s'] = $_REQUEST['s'];
        } elseif (array_key_exists('s', $_SESSION)) {
            $this->sess = AdminSession::FetchSessionFor($_SESSION['s']);
        }

        $this->user = null;
        if($this->sess!=null)
            $this->user = Admin::FindCKey($this->sess->ckey);

        $this->assignTemplateUserVars();
        // Cleaned.

        if ($this->adminOnly && !$this->sess) {
            header('HTTP/1.1 403 Forbidden');
            UserError('Access Denied');
            return;
        }

        if (!$this->OnPermissionsCheck($user))
            UserError('You have insufficient permissions to access this page.');

        // If we're handling AJAX, don't wrap in a template.
        if ($this->IsAJAX()) {
            //echo $this->OnBody();
            $act = $this->GetAjaxAction();
            if (array_key_exists($act, $this->actions)) {
                $ct = 'application/json';
                if (array_key_exists('content-type', $_REQUEST))
                    $ct = $_REQUEST['content-type'];
                header('Content-Type: ' . $ct);
                $ah = $this->actions[$act];
                if (!$ah->handleAjaxRequests)
                    return;
                //$ah->handler = $this;
                $ah->response = array('status' => false);
                $ah->path = &$this->path;
                $ah->request = $_REQUEST;
                $ah->OnRequest();
                //echo "COMPLETED";
                #var_dump($ah->response);
                $json = json_encode($ah->response, JSON_UNESCAPED_UNICODE);
                if (!$json || $json == '') {
                    $msg = json_last_error_msg();
                    echo '{"status":false,"message":"Failed to encode JSON: \\"' . $msg . '\\""}';
                }
                die($json);
            } else {
                die(json_encode(array('status' => false, 'message' => 'Unknown route "' . $act . '".')));
            }
        } else {
            $act = $this->GetAjaxAction();
            $actResponse = null;
            $actHideLinks = false;
            if (array_key_exists($act, $this->actions)) {
                $ah = $this->actions[$act];
                if (!$ah->handleAjaxRequests) {
                    $ah->response = null;
                    $ah->path = &$this->path;
                    $ah->request = $_REQUEST;
                    if ($ah->onRequest()) {
                        // Handler requesting override of OnBody().
                        $actResponse = $ah->response;
                    }
                    $actHideLinks = $ah->hideLinks;
                }
            }

            $this->js_assignments['WEB_ROOT'] = WEB_ROOT;
            $this->js_assignments['API_PHP_URL'] = WEB_ROOT."/api.php";
            $this->js_assignments['INDEX_PHP_URL'] = WEB_ROOT."/index.php";

            $this->setTemplateVar('head', $this->onHeader());
            $this->setTemplateVar('subpagelinks', $this->onSubPages());
            $this->setTemplateVar('body', ($actResponse == null) ? $this->onBody() : $actResponse);
            $this->setTemplateVar('links', Page::$SiteLinks);
            #$this->setTemplateVar('user', $this->user);
            #$this->setTemplateVar('stylesheets', $this->stylesheets);
            $this->setTemplateVar('scripts', $this->scripts);
            $this->setTemplateVar('title', $this->title);
            $this->setTemplateVar('js_vars', $this->js_assignments);

            echo $this->displayTemplate($this->wrapper);
        }
    }

    /**
     * Override to define whether a request is being sent from AJAX and should
     * not return the wrapper.
     */
    public function IsAjax()
    {
        return isset($_REQUEST['ajax']);
    }

    /**
     * What action do we call?
     */
    public function getAjaxAction()
    {
        if (isset($_REQUEST['act'])) {
            return $_REQUEST['act'];
        }
        return null;
    }

    /**
     * Create the links array.
     */
    public function OnLinks()
    {
        $links = array();
        foreach (self::$registeredPages as $key => $handler) {
            if (startsWith($key, 'web_'))
                $key = substr($key, 4);
            $url = fmtURL($key);
            if (get_class($handler) == 'ExternalLinkHandler')
                $url = $handler->url;
            if (!array_key_exists($handler->parent, $links))
                $links[$handler->parent] = array();
            $links[$handler->parent][$key] = array('image' => $handler->image, 'desc' => $handler->description, 'url' => $url);
        }
        return $links;
    }

    /**
     * Override to determine which pages will be displayed in the subpages bar.
     */
    public function OnSubPages()
    {
        return null;
    }

    /**
     * Override this to give your handler a body.
     */
    public function OnBody()
    {
        return '';
    }

    /**
     * Override this to check for access prior to rendering the page.
     *
     * Called before OnBody.
     *
     * @return True if access is granted.
     */
    public function OnPermissionsCheck($user)
    {
        return true;
    }

    /**
     * Override to add stuff to the header. (Just above </head> tag)
     */
    public function OnHeader()
    {
        return '';
    }


    /**
     * Klein shit
     */
    public function handleKleinRequest($request, $response, $service, $app)
    {
        $this->request=$request;
        $this->response=$response;
        $this->handle();
    }
    public function skipThis()
    {
        throw new \Klein\Exceptions\DispatchHaltedException(null, \Klein\Exceptions\DispatchHaltedException::SKIP_THIS);
    }

}
