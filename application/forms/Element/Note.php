<?php

/** Zend_Form_Element_Xhtml */
require_once 'Zend/Form/Element/Xhtml.php';

/**
 * Note form element
 *
 */
class Form_Element_Note extends Zend_Form_Element_Xhtml
{
    /**
     * Default form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'formNote';
}
