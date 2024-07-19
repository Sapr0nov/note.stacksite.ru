<?php

namespace App\Classes\Calendar;

class CalendarClass
{    
    function __construct() {
    }

    function generateCalendar() {
        $numDaysCurrentMonth = date('t');
        $firstDay = date('N', strtotime(date('Y-m-01')));
        $calArray = [];
        
        $row = [];
        for ($i = 1; $i < $firstDay; $i++) {
            $row[] = '-';
        }
        
        for ($i = 1; $i <= $numDaysCurrentMonth; $i++) {
            $row[] = strval($i);
            if (count($row) % 7 == 0) {
                $calArray[] = $row;
                $row = [];
            }
        }
        
        while (count($row) < 7) {
            $row[] = '-';
        }
        
        $calArray[] = $row;
        
        return $calArray;
    }
}

?>
