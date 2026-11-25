@extends('layouts.welcome')
@section('title')
Home - {{ config('app.name') }}
@endsection
@section('content')
@if (($libur ?? false) && (!$present || !$present->jam_masuk))
<div class="text-center">
    <p>Absen Libur (Hari Libur Nasional {{ $holiday }})</p>
</div>
@else
@if (\Carbon\Carbon::parse($shiftDate)->isWeekend() && (!$present || !$present->jam_masuk))
<div class="text-center">
    <p>Absen Libur</p>
</div>
@else
@if ($present)
@if ($present->keterangan == 'Alpha')
<div class="text-center">
    @if ($bisaCheckin)
        <p>Silahkan Check-in</p>
        <form action="{{ route('kehadiran.check-in') }}" method="post">
            @csrf
            <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
            <button class="btn btn-primary" type="submit">Check-in</button>
        </form>
        @else
        <p>Check-in Belum Tersedia</p>
        <p>Now: {{ date('H:i:s') }}</p>
        <p>Jam Masuk: {{ config('absensi.jam_masuk') }}</p>
        <p>Jam Pulang: {{ config('absensi.jam_pulang') }}</p>
        <p>Keterangan: {{ $present->keterangan ?? 'Tidak Ada' }}</p>

        <p>Hari: {{ date('l') }}</p>

        @endif
</div>
@elseif($present->keterangan == 'Cuti/Ijin')
<div class="text-center">
    <p>Anda Sedang Cuti/Ijin</p>
</div>
@else
<div class="text-center">
    <p>
        Check-in hari ini pukul : ({{ ($present->jam_masuk) }})
    </p>
    @if ($present->jam_keluar)
    <p>Check-out hari ini pukul : ({{ $present->jam_keluar }})</p>
    @else
    @if ($bisaCheckout)
    <p>Jika pekerjaan telah selesai silahkan check-out</p>
    <form action="{{ route('kehadiran.check-out', $present->id) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary">Check-out</button>
    </form>




    @else
    <p>Check-out Belum Tersedia</p>
    @endif
    @endif
</div>
@endif
@else
<div class="text-center">
    @if ($bisaCheckin)
        <p>Silahkan Check-in</p>
        <form action="{{ route('kehadiran.check-in') }}" method="post">
            @csrf
            <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
            <button class="btn btn-primary" type="submit">Check-in</button>
        </form>
        @else
        <p>Check-in Belum Tersedia</p>
        <p>Now: {{ date('H:i:s') }}</p>
        <p>Jam Masuk: {{ config('absensi.jam_masuk') }}</p>
        <p>Jam Pulang: {{ config('absensi.jam_pulang') }}</p>
        <p>Keterangan: {{ $present->keterangan ?? 'Tidak Ada' }}</p>
        <p>Hari: {{ date('l') }}</p>

        @endif
</div>
@endif
@endif
@endif
@endsection
