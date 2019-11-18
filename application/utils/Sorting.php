<?php

/*
 * Class provides language aware sorting.
 * Warning: this methods do a good job, but if you want to sort big arrays you should implement a new function
 */

class Util_Sorting
{
    const SORT_ASC  = 'asc';
    const SORT_DESC = 'desc';

    public function __construct()
    {
        /* Set internal character encoding to UTF-8 */
        mb_internal_encoding("UTF-8");
    }

    public static function sortArray($array, $direction = SORT_ASC)
    {
        if ((array)$array !== $array) {
            return $array;
        }
        return Util_Sorting::sortArraybyValue($array, $direction);
    }

    public static function sortArraybyKey($array, $direction = SORT_ASC)
    {
        if ((array)$array !== $array) {
            return $array;
        }
        uksort($array, 'Util_Sorting::compare');
        return $array;
    }

    public static function sortArraybyValue($array, $direction = SORT_ASC)
    {
        if ((array)$array !== $array) {
            return $array;
        }
        uasort($array, 'Util_Sorting::compare');
        return $array;
    }

    private function compare($a, $b)
    {
        if ($a == $b) {
            return 0;
        }
        return Util_Sorting::mb_strcmp($a, $b);
    }

    private function mb_strcmp($a, $b)
    {
        mb_internal_encoding("UTF-8");
        $array_match   = array('�', '�', '�', '�');
        $array_replace = array('a', 'o', 'u', 's');
        return strcmp(
            str_replace($array_match, $array_replace, iconv('UTF-8', 'Windows-1252', mb_strtolower($a))),
            str_replace($array_match, $array_replace, iconv('UTF-8', 'Windows-1252', mb_strtolower($b)))
        );
    }
}