<?php

interface Import_File_Type
{

    public function __construct($logger, $file, $historyId, $options = array());

    public function import($callback);

    public function getTotalLines();
}