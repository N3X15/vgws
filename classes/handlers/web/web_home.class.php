<?php

class HomePage extends Page {
  public $relurl = '/';
	public $title = "Home";
	public $image = "/img/home.png";

	public function OnBody() {
		return $this->displayTemplate('web/home');
	}

}

Router::Register('/?', new HomePage());
