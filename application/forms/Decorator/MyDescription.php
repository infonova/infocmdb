<?php

class Form_Decorator_MyDescription extends Zend_Form_Decorator_Abstract
{
    public function render($content)
    {
        $element     = $this->getElement();
        $description = trim($element->getDescription());
        if ($description) {
            $output = '<td><img src="' . APPLICATION_URL . '/images/icon/info_add.gif" alt="info" align="absmiddle"> <em>' . $description . '</em></td>';
        } else {
            $output = '';
        }
        $placement = $this->getPlacement();
        $separator = $this->getSeparator();


        $placement = 'APPEND';
        switch ($placement) {
            case 'PREPEND':
                return $output . $separator . $content;
            case 'APPEND':
            default:
                return $content . $separator . $output;
        }
    }
}