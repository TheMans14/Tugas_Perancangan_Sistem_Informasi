<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class ManagerAttendanceExport implements FromView
{
    private $presents;

    public function __construct(Collection $presents)
    {
        $this->presents = $presents;
    }

    public function view(): \Illuminate\Contracts\View\View
    {
        return view('presents.users-excel', ['presents' => $this->presents]);
    }
}
