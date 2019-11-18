<?php

class Util_Calendar
{

    public static function get($year = null, $month = null, $userId = null, $projectId = null, $withEvents = false)
    {
        if (!$year)
            $year = date('Y');

        if (!$month)
            $month = date('m');

        $result       = array();
        $result[0][0] = 'S'; // Sunday
        $result[1][0] = 'M';
        $result[2][0] = 'T';
        $result[3][0] = 'W';
        $result[4][0] = 'T';
        $result[5][0] = 'F';
        $result[6][0] = 'S'; // Saturday

        $running_day = date('w', mktime(0, 0, 0, $month, 1, $year));
        $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));

        $prevMonth = $month - 1;
        $prevYear  = $year;
        if ($prevMonth <= 0) {
            $prevMonth = 12;
            $prevYear  = $year - 1;
        }

        $daysInPrevMonth = date('t', mktime(0, 0, 0, $prevMonth, 1, $prevYear));

        $currentLine      = 0;
        $currentDayInWeek = 1;


        // fill with prev. month
        for ($x = 0; $x < $running_day; $x++) {
            $date = new Util_Calendar_Date(($daysInPrevMonth - ($running_day - $x)) + 1, $prevMonth, $prevYear);
            $date->setGrey(true);
            $result[$currentLine][$currentDayInWeek] = $date;
            $currentLine++;
        }

        $today     = date('d');
        $thisMonth = date('m');
        $thisYear  = date('Y');

        // fill with current month
        for ($currentDay = 1; $currentDay <= $daysInMonth; $currentDay++) {
            if ($currentLine == 7) {
                $currentDayInWeek++;;
                $currentLine = 0;
            }

            $date = new Util_Calendar_Date($currentDay, $month, $year);

            if ($currentDay == $today && $thisMonth == $month && $year == $thisYear)
                $date->setBorder(true);

            $result[$currentLine][$currentDayInWeek] = $date;
            $currentLine++;
        }

        $nextMonth = $month + 1;
        $nextYear  = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear  = $year + 1;
        }

        $dayInNextMonth = 1;
        // add next month
        for ($currentDay = $currentDayInWeek; $currentDay <= 8; $currentDay++) {
            $date = new Util_Calendar_Date($dayInNextMonth, $nextMonth, $nextYear);
            $date->setGrey(true);
            $result[$currentLine][$currentDayInWeek] = $date;
            $currentLine++;
            $dayInNextMonth++;
        }

        if ($withEvents) {
            $result = self::addEvents($result, $year, $month, $userId, $projectId);
        }
        return $result;
    }


    private static function addEvents($result, $year, $month, $userId, $projectId)
    {
        // select all value_date where between start and end of current month and attribute is event
        $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));

        $timeFrom = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, 1, $year));
        $timeTo   = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, $daysInMonth, $year));

        $calendarDaoImpl = new Dao_Calendar();
        $events          = $calendarDaoImpl->getEventsByTime($timeFrom, $timeTo, $userId, $projectId);

        foreach ($events as $eventResult) {
            $date  = new Zend_Date($eventResult[Db_CiAttribute::VALUE_DATE], Zend_Date::DATETIME);
            $event = new Util_Calendar_Event($eventResult[Db_Attribute::DESCRIPTION], $eventResult[Db_CiAttribute::CI_ID], $date);

            $eventDay   = $date->get(Zend_Date::DAY);
            $eventMonth = $date->get(Zend_Date::MONTH);
            foreach ($result as $column) {
                foreach ($column as $day) {
                    if ($day instanceof Util_Calendar_Date) {
                        if ($day->getDay() == $eventDay && $day->getMonth() == $eventMonth) {
                            $day->addEvent($event);
                            $day->setBold(true);
                        }
                    }
                }
            }
        }

        return $result;
    }
}