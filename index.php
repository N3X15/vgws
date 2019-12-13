<?php
session_start();

error_reporting(E_ALL & ~(E_NOTICE));

require ('../config.php');

// This is invalid because cookies, apparently.
//header("Access-Control-Allow-Origin: *");
// This is invalid because CORS' spec says one thing and browsers do another.
//header('Access-Control-Allow-Origin: '.implode(' ', $CORS_ALLOWED_DOMAINS));
header_remove('x-powered-by');
header_remove('X-Frame-Options');

require ('../classes/classes.php');

// This is just for the header.  Routing is handled by Router.
Page::$SiteLinks=[
  new HomePage(),        // /?
  new AdminListPage(),   // /admins/?
  new BanListPage(),     // /bans/?
  new PollListPage(),    // /poll/?
  new ExternalLinkHandler('Forums', '/img/forum.png', '/forum/'),
  new ExternalLinkHandler('Wiki', '/img/wiki.png', '/wiki/'),
  new ExternalLinkHandler('GitHub', '/img/pinned-octocat.svg', 'https://github.com/d3athrow/vgstation13'),
  new ExternalLinkHandler('Minimaps', '/img/map.png', 'http://game.ss13.moe/minimaps'),
  new ExternalLinkHandler('Statistics', '/img/statsicon.png', 'http://stats.ss13.moe'),
];

Router::Route();
