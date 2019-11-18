<?php

class Util_Calendar_Event
{

    private $name;
    private $date;
    private $ciId;
    private $list;

    public function __construct($name, $ciId, Zend_Date $date, $list = null)
    {
        $this->name = $name;
        $this->date = $date;
        $this->ciId = $ciId;

        if (!$this->date)
            $this->date = new Zend_Date();

        if ($list) {
            // heureka
            $this->list = $list;
        }
    }


    public function __toString()
    {
        $time = $this->date->get(Zend_Date::TIMES);

        $string = '<a href="' . APPLICATION_URL . 'ci/detail/ciid/' . $this->ciId . '" title="' . $time . '">' . $this->name . ' : ' . $this->date . '</a>';
        if ($this->list) {
            unset($this->list['id']);
            $string .= ' - ';

            foreach ($this->list as $att) {
                $string .= ' ' . $att;
            }
        }

        return $string;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getName()
    {
        return $this->name;
    }
}