<?php

class Utils
{

    public function __construct()
    {

    }

    public function dateToJUL($date)
    {
        $dateDayNbr = date('z', strtotime($date)) + 1;
        $dateYear = date('y', strtotime($date));
        return "1" . $dateYear . str_pad($dateDayNbr, 3, "0");
    }

    public function JULToDate($date)
    {
        $dateDayNbr = substr($date, 4);
        $dateYear = substr($date, 1, 2);
        $yearStart = "20" . $dateYear . "-01-01";
        return date("Y-m-d", strtotime($yearStart . " + " . $dateDayNbr . " days"));

    }
}


//$Utils = new Utils();
//echo $Utils->dateToJUL("2024-07-19");