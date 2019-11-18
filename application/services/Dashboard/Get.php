<?php

/**
 *
 *
 *
 */
class Service_Dashboard_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3401, $themeId);
    }

    public function getTodoList($userId)
    {
        $todo = array();

        $calendarDaoImpl = new Dao_Calendar();
        $todoResult      = $calendarDaoImpl->getTodoList($userId);

        foreach ($todoResult as $item) {
            $attributeType = Util_AttributeType_Factory::get($item[Db_Attribute::ATTRIBUTE_TYPE_ID]);

            $t = new Util_Calendar_Todo($attributeType->getValueByCiAttribute($item), $item[Db_TodoItems::ID], $item[Db_TodoItems::PRIORITY]);
            $t->setStatus($item[Db_TodoItems::STATUS]);
            $t->setCiId($item[Db_CiAttribute::CI_ID]);
            $t->setCreateDate($item[Db_TodoItems::CREATED]);
            array_push($todo, $t);
        }
        return $todo;
    }


    public function getEventList($userId, $projectId)
    {
        $events = array();

        $today = date('d');
        $month = date('m');
        $year  = date('Y');

        $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
        $timeFrom    = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, $today, $year));
        $timeTo      = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, $daysInMonth, 2035));


        $calendarDaoImpl = new Dao_Calendar();
        $eventResult     = $calendarDaoImpl->getEventsByTime($timeFrom, $timeTo, $userId, $projectId, 5);


        foreach ($eventResult as $event) {
            array_push($events, array('time' => $event[Db_CiAttribute::VALUE_DATE], 'text' => $event[Db_Attribute::DESCRIPTION], 'ciid' => $event[Db_CiAttribute::CI_ID]));
        }
        return $events;
    }


    public function getTimesForDay($userId, $projectId, $year, $month, $day)
    {
        $events = array();

        $timeFrom = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, $day, $year));
        $timeTo   = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $day, $year));

        $calendarDaoImpl = new Dao_Calendar();
        $eventResult     = $calendarDaoImpl->getEventsByTime($timeFrom, $timeTo, $userId, $projectId);

        $eventList = array();
        foreach ($eventResult as $ev) {
            $date = new Zend_Date($ev[Db_CiAttribute::VALUE_DATE], Zend_Date::DATETIME);
            $hour = $date->get(Zend_Date::HOUR_SHORT);
            $hour--;
            $date->setHour($hour);
            if ($hour < 6)
                $hour = 6;

            if ($hour > 19)
                $hour = 19;

            if (!$eventList[$hour])
                $eventList[$hour] = array();

            // TODO: get line by list config


            $attributeDao  = new Dao_Attribute();
            $attributeList = $attributeDao->getAttributesByTypeId($ev[Db_Ci::CI_TYPE_ID], $this->getThemeId(), $userId);

            $ciDao  = new Dao_Ci();
            $result = $ciDao->getCiConfigurationStatementByCiTypeId($attributeList, $ev[Db_CiAttribute::CI_ID], null, null, $userId);

            array_push($eventList[$hour], new Util_Calendar_Event($ev[Db_Attribute::DESCRIPTION], $ev[Db_CiAttribute::CI_ID], $date, $result[0]));
        }

        array_push($events, new Util_Calendar_Time('6:00 AM', $eventList[6]));
        array_push($events, new Util_Calendar_Time('7:00 AM', $eventList[7]));
        array_push($events, new Util_Calendar_Time('8:00 AM', $eventList[8]));
        array_push($events, new Util_Calendar_Time('9:00 AM', $eventList[9]));
        array_push($events, new Util_Calendar_Time('10:00 AM', $eventList[10]));
        array_push($events, new Util_Calendar_Time('11:00 AM', $eventList[11]));
        array_push($events, new Util_Calendar_Time('12:00 AM', $eventList[12]));

        array_push($events, new Util_Calendar_Time('1:00 PM', $eventList[13]));
        array_push($events, new Util_Calendar_Time('2:00 PM', $eventList[14]));
        array_push($events, new Util_Calendar_Time('3:00 PM', $eventList[15]));
        array_push($events, new Util_Calendar_Time('4:00 PM', $eventList[16]));
        array_push($events, new Util_Calendar_Time('5:00 PM', $eventList[17]));
        array_push($events, new Util_Calendar_Time('6:00 PM', $eventList[18]));
        array_push($events, new Util_Calendar_Time('7:00 PM', $eventList[19]));

        return $events;
    }
}