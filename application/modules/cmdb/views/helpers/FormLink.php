<?php
require_once 'Zend/View/Helper/FormElement.php';

class Zend_View_Helper_FormLink extends Zend_View_Helper_FormElement
{
    public function formLink($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable

        $endTag = '</div>';

        $xhtml = '<div'
            . ' name="' . $this->view->escape($name) . '"'
            . ' id="' . $this->view->escape($id) . '"'
            . '>' . '<a href="' . $this->view->escape($attribs['link']) . '" ';

        if ($attribs['onClick']) {
            $xhtml = $xhtml . ' onClick=' . $attribs['onClick'];
        }

        if ($attribs['title']) {
            $xhtml = $xhtml . ' title="' . $attribs['title'] . '"';
        }

        $xhtml = $xhtml . '>' . $attribs['linkname'] . '</a>'
            . $endTag;

        return $xhtml;
    }
}