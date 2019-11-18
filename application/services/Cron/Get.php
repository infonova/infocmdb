<?php

/**
 *
 *
 *
 */
class Service_Cron_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 601, $themeId);
    }


    public static function getExecutionTimeAsString($formData)
    {

        switch ($formData['minutes_radio']) {
            case 0:
                $min = '*';
                break;
            case 2:
                $min = '*/' . $formData['minutes'];
                break;
            default:
                $min = $formData['minutes'];
        }

        switch ($formData['hours_radio']) {
            case 0:
                $hour = '*';
                break;
            case 2:
                $hour = '*/' . $formData['hours'];
                break;
            default:
                $hour = $formData['hours'];
        }

        switch ($formData['days_radio']) {
            case 0:
                $day = '*';
                break;
            case 2:
                $day = '*/' . $formData['days'];
                break;
            default:
                $day = $formData['days'];
        }

        switch ($formData['months_radio']) {
            case 0:
                $month = '*';
                break;
            case 2:
                $month = '*/' . $formData['months'];
                break;
            default:
                $month = $formData['months'];
        }

        switch ($formData['weekdays_radio']) {
            case 0:
                $week = '*';
                break;
            default:
                $week = $formData['weekdays'];
        }

        return $min . ' ' . $hour . ' ' . $day . ' ' . $month . ' ' . $week;
    }
    /* TODO: seems to be not used --> delete later
    public static function getExecutionTimeAsArray($string) {
            
        $array = explode(' ', $string);
        if (count($array) != 5)
            throw new Exception();
            
        // handle minutes
        if ($array[0] == '*')
            $return['minutes_radio'] = 0;
        else {
            $return['minutes_radio'] = 1;
            $return['minutes'] = $array[0];
        }

        // handle hours
        if ($array[1] == '*')
            $return['hours_radio'] = 0;
        else {
            $return['hours_radio'] = 1;
            $return['hours'] = $array[1];
        }
        
        // handle days
        if ($array[2] == '*')
            $return['days_radio'] = 0;
        else {
            $return['days_radio'] = 1;
            $return['days'] = $array[2];
        }
        
        // handle months
        if ($array[3] == '*')
            $return['months_radio'] = 0;
        else {
            $return['months_radio'] = 1;
            $return['months'] = $array[3];
        }
        
        // handle weekdays
        if ($array[4] == '*')
            $return['weekdays_radio'] = 0;
        else {
            $return['weekdays_radio'] = 1;
            $return['weekdays'] = $array[4];
        }
        
        return $return;
    }
    */
}