<?php
// THIS FILE SHOULD BE PLACED here: Zend\Form\Element
// it extends the Zend Framework
/** Zend_Form_Element_Xhtml */
require_once 'Zend/Form/Element/Xhtml.php';

/**
 * Custom Link form element
 *
 */
class Zend_Form_Element_Link extends Zend_Form_Element_Xhtml
{
	/**
	 * Default form view helper to use for rendering
	 * @var string
	 */
	public $helper = 'formLink';
}