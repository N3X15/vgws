<?php
class VGWSExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('static', array($this, 'twig_static_call'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('fmtURL', 'fmtURL'),
            new \Twig_SimpleFunction('empty', 'empty'),
            new \Twig_SimpleFunction('round', 'round'),
        );
    }


    function twig_static_call($class, $function, $args = array())
    {
        if (class_exists($class) && method_exists($class, $function))
            return call_user_func_array(array($class, $function), $args);
        return null;
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'VGWS';
    }
}
