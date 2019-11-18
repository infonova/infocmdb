<?php

class Util_Calendar_Date
{

    private $day;
    private $month;
    private $year;

    private $isGrey = false;
    private $events = array();

    private $method = 'dashboard/calendar';
    private $bold   = false;
    private $border = false;

    public function __construct($day, $month = null, $year = null)
    {
        $this->day   = $day;
        $this->month = $month;
        $this->year  = $year;
    }


    public function __toString()
    {
        $class = "";
        if ($this->isGrey)
            $class = "disabled";

        $day = $this->day;
        if ($this->bold)
            $day = '<b>' . $day . '</b>';

        $style = "text-align: center;";
        if ($this->border)
            $style = 'border: 1px solid; color: gray; text-align: center;';

        return '<td style="' . $style . '"><a class="' . $class . '" href="' . APPLICATION_URL . $this->method . '/day/' . $this->day . '/month/' . $this->month . '/year/' . $this->year . '/">' . $day . '</a></td>';
    }


    public function setDay($day)
    {
        $this->day = $day;
    }

    public function getDay()
    {
        return $this->day;
    }

    public function setMonth($month)
    {
        $this->month = $month;
    }

    public function getMonth()
    {
        return $this->month;
    }

    public function setYear($year)
    {
        $this->year = $year;
    }

    public function getYear()
    {
        return $this->year;
    }

    public function setGrey($isGrey)
    {
        $this->isGrey = $isGrey;
    }

    public function isGrey()
    {
        return $isGrey;
    }

    public function setBold($bold)
    {
        $this->bold = $bold;
    }

    public function setBorder($border)
    {
        $this->border = $border;
    }

    public function addEvent(Util_Calendar_Event $event)
    {
        array_push($this->events, $event);
    }
}