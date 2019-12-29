<?php 
namespace VGWS\HTML\Elements;
class TableRow extends Element
{
    public function __construct($class = '', $other = array())
    {
        parent::__construct('tr');
        if ($class != '')
            $this->attributes['class'] = $class;
        $this->setAttributes($other);
    }

    public function createCell($class = '', array $other = array())
    {
        if ($class != '')
            $other['class'] = $class;
        $td = new Element('td', $other);
        $this->addChild($td);
        return $td;
    }

    public function createCells($count)
    {
        $cells = array();
        for ($i = 0; $i < $count; $i++)
            $cells[] = $this->createCell();
        return $cells;
    }

    public function createHeaderCell($class = '', array $other = array())
    {
        if ($class != '')
            $other['class'] = $class;
        $td = new Element('th', $other);
        $this->addChild($td);
        return $td;
    }

    public function addHeader($children, array $other = array())
    {
        $th = new Element('th', $other);
        $this->addChild($th);
        $th->addChild($children);
        return $th;
    }

    public function addCell($children, array $other = array())
    {
        $th = new Element('td', $other);
        $this->addChild($th);
        $th->addChild($children);
        return $th;
    }

}
