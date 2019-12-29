<?php
namespace VGWS\HTML\Elements;
class Form extends Element
{
    public function __construct($action, $method = 'get', $name = '', $other = array())
    {
        parent::__construct('form', array('method' => $method, 'name' => $name, ));
        $this->attributes['name'] = $name;
        $this->attributes['method'] = $method;
        $this->setAttributes($other);
    }

}
