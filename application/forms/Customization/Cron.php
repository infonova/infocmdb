<?php

/**
 * This class is used to create the reporting input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Customization_Cron extends Form_AbstractAppForm
{
    public function __construct($translator, $executionTime = null, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('CreateForm');

        if (!$executionTime)
            $executionTime = '* * * * *';
        list($min, $hour, $day, $month, $week) = explode(' ', $executionTime);


        // HANDLE MINUTES
        $radioMinutes = new Zend_Form_Element_Radio('minutes_radio');
        $radioMinutes->setMultiOptions(array(0 => 'everyMinute', 1 => 'pleaseChose'));
        $radioMinutes->setAttrib('onChange', 'javascript:triggerDisabled("minutes"), submit()');

        $minutes = new Zend_Form_Element_Select('minutes');
        $minutes->setMultiOptions(range(0, 59));

        if ($min == '*') {
            $radioMinutes->setValue(0);
            $minutes->setAttrib('disabled', 'disabled');
        } else {
            $radioMinutes->setValue(1);
            $minutes->setValue($min);
        }


        // HANDLE HOURS
        $radioHours = new Zend_Form_Element_Radio('hours_radio');
        $radioHours->setMultiOptions(array(0 => 'everyHour', 1 => 'pleaseChose'));
        $radioHours->setAttrib('onChange', 'javascript:triggerDisabled("hours"), submit()');

        $hours = new Zend_Form_Element_Select('hours');
        $hours->setMultiOptions(range(0, 23));

        if ($hour == '*') {
            $radioHours->setValue(0);
            $hours->setAttrib('disabled', 'disabled');
        } else {
            $radioHours->setValue(1);
            $hours->setValue($hour);
        }


        // HANDLE DAYS
        $radioDays = new Zend_Form_Element_Radio('days_radio');
        $radioDays->setMultiOptions(array(0 => 'everyDay', 1 => 'pleaseChose'));
        $radioDays->setAttrib('onChange', 'javascript:triggerDisabled("days"), submit()');

        $dayList = array();
        for ($i = 1; $i < 32; $i++)
            $dayList[$i] = $i;

        $days = new Zend_Form_Element_Select('days');
        $days->setMultiOptions($dayList);

        if ($day == '*') {
            $radioDays->setValue(0);
            $days->setAttrib('disabled', 'disabled');
        } else {
            $radioDays->setValue(1);
            $days->setValue($day);
        }


        // HANDLE MONTHS
        $radioMonths = new Zend_Form_Element_Radio('months_radio');
        $radioMonths->setMultiOptions(array(0 => 'everyMonth', 1 => 'pleaseChose'));
        $radioMonths->setAttrib('onChange', 'javascript:triggerDisabled("months"), submit()');

        $monthList = array();
        for ($i = 1; $i < 13; $i++)
            $monthList[$i] = $i;

        $months = new Zend_Form_Element_Select('months');
        $months->setMultiOptions($monthList);


        if ($month == '*') {
            $radioMonths->setValue(0);
            $months->setAttrib('disabled', 'disabled');
        } else {
            $radioMonths->setValue(1);
            $months->setValue($month);
        }

        // HANDLE WEEKDAYS
        $radioWeekdays = new Zend_Form_Element_Radio('weekdays_radio');
        $radioWeekdays->setMultiOptions(array(0 => 'everyWeekday', 1 => 'pleaseChose'));
        $radioWeekdays->setAttrib('onChange', 'javascript:triggerDisabled("weekdays"), submit()');

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

        $this->addElements(array($radioMinutes, $minutes,
            $radioHours, $hours,
            $radioDays, $days,
            $radioMonths, $months,
            $radioWeekdays, $weekdays,
        ));
    }

}