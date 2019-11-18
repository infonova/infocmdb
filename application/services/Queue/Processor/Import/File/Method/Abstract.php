<?php

abstract class Import_File_Method_Abstract
{

    const VALID = 1;

    public function finalize($logger)
    {
        $status           = array();
        $status['status'] = true;
        $status['errors'] = array();
        return $status;
    }

    public function getAttributeList(&$data, &$logger)
    {
        return Import_File_Util_Attribute::getAttributes($data, $logger);
    }

    protected function encode($values)
    {
        $encoding = mb_detect_encoding($values, "auto");
        if ($encoding != "UTF-8" || !$this->detectUTF8($values)) {
            $values = mb_convert_encoding($values, "UTF-8");
        }
        return $values;
    }


    private function detectUTF8($string)
    {
        return preg_match('%(?:
	        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
	        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
	        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
	        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
	        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
	        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
	        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
	        )+%xs', $string);
    }

}