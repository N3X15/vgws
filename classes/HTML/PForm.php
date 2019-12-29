<?php
/**
 * Procedural Form
 *
 * Written before QuickHTML.  Now just a convenience wrapper.
 *
 * @package QuickHTML
 * @author Rob Nelson <nexisentertainment@gmail.com>
 */
namespace VGWS\HTML;
class PForm
{
    public static $buffer = '';
    public static $hidden = array();
    public static function Form($action, $method = 'get', $name = '', $other = array())
    {
        return new Form($action, $method, $name, $other);
    }

    public static function Textbox($name, $default = '', $other = array())
    {
        return self::Input('textbox', $name, $default, $other);
    }

    public static function Spinner($name, $default = 0, $other = array())
    {
        return self::Input('number', $name, $default, $other);
    }

    public static function Password($name, $default = '', $other = array())
    {
        return self::Input('password', $name, $default, $other);
    }

    public static function Email($name, $default = '', $other = array())
    {
        return self::Input('email', $name, $default, $other);
    }

    public static function Button($type, $name, $label, $title = null, $other = array())
    {
        if ($value != null)
            $other['title'] = $value;
        $other['name'] = $name;
        $other['type'] = $type;
        $button = new Element('button', $other, $label);
    }

    public static function Reset($name, $label = 'Reset', $title = null, $other = array())
    {
        return self::Button('reset', $name, $label, $title, $other);
    }

    public static function Checkbox($name, $value = '1', $other = array())
    {
        return self::Input('checkbox', $name, $value, $other);
    }

    public static function Submit($name, $label = 'Submit', $title = null, $other = array())
    {
        return self::Button('submit', $name, $label, $title, $other);
    }

    public static function Input($type, $name, $default = '', $other = array())
    {
        return new Input($type, $name, $default, $other);
    }

    public static function Hidden($name, $value)
    {
        return self::Input('hidden', $name, $value);
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
    public static function Select($name, array $options, $default = null, $other = array())
    {
        $other['name'] = $name;
        $select = new Element('select', $other);
        foreach ($options as $k => $v) {
            $opt_attr = array('value' => $k);
            if ($k == $default)
                $opt_attr['selected'] = 'selected';
            $select->addChild(new Element('option', $opt_attr, $v));
        }
        return $select;
    }

}
