<?php
/**
 * HTML Element Builders
 *
 * Another common system I wrote.  Used for generating
 * HTML, and additionally handles form validation and
 * feedback.
 *
 * I should probably make this its own project considering
 * how much shit uses it.
 *
 * @package QuickHTML
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */
namespace VGWS\HTML\Elements;
class Element
{
    /**
     * Textareas will bug the fuck out if they're shortened to <textarea /> (on
     * Firefox, at least).
     */
    public static $DontShortenNotation = array('textarea');

    /**
     * Name of element (a = <a>)
     */
    public $name = '';

    /**
     * Attributes and their values
     */
    public $attributes = array();

    /**
     * Child elements/strings.
     */
    public $children = array();

    /**
     * Used in input elements.
     */
    public $messages = array();

    public function __construct($name, $attr = array(), $inner = array())
    {
        $this->name = $name;
        $this->attributes = $attr;
        if (!is_array($inner))
            $this->children = array($inner);
        else
            $this->children = $inner;
    }

    private function fmtAttributes()
    {
        if (!is_array($this->attributes) || count($this->attributes) == 0)
            return '';
        $o = array();
        //var_dump($this->attributes);
        foreach ($this->attributes as $k => $v) {
            if (!is_string($k) && !is_int($k)) {
                Page::Message('error', "{$this->name} - \$k=" . var_export($k, true));
                continue;
            }
            if (!is_string($v) && !is_int($v)) {
                Page::Message('error', "{$this->name}[{$k}] - \$v=" . var_export($v, true));
                continue;
            }
            $o[] = "{$k}=\"" . htmlentities($v, ENT_QUOTES, 'ISO-8859-1', false) . "\"";
            //$o[]="{$k}=\"".htmlentities($v)."\"";
        }
        //var_dump($o);
        return ' ' . implode(' ', $o);
    }

    public function __toString()
    {
        $attr = $this->fmtAttributes();
        $buf = "{$this->name}{$attr}";
        if (count($this->children) == 0 && !in_array($this->name, Element::$DontShortenNotation))
            return '<' . $buf . ' />';
        $buf = "<{$buf}>";
        for ($i = 0; $i < count($this->children); $i++) {
            if (is_string($this->children[$i]))
                $buf .= $this->children[$i] . '';
            else
                $buf .= $this->children[$i];
        }
        $buf .= "</{$this->name}>";
        return $buf;
    }

    public function prettyPrint($level = 0)
    {
        $attr = $this->fmtAttributes();
        $buf = "{$this->name}{$attr}";
        if (count($this->children) == 0 && !in_array($this->name, Element::$DontShortenNotation))
            return indent($level, '<' . $buf . ' />');
        $buf = indent($level, "<{$buf}>");
        for ($i = 0; $i < count($this->children); $i++) {
            if (is_string($this->children[$i]))
                $buf .= indent($level + 1, $this->children[$i]);
            else
                $buf .= $this->children[$i]->prettyPrint($level + 1);
        }
        $buf .= indent($level, "</{$this->name}>");
        return $buf;
    }

    public function addChild($child)
    {
        $this->children[] = $child;
        return $this;
    }

    public function addChildren($child)
    {
        foreach (func_get_args() as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $_a)
                    $this->children[] = $_a;
            } else {
                $this->children[] = $arg;
            }
        }
        return $this;
    }

    public function addClass($class)
    {
        if (!array_key_exists('class', $this->attributes))
            $this->setAttribute('class', $class);
        else
            $this->attributes['class'] = $this->attributes['class'] . ' ' . $class;
        return $this;
    }

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function setAttributes($a = array())
    {
        foreach ($a as $k => $v) {
            $this->attributes[$k] = $v;
        }
        return $this;
    }

    public function addLabel($label, $for)
    {
        $this->addChild(new Element('label', array('for' => $for), array($label)));
        return $this;
    }

    public function addPlus($formname, $for, $addval, $label, array $other = array())
    {
        //<a href="#" onclick="document.banform.seconds.value='3600';return
        // false;">1hr</a>
        $a = new Element('a');
        $a->setAttributes($other);
        $a->setAttribute('href', '#');
        $a->setAttribute('onclick', "document.{$formname}.{$for}.value=parseInt(document.{$formname}.{$for}.value) + {$addval};return false;");
        $a->addChild($label);
        $this->addChild('&nbsp;');
        $this->addChild($a);
        return $this;
    }

    public function addPreset($formname, $for, $label, $value, array $other = array())
    {
        //<a href="#" onclick="document.banform.seconds.value='3600';return
        // false;">1hr</a>
        $a = new Element('a');
        $a->setAttributes($other);
        $a->setAttribute('href', '#');
        $value = json_encode($value);
        $a->setAttribute('onclick', "document.{$formname}.{$for}.value={$value};return false;");
        $a->addChild($label);
        $this->addChild('&nbsp;');
        $this->addChild($a);
        return $this;
    }

    public function addBreak()
    {
        $this->addChild(new Element('br'));
        return $this;
    }

    public function addTextbox($name, $default = '', $other = array())
    {
        return $this->addInput('textbox', $name, $default, $other);
    }

    public function addPassword($name, $default = '', $other = array())
    {
        return $this->addInput('password', $name, $default, $other);
    }

    public function addEmail($name, $default = '', $other = array())
    {
        return $this->addInput('email', $name, $default, $other);
    }

    public function addButton($type, $name, $label, $value = null, $other = array())
    {
        if ($value != null)
            $other['value'] = $value;
        $other['type'] = $type;
        $other['name'] = $name;

        $this->addChild(new Element('button', $other, $label));
        return $this;
    }

    public function addReset($name, $label = 'Reset', $value = null, $other = array())
    {
        return $this->addButton('reset', $name, $label, $value, $other);
    }

    public function addCheckbox($name, $value = '1', $other = array())
    {
        return $this->addInput('checkbox', $name, $value, $other);
    }

    public function addSubmit($name, $label = 'Submit', $value = null, $other = array())
    {
        return $this->addButton('submit', $name, $label, $value, $other);
    }

    public function addMessage($message)
    {
        $span = new Element('li', array(), $message->message);
        $span->addClass('msg-' . $message->severity);
        $this->addChild($span);
    }

    public function addInput($type, $name, $default = '', array $other = array())
    {
        $this->addChild(new Input($type, $name, $default, $other));
        return $this;
    }

    public function addTextarea($name, $default = '', $other = array())
    {
        $other['name'] = $name;
        $this->addChild(new Element('textarea', $other, $default));
        return $this;
    }

    public function addHidden($name, $value)
    {
        //var_dump($value);
        return $this->addInput('hidden', $name, $value);
    }

    public function addBookmarklet($label,$javascript,array $other=array()) {
        //<a href="#" onclick="document.banform.seconds.value='3600';return false;">1hr</a>
        $a = new Element('a');
        $a->setAttributes($other);
        $a->setAttribute('href', '#');
        $a->setAttribute('onclick', "{$javascript}return false;");
        $a->addChild($label);
        $this->addChild('&nbsp;');
        $this->addChild($a);
        return $this;
    }

    /**
     * Emit a selection input.
     *
     * @param name
     * @param label
     * @param options
     * @param default
     * @param other
     */
    public function addSelect($name, array $options, $default = '', $other = array())
    {
        $other['name'] = $name;
        $select = new Element('select', $other);
        foreach ($options as $k => $v) {
            $opt = new Element('option');
            $opt->setAttribute('value', $k)->addChild($v);
            if ($k == $default)
                $opt->setAttribute('selected', 'selected');
            $select->addChild($opt);
        }
        $this->addChild($select);
        if (Page::HaveMessages($name)) {
            foreach (Page::GetMessages($name) as $message) {
                $this->addMessage($message);
            }
        }
        return $this;
    }

    /**
     * Form validation;  Pass on to children by default
     */
    public function Validate($_input)
    {
        if (empty($this->children))
            return true;
        $success = true;
        for ($i = 0; $i < count($this->children); $i++) {
            if (is_a($this->children[$i], 'Element'))
                if (!$this->children[$i]->Validate($_input))
                    $success = false;
        }
        return $success;
    }

    protected function takeNSet(array &$attrArray, $attrName, $varName)
    {
        if (array_key_exists($attrName, $attrArray)) {
            $this->$varName = $attrArray[$attrName];
            unset($attrArray[$attrName]);
        }
    }

}
