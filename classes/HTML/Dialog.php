<?php
/**
 * Dialog Builder
 *
 * Constructs a dialog box.
 *
 * @package Spaceport
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */
namespace VGWS\HTML;
class DialogBuilder {
	public $choices = array();
	public $title = '';
	public $body;

	public function __construct() {
		$this->body = new Element('div',array('class'=>'dlgBody'));
	}

	public function RenderHTML() {
		$dlg = new Element('div',array('class'=>'dialog'));
		$dlg->addChild(new Element('div',array('class'=>'dlgTitle'),$this->title));
		$dlg->addChild($this->body);
		$choiceContainer = new Element('ul',array('class'=>'dlgChoices'));
		$dlg->addChild($choiceContainer);
		if (count($this->choices) > 0) {
			foreach ($this->choices as $choice) {
				$choiceContainer->addChild(new Element('li',array(),$choice));
			}
		}
		return $dlg;
		/*
		return "<div class=\"dialog\">"
		. "<div class=\"dlgTitle\">$title</div>"
		. "<div class=\"dlgBody\">$body</div>"
		. "<ul class=\"dlgChoices\">" . $choiceshtml . "</ul></div>";
		*/
	}
}
