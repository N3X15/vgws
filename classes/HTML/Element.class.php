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

class FileUpload extends Input
{
    public $validTypes = array();
    public $uploadData = null;

    public function __construct($name, array $validTypes, array $other = array())
    {

        $other['type'] = 'file';
        $this->validTypes = $validTypes;
        parent::__construct('file', $name, '', $other);
    }

    /**
     * MUST BE CALLED OR SHIT WILL BREAK.
     */
    public function Validate($devnull = array())
    {
        $name = $this->attributes['name'];
        if ($this->required) {
            if (!isset($_FILES[$name]) || $_FILES[$name]['size'] == 0) {
                Page::Message('error', 'This field is required.', $this->attributes['name']);
                return false;
            }
        }

        // Don't process further if unset.
        if (!isset($_FILES[$name]) || $_FILES[$name]['size'] == 0)
            return true;

        $data = $_FILES[$name];
        $data['type'] = getMime($data['tmp_name']);

        // Max file size checks
        if ($this->maxLength > 0) {
            if ($data['size'] > $this->maxLength) {
                $fmtSize = formatBytes($data['size']);
                Page::Message('error', "You cannot upload files exceeding {$fmtSize}.", $this->attributes['name']);
                $this->GC($data['tmp_name']);
                return false;
            }
        }

        // Check MIME type (keeps people from uploading PHP files)
        if ($this->validTypes != array()) {
            if (!array_key_exists($data['type'], $this->validTypes)) {
                $validFileExts = array();
                foreach ($this->validTypes as $type => $exts) {
                    foreach ($exts as $ext) {
                        if (startsWith($ext, '.'))
                            $validFileExts[] = $ext;
                        else
                            $validFileExts[] = '.' . $ext;
                    }
                }
                Page::Message('error', "You uploaded an unacceptable file type: The file you uploaded isn't a " . join_english($validFileExts, '[EMPTY ARRAY!]', ' or a ') . '.', $name);
                $this->GC($data['tmp_name']);
                return false;
            }
            // Standardize extension
            $data['name'] = replace_extension($data['name'], $this->validTypes[$data['type']][0]);
        }

        // Check image size
        if (array_key_exists('max_height', $this->attributes) && array_key_exists('max_width', $this->attributes)) {
            $size = getimagesize($data['tmp_name']);
            if (!$size) {
                Page::Message('error', 'Failed to read the size of uploaded image.');
                $this->GC($data['tmp_name']);
                return false;
            }
            $fails = 0;
            if ($size[0] > intval($this->attributes['max_width'])) {
                Page::Message('error', "Uploaded image is {$size[0]}px wide, must be under {$this->attributes['max_width']}px wide.", $this->attributes['name']);
                $fails++;
            }
            if ($size[1] > intval($this->attributes['max_height'])) {
                Page::Message('error', "Uploaded image is {$size[1]}px wide, must be under {$this->attributes['max_height']}px high.", $this->attributes['name']);
                $fails++;
            }
            if ($fails) {
                $this->GC($data['tmp_name']);
                return false;
            }
        }

        $this->uploadData = $data;

        return true;
    }

    public function Cleanup()
    {
        if ($this->uploadData == null)
            return;
        $this->GC($this->uploadData['tmp_name']);
    }

    private function AssertWasValidated()
    {
        squad_assert('FileUpload not validated prior to attempting data access.', $this->uploadData != null);
    }

    private function GC($file)
    {
        if (file_exists($file))
            unlink($file);
    }

    public function GetMD5()
    {
        if ($this->uploadData == null)
            return null;
        return md5_file($this->uploadData['tmp_name']);
    }

    public function GetExt()
    {
        if ($this->uploadData == null)
            return null;
        $mime = getMime($this->uploadData['tmp_name']);
        if (array_key_exists($mime, $this->validTypes))
            return $this->validTypes[$mime][0];
        return 'bin';
    }

    public function MoveFileTo($dest)
    {
        if ($this->uploadData == null)
            return;
        $dir = dirname($dest);
        if (!file_exists($dir))
            mkdir($dir, 0755, true);
        return move_uploaded_file($this->uploadData["tmp_name"], $dest);
    }

    public function GetTemporaryFilename()
    {
        return $this->uploadData["tmp_name"];
    }

    public function GetOriginalFilename()
    {
        if ($this->uploadData == null)
            return null;
        return $this->uploadData['name'];
    }

}

class TextArea extends Input
{

    public function __construct($name, $value = '', array $other = array())
    {
        parent::__construct('textarea', $name, $value, $other);
        if ($value != '')
            $this->addChild($value);
        $this->name = 'textarea';
        unset($this->attributes['type']);
        if (array_key_exists('value', $this->attributes))
            unset($this->attributes['value']);
    }

    public function setValue($val)
    {
        $this->children = array($val);
    }

    public function __toString()
    {
        $name = $this->attributes['name'];
        $buf = '';
        if ($this->required) {
            $reqTag = new Element('span', array('class' => 'textarea-required'), '&#9733;');
            $buf .= $reqTag;
        }

        if (Page::HaveMessages($name)) {
            $msgs = new Element('ul', array('class' => 'textarea-messages'));
            foreach (Page::GetMessages($name) as $message) {
                $msgs->addMessage($message);
            }
            $buf .= $msgs;
        }
        $textarea = new Element('textarea', $this->attributes);
        $textarea->attributes = $this->attributes;
        $textarea->children = $this->children;
        $buf .= $textarea;

        return $buf;
    }

    public function prettyPrint($level = 0)
    {

        $name = $this->attributes['name'];
        if ($this->required) {
            $reqTag = new Element('span', array('class' => 'textarea-required'), '&#9733;');
            $buf .= $reqTag->prettyPrint($level + 1);
        }

        if (Page::HaveMessages($name)) {
            $msgs = new Element('ul', array('class' => 'textarea-messages'));
            foreach (Page::GetMessages($name) as $message) {
                $msgs->addMessage($message);
            }
            $buf .= $msgs->prettyPrint($level + 1);
        }
        $textarea = new Element('textarea', $this->attributes);
        $textarea->attributes = $this->attributes;
        $textarea->children = $this->children;
        $buf .= $textarea;

        return $buf;
    }

}

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
