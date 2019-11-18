<?php

class Util_Calendar_Time
{

    private $name;
    private $events = array();

    public function __construct($name, $event = array())
    {
        if (!$event)
            $event = array();

        $this->name   = $name;
        $this->events = $event;
    }


    public function __toString()
    {
        $event1 = array();
        $event2 = array();
        $event3 = array();
        $event4 = array();

        foreach ($this->events as $event) {
            $date   = $event->getDate();
            $minute = $date->get(Zend_Date::MINUTE);

            if ($minute <= 15) {
                array_push($event1, $event);
            } else if ($minute <= 30) {
                array_push($event2, $event);
            } else if ($minute <= 45) {
                array_push($event3, $event);
            } else {
                array_push($event5, $event);
            }
        }

        $string = "<tr>
				<td class='timeborder' width='60' valign='top' align='center' rowspan='4'>" . $this->name . "</td>
				<td height='15' width='1' bgcolor='#a1a5a9'></td>
				<td class='dayborder' colspan='1'>";
        foreach ($event1 as $e) {
            $string .= $e . '<br>';
        }
        $string .= "</td>
			</tr>
			<tr>
				<td height='15' width='1' bgcolor='#a1a5a9'></td>
				<td class='dayborder2' colspan='1'>";
        foreach ($event2 as $e) {
            $string .= $e . '<br>';
        }
        $string .= "</td>
			</tr>
			<tr>
				<td height='15' width='1' bgcolor='#a1a5a9'></td>
				<td class='dayborder' colspan='1'>";
        foreach ($event3 as $e) {
            $string .= $e . '<br>';
        }
        $string .= "</td>
			</tr>
			<tr>
				<td height='15' width='1' bgcolor='#a1a5a9'></td>
				<td class='dayborder2' colspan='1'>";
        foreach ($event4 as $e) {
            $string .= $e . '<br>';
        }
        $string .= "</td>
			</tr>";

        return $string;
    }

    public function addEvent(Util_Calendar_Event $event)
    {
        array_push($this->events, $event);
    }
}