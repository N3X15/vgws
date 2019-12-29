<?php namespace VGWS\HTML\Elements;
class Select extends Input
{
    protected $options = array();
    protected $selected = '';

    public function __construct($name, $options, $default = '', $other = array())
    {
        parent::__construct('', $name, '', $other);
        $this->name = 'select';
        $this->options = $options;
        unset($this->attributes['type']);
        foreach ($this->options as $k => $v) {
            $opt = new Element('option');
            $opt->setAttribute('value', $k)->addChild($v);
            if ($k == $default)
                $opt->setAttribute('selected', 'selected');
            $this->addChild($opt);
        }
        if (Page::HaveMessages($name)) {
            foreach (Page::GetMessages($name) as $message) {
                $this->addMessage($message);
            }
        }
    }

    public function setValue($default)
    {
        $this->children = array();
        foreach ($this->options as $k => $v) {
            $opt = new Element('option');
            $opt->setAttribute('value', $k)->addChild($v);
            if ($k == $default)
                $opt->setAttribute('selected', 'selected');
            $this->addChild($opt);
        }
    }

}
