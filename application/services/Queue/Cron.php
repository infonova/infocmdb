<?php

require_once APPLICATION_PATH . "/../library/Cron/Autoloader.php";

class Service_Queue_Cron
{


    public static function checkExecutionTime($lastExecution, $cronjob)
    {

        //ignore less than a minute
        if (time() - strtotime($lastExecution) < 60)
            return false;

        $cronjob = implode(" ", $cronjob);
        //using library from github(mtdowling/cron-expression): https://github.com/mtdowling/cron-expression
        $cron = Cron\CronExpression::factory($cronjob);

        /*
         * always look forward
         * cronjobs don't work like "last execution + check time"
         * they work like "what time is now, does this match with cronjob time?"
         * Examples: 
         * 		every 7 minutes: execute at minute 07, 14, 21, 28, ...
         * 		at 07:00: execute if current hour is 07 and current minute is 0
         * 		every 3 months: execute if month is 03, 06, 09, ...
         */
        $lastExecution = 'now';

        return $cron->isDue($lastExecution);
    }
}