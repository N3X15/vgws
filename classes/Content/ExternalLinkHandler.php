<?php
namespace VGWS\Content;
/**
 * Used to add external links to the navigation bar.
 */
class ExternalLinkHandler extends BaseHandler {
	public $parent = '/';
	public $url='';
	public function __construct($label,$img,$uri) {
		$this->description=$label;
		$this->image=$img;
		$this->url=$uri;
	}
}
