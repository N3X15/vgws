<?php
namespace VGWS\HTML\Elements;
class Input extends Element
{
    protected $required = false;
    protected $hideStar = false;
    protected $maxLength = 0;

    public function __construct($type, $name, $value = '', array $other = array())
    {
        $other['name'] = $name;
        $other['type'] = $type;
        if ($value != '')
            $other['value'] = $value;

        $this->takeNSet($other, 'required', 'required');
        $this->takeNSet($other, 'hidestar', 'hideStar');
        $this->takeNSet($other, 'maxlength', 'maxLength');

        parent::__construct('input', $other);
    }

    public function setRequired()
    {
        $this->required = true;
    }

    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
    }

    public function Validate($_input)
    {
        $value = null;
        $name = $this->attributes['name'];
        if (array_key_exists($name, $_input))
            $value = trim($_input[$name]);
        if (isset($this->attributes['title']))
            $name = $this->attributes['title'];
        if ($this->required) {
            if (empty($value)) {
                Page::Message('error', 'This field is required.', $this->attributes['name']);
                return false;
            }
        }
        if ($this->maxLength > 0) {
            if (strlen($value) > $this->maxLength) {
                Page::Message('error', "This field has a maximum length of {$this->maxLength}.", $this->attributes['name']);
                return false;
            }
        }
        return true;
    }

    public function setValue($value)
    {
        $this->attributes['value'] = $value;
    }

    public function __toString()
    {
        $buf = parent::__toString();

        $name = $this->attributes['name'];

        if ($this->required && !$this->hideStar) {
            $reqTag = new Element('span', array('class' => 'input-required'), '&#9733;');
            $buf .= $reqTag;
        }

        if (Page::HaveMessages($name)) {
            $msgs = new Element('ul', array('class' => 'input-messages'));
            foreach (Page::GetMessages($name) as $message) {
                $msgs->addMessage($message);
            }
            $buf .= $msgs;
        }

        return $buf;
    }

    public function prettyPrint($level = 0)
    {
        $buf = parent::__toString();

        $name = $this->attributes['name'];

        if ($this->required) {
            $reqTag = new Element('span', array('class' => 'input-required'), '&#9733;');
            $buf .= $reqTag->prettyPrint($level + 1);
        }

        if (Page::HaveMessages($name)) {
            $msgs = new Element('ul', array('class' => 'input-messages'));
            foreach (Page::GetMessages($name) as $message) {
                $msgs->addMessage($message);
            }
            $buf .= $msgs->prettyPrint($level + 1);
        }

        return $buf;
    }

}
