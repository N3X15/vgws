<?php
use \VGWS\Content\Page;
use \VGWS\Lobby\Animation;
use \VGWS\Lobby\Pool;
class LobbyScreen extends Page {
  public $relurl = '/lobby';
	public $title = "Lobby";

  public $wrapper = '';
	//public $image = "/img/home.png";

  const ALPHANUMERIC = '/^[a-zA-Z0-9]+$/';
  const FILENAME = '/^[a-zA-Z0-9_\-]+$/';
	public function OnBody() {
    /***
      REMEMBER: The players connect here from their clients.  Do not trust them.
    ***/
    $poolID = filter_input(INPUT_GET, 'pool', FILTER_VALIDATE_REGEXP, ['options'=>['default'=>'main', 'regexp'=>self::FILENAME]]);
    $animID = filter_input(INPUT_GET, 'anim', FILTER_VALIDATE_REGEXP, ['options'=>['regexp'=>self::ALPHANUMERIC]]);
    // Not set, so set to null.
    if($animID === false)
      $animID = null;

    // Statistics and template usage
    $map  = filter_input(INPUT_GET, 'map',  FILTER_VALIDATE_REGEXP, ['options'=>['default'=>'', 'regexp'=>self::FILENAME]]);
    $ckey = filter_input(INPUT_GET, 'ckey', FILTER_VALIDATE_REGEXP, ['options'=>['default'=>'', 'regexp'=>self::FILENAME]]);

    // Adminbus
    $adminOverrides = null;
    if(file_exists(PATH_ROOT.'/data/lobby_overrides.json'))
      $adminOverrides = json_decode(file_get_contents(PATH_ROOT."/data/lobby_overrides.json"), true);

    $pools = json_decode(file_get_contents(PATH_ROOT."/data/lobby.json"), true);
    if(!array_key_exists($poolID, $pools))
      die('ERROR: You you were given a lobby screen pool that doesn\'t exist. Please file a bug report.');

    $pool = new Pool();
    $pool->ID = $poolID;
    $pool->deserialize($pools[$poolID]);

    if($animID != null && !array_key_exists($animID, $pool->animationsByID)){
      $animID = htmlentities(var_export($animID, true));
      die("ERROR: The animation ID {$animID} doesn't exist in pool {$pool->ID}! Please file a bug report.");
    }

    $animation = null;
    if($animID == null){
      $animation = $pool->pick();
      if($animation == null)
        die('Could not find an animation in pool.');
    } else {
      $animation = $pool->animationsByID[$animID];
    }

    $playlist = $pool->playlist;
    if($animation->overridePlaylist != null)
      $playlist = $animation->overridePlaylist;
    if($adminOverrides != null && array_key_exists('playlist', $adminOverrides))
      $playlist = $adminOverrides['playlist'];
    if(isset($_GET['playlist']))
      $playlist = filter_input(INPUT_GET, 'playlist', FILTER_VALIDATE_REGEXP, ['options'=>['default'=>'main', 'regexp'=>self::FILENAME]]);

    $template = $pool->template;
    if($animation->overrideTemplate != null)
      $template = $animation->overrideTemplate;
    if($adminOverrides != null && array_key_exists('template', $adminOverrides))
      $template = $adminOverrides['template'];

    $url = $animation->url;
    if(strpos($url, ':') === FALSE)
      $url = WEB_ROOT."/img/lobby/{$poolID}/{$animation->url}";
    if($adminOverrides != null && array_key_exists('url', $adminOverrides))
      $url = $adminOverrides['url'];

    if($animation->scripts != null) {
      foreach($animation->scripts as $scr) {
        $this->scripts[] = $scr;
      }
    }

    $this->scripts = [
      'js/byond.js',
      'js/core.js',
      'js/lobby-core.js',
    ];

    $animation->url = $url;

    $this->js_assignments['MEDIA_BASEURL'] = MEDIA_BASEURL;
    $this->js_assignments['MEDIA_KEY'] = shortEncode(MEDIA_KEY);
    $this->js_assignments['PLAYLIST'] = $playlist;
    $this->js_assignments['BG_URL'] = $url;
    $this->js_assignments['ANIMATION'] = $animation;
    $this->js_assignments['POOL_ID'] = $poolID;
    $this->js_assignments['ANIM_ID'] = $animID;
    $this->js_assignments['ANIM_DATA'] = $animation->data;

    $this->setTemplateVar('IMAGE', $url);
    $this->setTemplateVar('POOL_ID', $poolID);
    $this->setTemplateVar('ANIM_ID', $animID);
    $this->setTemplateVar('ANIM_DATA', $animation->data);
    $this->setTemplateVar('PLAYLIST', $playlist);
    $this->setTemplateVar('map', $map);
    $this->setTemplateVar('ckey', $ckey);
    #$this->setTemplateVar('scripts', $this->scripts);
		#die($this->displayTemplate("ingame/lobby/{$template}"));
    $this->setTemplateVar('UA', $_SERVER['HTTP_USER_AGENT']);
		$this->wrapper = "ingame/lobby/{$template}";
	}

}

\VGWS\Router::Register('/lobby?', new LobbyScreen());
