<?php

// IMPLEMENT ME!!
interface Import_File_Method
{

    public function import(&$logger, $historyId, &$data, $attributes, $options = array());
}