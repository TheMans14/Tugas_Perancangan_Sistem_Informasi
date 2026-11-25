<?php

namespace App\Support;

use Carbon\Carbon;

class AttendanceShift
{
    public static function window($date)
    {
        $start = Carbon::parse($date . ' ' . config('absensi.jam_masuk'));
        $end = Carbon::parse($date . ' ' . config('absensi.jam_pulang'));
        if ($end->lte($start)) {
            $end->addDay();
        }
        return [$start->copy()->subHour(), $start, $end];
    }

    // Keep the previous overnight shift active until the next check-in window.
    public static function date(Carbon $now)
    {
        list($opens, $start, $end) = self::window($now->toDateString());
        return $end->toDateString() !== $start->toDateString() && $now->lt($opens)
            ? $now->copy()->subDay()->toDateString()
            : $now->toDateString();
    }

    public static function canCheckIn(Carbon $now)
    {
        $date = self::date($now);
        list($opens, $start, $end) = self::window($date);
        return !Carbon::parse($date)->isWeekend() && $now->between($opens, $end);
    }

    public static function canCheckOut(Carbon $now, $date)
    {
        list($opens, $start, $end) = self::window($date);
        return $now->gte($end);
    }

    public static function description(Carbon $now)
    {
        list($opens, $start, $end) = self::window(self::date($now));
        if ($now->lt($opens) || $now->gt($end)) {
            return 'Alpha';
        }
        return $now->lte($start) ? 'Masuk' : 'Telat';
    }

    public static function descriptionForTime($date, $time)
    {
        list($opens, $start, $end) = self::window($date);
        $arrival = Carbon::parse($date . ' ' . $time);
        if ($end->toDateString() !== $start->toDateString() && $arrival->lt($opens)) {
            $arrival->addDay();
        }
        return $arrival->lt($opens) || $arrival->gt($end)
            ? 'Alpha' : ($arrival->lte($start) ? 'Masuk' : 'Telat');
    }

    public static function workedMinutes($arrival, $departure)
    {
        $start = Carbon::parse('2000-01-01 ' . $arrival);
        $end = Carbon::parse('2000-01-01 ' . $departure);
        if ($end->lt($start)) {
            $end->addDay();
        }
        return $start->diffInMinutes($end);
    }

    public static function lateHours($date, $time)
    {
        list($opens, $start, $end) = self::window($date);
        $arrival = Carbon::parse($date . ' ' . $time);
        if ($end->toDateString() !== $start->toDateString() && $arrival->lt($opens)) {
            $arrival->addDay();
        }
        return $arrival->gt($start) ? $start->diffInHours($arrival) : 0;
    }
}
