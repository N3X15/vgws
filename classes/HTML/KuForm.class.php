<?php

/**
 * A class that automatically generates a Kusaba form layout. (divs, labels, etc.)
 */
class KuForm
{
    // Name => array(fields,)
    public $sections = array();

    /**
     * @type KuFormSection
     */
    private $defaultSection = null;

    /**
     * The actual <form> tag.
     * @type Form
     */
    public $form = null;

    public function __construct($action, $method = 'get', $name = '', $other = array(), $sectionLegend='')
    {
        $this->form = new Form($action, $method, $name, $other);
        $this->defaultSection = $this->CreateSection($sectionLegend);
    }

    /**
     * Create a fieldset.
     * @param $legend string The legend of the fieldset.
     * @param $attributes array Any attributes to apply to the fieldset.
     * @return KuFormSection
     */
    public function createSection($legend, $attributes = array())
    {
        $section = new KuFormSection($this, $legend, $attributes);
        $this->sections[$legend] = $section;
        $this->form->addChild($section);
        return $section;
    }
    
    public function addButtonBox() {
        
        $submitbox = new Element('div',array('class'=>'formsubmit'));
        $this->form->addChild($submitbox);
        $submitbox->addSubmit('','Submit');
        $submitbox->addReset('','Reset');
    }

    /**
     * Bind a Page to the form.
     * @param $page string Page to bind to.
     */
    public function bindPage(Page $page)
    {
        $this->page = $page;
    }


    /**
     * Create a hidden field.
     */
    public function addHidden($name, $value = '', array $other = array())
    {
        $control = new Input('hidden', $name, $value, $other);
        $this->form->addChild($control);
        return $control;
    }

    /**
     * Add a textbox input to the default section.
     * @param $name string Name attribute of the element.
     * @param $label string Label tag text.
     * @param $desc string Description of the field, or empty string.
     * @param $value string Default value of the field, or empty string.
     * @param $other array Attributes to set on the textbox.
     * @return Input
     */
    public function addTextbox($name, $label, $desc = '', $value = '', array $other = array())
    {
        $control = PForm::Textbox($name, $value, $other);
        $this->defaultSection->addFormBlock($name, $label, $desc, $control);
        return $control;
    }


    /**
     * Add a textarea to the default section.
     * @param $name string Name attribute of the element.
     * @param $label string Label tag text.
     * @param $desc string Description of the field, or empty string.
     * @param $value string Default value of the field, or empty string.
     * @param $other array Attributes to set on the textarea.
     * @return TextArea
     */
    public function addTextarea($name, $label, $desc = '', $value = '', array $other = array())
    {
        $control = new TextArea($name, $value, $other);
        $this->defaultSection->addFormBlock($name, $label, $desc, $control);
        return $control;
    }


    /**
     * Add a number spinner input to the default section.
     * @param $name string Name attribute of the element.
     * @param $label string Label tag text.
     * @param $desc string Description of the field, or empty string.
     * @param $value string Default value of the field, or empty string.
     * @param $other array Attributes to set on the spinner.
     * @return Input
     */
    public function addSpinner($name, $label, $desc = '', $value = '', array $other = array())
    {
        $control = new Input('number', $name, $value, $other);
        $this->defaultSection->addFormBlock($name, $label, $desc, $control);
        return $control;
    }

    /**
     * Add a file upload input to the default section.
     * @param $name string Name attribute of the element.
     * @param $label string Label tag text.
     * @param $desc string Description of the field, or empty string.
     * @param $validFileTypes array Array of valid MIME types. See FileUpload.
     * @param $other array Attributes to set on the spinner.
     * @return Input
     */
    public function addFileUpload($name, $label, $desc = '', $validFileTypes=array(), array $other = array())
    {
        $control = new FileUpload($name, $validFileTypes, $other);
        $this->defaultSection->addFormBlock($name, $label, $desc, $control);
        return $control;
    }
    
    public function __toString() {
        return $this->form->__toString();
    }
    
    public function prettyPrint() {
        return $this->form->prettyPrint();
    }
}

class KuFormSection
{
    public $legend = null;
    public $form = null;

    public function __construct(KuForm $form, $legend, array $attr)
    {
        $this->form = $form;
        if (empty($legend)) {
            $this->element = new Element('div', $attr, $this->legend);
            $this->element->addClass('formcontainer');
        } else {
            $this->legend = new Element('legend', array(), $legend);
            $this->element = new Element('fieldset', $attr, $this->legend);
        }
    }

    /**
     * Get the <form> tag.
     * @return Form
     */
    private function getForm()
    {
        return $this->form->form;
    }

    public function addFormBlock($name, $label, $desc, $control)
    {
        $div = new Element('section', array('class' => 'formblock'));
        $this->element->addChild($div);
        $div->addLabel($label, $name);
        $div->addChild($control);
        $div->addChild(new Element('span', array('class' => 'formdesc'), $desc));
    }

    public function addTextbox($name, $label, $desc = '', $value = '', array $other = array())
    {
        $control = PForm::Textbox($name, $value, $other);
        $this->addFormBlock($name, $label, $desc, $control);
        return $control;
    }

    public function addTextarea($name, $label, $desc = '', $value = '', array $other = array())
    {
        $control = new TextArea($name, $value, $other);
        $this->addFormBlock($name, $label, $desc, $control);
        return $control;
    }

    public function addSpinner($name, $label, $desc = '', $value = '', array $other = array())
    {
        $control = new Input('number', $name, $value, $other);
        $this->addFormBlock($name, $label, $desc, $control);
        return $control;
    }

    public function addFileUpload($name, $label, $desc = '', $validFileTypes=array(), array $other = array())
    {
        $control = new FileUpload($name, $validFileTypes, $other);
        $this->addFormBlock($name, $label, $desc, $control);
        return $control;
    }
    
    public function __toString() {
        return $this->element->__toString();
    }
    
    public function prettyPrint() {
        return $this->element->prettyPrint();
    }

}
