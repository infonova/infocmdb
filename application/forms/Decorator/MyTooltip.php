<?php

class Form_Decorator_MyTooltip extends Zend_Form_Decorator_Abstract
{
    public function render($content)
    {
        $element     = $this->getElement();
        $description = trim($element->getDescription());

        $descriptionCheck = self::sanitizeHint($description);
        if (empty($descriptionCheck)) {
            return $content;
        }

        $output    = '<img onmouseover="Tip(\'' . $description . '\')" onmouseout="UnTip()" src="' . APPLICATION_URL . '/images/icon/info.png" alt="info">';
        $separator = $this->getSeparator();

        $this->getElement()->getView()->inlineScript()->appendFile(
            APPLICATION_URL . 'js/tooltip/wz_tooltip.js',
            'text/javascript'
        );
        return $content . $separator . $output;
    }

    /**
     * returns an empty string or the given hint untouched
     *
     * @param string $hintContent is the original hint
     *
     * @return string
     */
    public static function sanitizeHint($hintContent)
    {
        $html = htmlspecialchars_decode($hintContent);

        if (self::isHtmlEmpty($html)) {
            return '';
        }

        return $hintContent;
    }

    /**
     * checks if given parameter contains visual text
     *
     * @param string $html
     *
     * @return bool
     */
    public static function isHtmlEmpty($html)
    {
        require_once('Html2Text.php');

        $h2t  = new \Html2Text\Html2Text($html);
        $text = trim(str_replace("\xc2\xa0", '', $h2t->getText()));

        if ($text === '') {
            return true;
        }

        return false;
    }
}