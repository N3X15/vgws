<?php

class homepage_handler extends Page {
	public $parent = '/';
	public $title = "Home page";
	public $image = "/img/home.png";

	public function OnBody() {
		global $tpl, $db, $ALLOWED_TAGS;
		return $tpl->fetch('web/home.tpl.php');
	}

}

Page::Register('web_home', new homepage_handler);