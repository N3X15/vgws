<?php
/**
 * From ChanMan
 * MIT License
 * Also by me and I don't give a fuck - N3X
 */
class Router
{
    public static $router = null;
    public static $pages = [];

    public static function Initialize()
    {
        if (self::$router == null) {
            // Update request when we have a subdirectory
            if(isset($_SERVER['REQUEST_URI'])) {
                if(ltrim(WEB_ROOT, '/')){
                    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], strlen(WEB_ROOT));
                }
                if(startswith($_SERVER['REQUEST_URI'],'/index.php')){
                    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 10);
                }
            }

            self::$router = new \Klein\Klein();
            self::$router->respond(function($request, $response, $service, $app)
            {
                self::$router->onError(function($klein, $errMsg)
                {
                    error($errMsg);
                });
            });
            // Using exact code behaviors via switch/case
            self::$router->onHttpError(function ($code, $router) {
                switch ($code) {
                    case 404:
                        $pg = "<h2>Error: 404</h2><p>Available pages:</p><ul>";
                        foreach(self::$pages as $route => $page) {
                            $pg .= "<li><b>{$route}:</b> {$page->title}</li>";
                        }
                        $pg .="</ul><p>Sent pathname: {$router->request()->pathname()}</p>";
                        error($pg);
                        break;
                    case 405:
                        $router->response()->body(
                            'You can\'t do that!'
                        );
                        break;
                    default:
                        $router->response()->body(
                            'Oh no, a bad error happened that caused a '. $code
                        );
                }
            });
            //echo("Klein initialized.");
        }
    }

    public static function Request()
    {
        self::Initialize();
        return self::$router->request();
    }

    public static function Register($format, Page $page)
    {
        self::Initialize();
        self::$pages[$format]=$page;
        self::$router->respond(['GET', 'POST'], $format, array($page,'handleKleinRequest'));
    }

    public static function Route()
    {
        self::Initialize();
        self::$router->dispatch();
    }

}
