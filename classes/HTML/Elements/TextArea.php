<?php
namespace VGWS\HTML\Elements;
class TextArea extends Input
{

    public function __construct($name, $value = '', array $other = array())
    {
        parent::__construct('textarea', $name, $value, $other);
        if ($value != '')
            $this->addChild($value);
        $this->name = 'textarea';
        unset($this->attributes['type']);
        if (array_key_exists('value', $this->attributes))
            unset($this->attributes['value']);
    }

    public function setValue($val)
    {
        $this->children = array($val);
    }

    public function __toString()
    {
        $name = $this->attributes['name'];
        $buf = '';
        if ($this->required) {
            $reqTag = new Element('span', array('class' => 'textarea-required'), '&#9733;');
            $buf .= $reqTag;
        }

        if (Page::HaveMessages($name)) {
            $msgs = new Element('ul', array('class' => 'textarea-messages'));
            foreach (Page::GetMessages($name) as $message) {
                $msgs->addMessage($message);
            }
            $buf .= $msgs;
        }
        $textarea = new Element('textarea', $this->attributes);
        $textarea->attributes = $this->attributes;
        $textarea->children = $this->children;
        $buf .= $textarea;

        return $buf;
    }

    public function prettyPrint($level = 0)
    {

        $name = $this->attributes['name'];
        if ($this->required) {
            $reqTag = new Element('span', array('class' => 'textarea-required'), '&#9733;');
            $buf .= $reqTag->prettyPrint($level + 1);
        }

        if (Page::HaveMessages($name)) {
            $msgs = new Element('ul', array('class' => 'textarea-messages'));
            foreach (Page::GetMessages($name) as $message) {
                $msgs->addMessage($message);
            }
            $buf .= $msgs->prettyPrint($level + 1);
        }
        $textarea = new Element('textarea', $this->attributes);
        $textarea->attributes = $this->attributes;
        $textarea->children = $this->children;
        $buf .= $textarea;

        return $buf;
    }

}
