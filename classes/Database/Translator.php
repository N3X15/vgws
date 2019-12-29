<?php
namespace VGWS\Database;

abstract class Translator
{
    abstract public function toDB($input);
    abstract public function fromDB($input);
    public function wrapSetSQL($input)
    {
        return $input;
    }
    public function wrapGetSQL($input)
    {
        return $input;
    }
}
