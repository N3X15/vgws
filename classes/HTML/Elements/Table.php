<?php
namespace VGWS\HTML\Elements;
class Table extends Element
{
    public function __construct($class = '', $other = array())
    {
        parent::__construct('table');
        if ($class != '')
            $this->attributes['class'] = $class;
        $this->setAttributes($other);
    }

    public function createRow($class = '', array $other = array())
    {
        $tr = new TableRow($class, $other);
        $this->addChild($tr);
        return $tr;
    }

    /**
     * @param ... Cells
     * @return TR Element
     */
    public function addRow()
    {
        $row = $this->createRow();
        foreach (func_get_args() as $arg) {
            if (subclasses($arg, 'Element') && ($arg->name == 'td' || $arg->name == 'th'))
                $row->addChild($arg);
            else
                $row->addChild(new Element('td', array(), $arg));
        }
        return $row;
    }

    public function addHeadingsRow(array $labels)
    {
        foreach (func_get_args() as $arg) {
            if (subclasses($arg, 'Element') && ($arg->name == 'td' || $arg->name == 'th'))
                $row->addChild($arg);
            else
                $row->addChild(new Element('th', array(), $arg));
        }
        return $row;
    }

}
