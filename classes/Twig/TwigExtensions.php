<?php
namespace VGWS\Twig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
class VGWSExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return array(
            new TwigFunction('static', array($this, 'twig_static_call'), array('is_safe' => array('html'))),
            new TwigFunction('fmtURL', 'fmtURL'),
            new TwigFunction('empty', 'empty'),
            new TwigFunction('round', 'round'),
            new TwigFunction('asset', array($this, 'twig_asset_call')),
        );
    }


    function twig_static_call($class, $function, $args = array())
    {
        if (class_exists($class) && method_exists($class, $function))
            return call_user_func_array(array($class, $function), $args);
        return null;
    }

    function twig_asset_call($ID)
    {
        return Assets::Get($ID);
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'VGWS';
    }
}
