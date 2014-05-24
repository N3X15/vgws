<?php
/**
 * Base Handler
 *
 * Defines the basic features of a URL handler.
 *
 * @package Spaceport
 * @subpackage Pages
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */

/**
 * Validation/error message passed to a field or the entire page.
 * @package Spaceport
 * @subpackage Pages
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */
class Message {
    /**
     * Message itself.
     */
    public $message = '';

    /**
     * Severity of the message.
     * error, warning, or generic
     */
    public $severity = '';

    public function __construct($severity, $message) {
        $this->message = $message;
        $this->severity = $severity;
    }

}

// Handles messages sent from AJAX (ajax=1)
class ActionHandler {
	public $handler;
	public $handleAjaxRequests;
	public $request=array(); // Clone of the request ($_REQUEST)
	public $response='';
	public $error='';
	
	public function __construct($handler,$ajax=false) {
		$this->handler=$handler;
		$this->handleAjaxRequests=$ajax;
	}
}

/**
 * Base Page class.
 *
 * The class from which all pages are born or whatever.
 * @package Spaceport
 * @subpackage Pages
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */
class Page {
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
     * Savant3 Engine
     */
    public $tpl = NULL;

    /**
     * ADODB Connection
	 * (Older code had global $db everywhere)
     */
    public $db = NULL;
	
	/**
	 * Registered actions (for AJAX etc)
	 */
	private $actions = array();

    public static function Register($route, $handler) {
        self::$registeredPages[$route] = $handler;
    }

    public static function HandleRequest($route, $pathInfo) {
        //var_dump(self::$registeredPages);
        if (key_exists($route, self::$registeredPages)) {
            self::$registeredPages[$route]->handle($pathInfo);
        } else {
            //header('HTTP/1.1 404 Not found');
            error(sprintf('Unhandled route "%s".  Are you trying to access a page that doesn\'t exist?', htmlentities($route)));
        }
    }

    public function __construct() {
        global $db, $tpl;
        $this->db = &$db;
        $this->tpl = &$tpl;
    }

    public function SetAction($actionName, $handler, $ajax=false) {
    	$this->actions[$route] = new ActionHandler($handler,$ajax);
    }

    public function assignTemplateUserVars() {
        $arr = array('user' => Authentication::GetUser(), 'expires' => Authentication::GetExpireTime(), 'sessid' => Authentication::GetSessionID());
        foreach ($arr as $k => $v)
            $this->setTemplateVar($k, $v);
    }

    /**
     * Assign a template variable, accessible with $this->$k.
     */
    public function setTemplateVar($k, $v) {
        $this->tpl->assign($k, $v);
    }

    /**
     * Debugging method for dumping page and field messages.
     */
    public static function DumpMessages() {
        var_dump(Page::$messages);
    }

    /**
     * Add an error to the page or a field.
     *
     * @param message Message itself
     * @param fieldName Field to attach to
     */
    public function addError($message, $fieldName = '__GLOBAL__') {
        Page::Message('error', $message, $fieldName);
    }

    /**
     * Add a warning to the page or a field.
     *
     * @param message Message itself
     * @param fieldName Field to attach to
     */
    public function addWarning($message, $fieldName = '__GLOBAL__') {
        Page::Message('warning', $message, $fieldName);
    }

    /**
     * Add a generic message to the page or a field.
     *
     * @param message Message itself
     * @param fieldName Field to attach to
     */
    public function addMessage($message, $fieldName = '__GLOBAL__') {
        Page::Message('generic', $message, $fieldName);
    }

    /**
     * Add a message to the page or a field.
     *
     * @param message Message itself
     * @param fieldName Field to attach to
     */
    public static function Message($severity, $message, $fieldName = '__GLOBAL__') {
        if (!array_key_exists($fieldName, self::$messages))
            self::$messages[$fieldName] = array();
        self::$messages[$fieldName][] = new Message($severity, $message);
    }

    /**
     * Retrieve messages for a field or globally.
     * @param fieldName field to get messages for.
     */
    public static function GetMessages($fieldName = '__GLOBAL__') {
        if (!array_key_exists($fieldName, self::$messages))
            return array();
        return self::$messages[$fieldName];
    }

    /**
     * Retrieve messages for a field or globally.
     * @param fieldName field to get messages for.
     */
    public static function HaveMessages($fieldName = '__GLOBAL__') {
        return array_key_exists($fieldName, self::$messages);
    }
	
	/**
	 * Convenience function that assigns a value from, say, $_POST to a QuickHTML field.
	 * @param $_input Either $_POST or $_GET
	 * @param $fieldName Name of the field
	 * @param $fieldArray name => field array
	 */
	public function setFormValue(array $_input,$fieldName,&$fieldArray) {
		if(!array_key_exists($fieldName,$fieldArray))
			error('PROGRAMMING SCREWUP: Someone didn\'t feed Page::setFormValue a $fieldArray with '.$fieldName.' as a key somewhere.');
		$value='';
		if(array_key_exists($fieldName,$_input))
			$value=$_input[$fieldName];
		$fieldArray[$fieldName]->setValue($value);
	}

    /**
     * Set up an internal redirect.
     */
    public function setRedirect(array $to, $seconds) {
        header("Refresh: {$seconds}," . fmtURL($to));
    }

    /**
     * Display something with Savant.
     */
    public function displayTemplate($id) {
        if (!file_exists(TEMPLATE_DIR . '/' . $id))
            error('Template ' . TEMPLATE_DIR . '/' . $id . ' cannot be found.');
        return $this->tpl->fetch($id);
    }

    /**
     * Wrapper around OnBody and friends.
     */
    public function handle($pi) {
        $this->path = $pi;
		$user=null;
		// If we're logged in, set the appropriate template vars.
        if (Authentication::AmLoggedIn()){
            $this->assignTemplateUserVars();
			$user=Authentication::GetUser();
		}
		
		if(!$this->OnPermissionsCheck($user))
			UserError('You have insufficient permissions to access this page.');
		
		// If we're handling AJAX, don't wrap in a template.
        if ($this->IsAJAX())
            echo $this->OnBody();
        else {
            $this->tpl->assign('links', $this->OnLinks());
            $this->tpl->assign('body', $this->OnBody());
            $this->tpl->assign('subpagelinks', $this->OnSubPages());
            $this->tpl->assign('title', $this->title);
            $this->tpl->assign('head', $this->OnHeader());

            $this->tpl->display('wrapper.tpl.php');
        }
    }

    /**
     * Override to define whether a request is being sent from AJAX and should
     * not return the wrapper.
     */
    public function IsAjax() {
        return false;
    }

    /**
     * Create the links array.
     */
    public function OnLinks() {
        $links = array();
        foreach (self::$registeredPages as $key => $handler) {
            if (startsWith($key, 'web_'))
                $key = substr($key, 4);
            if (!array_key_exists($handler->parent, $links))
                $links[$handler->parent] = array();
            $links[$handler->parent][$key] = $handler->description;
        }
        return $links;
    }

    /**
     * Override to determine which pages will be displayed in the subpages bar.
     */
    public function OnSubPages() {
        return null;
    }

    /**
     * Override this to give your handler a body.
     */
    public function OnBody() {
        return '';
    }

    /**
     * Override this to check for access prior to rendering the page.
	 * 
	 * Called before OnBody.
	 * 
	 * @return True if access is granted.
     */
    public function OnPermissionsCheck($user) {
        return true;
    }

    /**
     * Override to add stuff to the header. (Just above </head> tag)
     */
    public function OnHeader() {
        return '';
    }

}
