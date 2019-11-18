<?php

class Form_Reporting_Cron extends Form_AbstractAppForm
{
    public function __construct($translator, $values = null, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('CronForm');
        $this->setAttrib('enctype', 'multipart/form-data');

        if (!$values)
            $values = '* * * * *';
        list($min, $hour, $day, $month, $week) = explode(' ', $values);


        // HANDLE MINUTES
        $radioMinutes = new Zend_Form_Element_Radio('minutes_radio');
        $radioMinutes->setMultiOptions(array(0 => 'everyMinute', 1 => 'atMinute', 2 => 'everyXMinutes'));
        $radioMinutes->setAttrib('onChange', 'javascript:triggerDisabled("minutes")');

        $minutes = new Zend_Form_Element_Select('minutes');
        $minutes->setMultiOptions(range(0, 59));

        if ($min == '*') {
            $radioMinutes->setValue(0);
            $minutes->setAttrib('disabled', 'disabled');
        } elseif (strpos($min, '/') !== false) {
            $radioMinutes->setValue(2);
            $minutes->setValue(str_replace('*/', '', $min));
        } else {
            $radioMinutes->setValue(1);
            $minutes->setValue($min);
        }


        // HANDLE HOURS
        $radioHours = new Zend_Form_Element_Radio('hours_radio');
        $radioHours->setMultiOptions(array(0 => 'everyHour', 1 => 'atHour', 2 => 'everyXHours'));
        $radioHours->setAttrib('onChange', 'javascript:triggerDisabled("hours")');

        $hours = new Zend_Form_Element_Select('hours');
        $hours->setMultiOptions(range(0, 23));

        if ($hour == '*') {
            $radioHours->setValue(0);
            $hours->setAttrib('disabled', 'disabled');
        } elseif (strpos($hour, '/') !== false) {
            $radioHours->setValue(2);
            $hours->setValue(str_replace('*/', '', $hour));
        } else {
            $radioHours->setValue(1);
            $hours->setValue($hour);
        }


        // HANDLE DAYS
        $radioDays = new Zend_Form_Element_Radio('days_radio');
        $radioDays->setMultiOptions(array(0 => 'everyDay', 1 => 'atDay', 2 => 'everyXDays'));
        $radioDays->setAttrib('onChange', 'javascript:triggerDisabled("days")');

        $dayList = array();
        for ($i = 1; $i < 32; $i++)
            $dayList[$i] = $i;

        $days = new Zend_Form_Element_Select('days');
        $days->setMultiOptions($dayList);

        if ($day == '*') {
            $radioDays->setValue(0);
            $days->setAttrib('disabled', 'disabled');
        } elseif (strpos($day, '/') !== false) {
            $radioDays->setValue(2);
            $days->setValue(str_replace('*/', '', $day));
        } else {
            $radioDays->setValue(1);
            $days->setValue($day);
        }


        // HANDLE MONTHS
        $radioMonths = new Zend_Form_Element_Radio('months_radio');
        $radioMonths->setMultiOptions(array(0 => 'everyMonth', 1 => 'atMonth', 2 => 'everyXMonths'));
        $radioMonths->setAttrib('onChange', 'javascript:triggerDisabled("months")');

        $monthList = array();
        for ($i = 1; $i < 13; $i++)
            $monthList[$i] = $i;

        $months = new Zend_Form_Element_Select('months');
        $months->setMultiOptions($monthList);


        if ($month == '*') {
            $radioMonths->setValue(0);
            $months->setAttrib('disabled', 'disabled');
        } elseif (strpos($month, '/') !== false) {
            $radioMonths->setValue(2);
            $months->setValue(str_replace('*/', '', $month));
        } else {
            $radioMonths->setValue(1);
            $months->setValue($month);
        }

        // HANDLE WEEKDAYS
        $radioWeekdays = new Zend_Form_Element_Radio('weekdays_radio');
        $radioWeekdays->setMultiOptions(array(0 => 'everyWeekday', 1 => 'atWeekday'));
        $radioWeekdays->setAttrib('onChange', 'javascript:triggerDisabled("weekdays")');

        $weekdayList = array();
        for ($i = 1; $i < 8; $i++)
            $weekdayList[$i] = $i;

        $weekdays = new Zend_Form_Element_Select('weekdays');
        $weekdays->setMultiOptions($weekdayList);

        if ($week == '*') {
            $radioWeekdays->setValue(0);
            $weekdays->setAttrib('disabled', 'disabled');
        } else {
            $radioWeekdays->setValue(1);
            $weekdays->setValue($week);
        }


        // finalize form
        $this->addElements(array($radioMinutes, $minutes,
            $radioHours, $hours,
            $radioDays, $days,
            $radioMonths, $months,
            $radioWeekdays, $weekdays));

    }
}