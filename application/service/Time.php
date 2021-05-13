<?php

namespace app\service;

class Time
{
    public static function getBeforeSevenMonth($now)
    {
        [$nowYear, $nowMonth] = explode('-', $now);
        
        $dates[] = [
            'y' => $nowYear,
            'm' => $nowMonth
        ];

        $nodeMonth = (int)$nowMonth;
        $nodeYear = (int)$nowYear;
        for ($i = 6; $i > 0; $i--) {
            if ($nodeMonth > 1) {
                $nodeMonth -= 1;
                array_push($dates, ['y' => (string)$nodeYear, 'm' => (string)$nodeMonth]);
            } else {
                $nodeMonth = 12;
                $nodeYear -= 1;
                array_push($dates, ['y' => (string)$nodeYear, 'm' => '12']);
            }
        }
        return $dates;
    }
}