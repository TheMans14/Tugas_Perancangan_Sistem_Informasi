@extends('layouts.app')

@section('title', 'Lembur')

@section('content')
<div class="container mt-5">
    <div class="text-center" style="padding-top: 120px;"> {{-- Tambah padding dari atas --}}
        <div id="stopwatch" style="font-size: 4rem; font-weight: bold; margin-bottom: 20px; color: #2d3748;">00:00:00</div>

        <div class="mb-4">
            <button id="startBtn" class="btn btn-success mr-2 px-4 py-2">Start</button>
            <button id="stopBtn" class="btn btn-danger px-4 py-2" disabled>Stop</button>
        </div>

        <div>
            <p>Total: <strong><span id="totalMinutes">0</span> menit</strong></p>
            <p>Upah: <strong>Rp <span id="totalUpah">0</span></strong></p>
        </div>

        <form id="lemburForm" action="{{ route('lembur.store') }}" method="POST">
            @csrf
            <input type="hidden" name="start_time" id="startTimeInput">
            <input type="hidden" name="end_time" id="endTimeInput">
            <input type="hidden" name="duration_minutes" id="durationInput">
            <input type="hidden" name="upah" id="upahInput">
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let startTime, timerInterval;

function toMySQLDatetime(dt) {
    return dt.getFullYear() + "-" +
        String(dt.getMonth() + 1).padStart(2, '0') + "-" +
        String(dt.getDate()).padStart(2, '0') + " " +
        String(dt.getHours()).padStart(2, '0') + ":" +
        String(dt.getMinutes()).padStart(2, '0') + ":" +
        String(dt.getSeconds()).padStart(2, '0');
}

function formatTime(seconds) {
    let h = String(Math.floor(seconds / 3600)).padStart(2, '0');
    let m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
    let s = String(seconds % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
}

document.getElementById('startBtn').addEventListener('click', function () {
    startTime = new Date();
    document.getElementById('startTimeInput').value = toMySQLDatetime(startTime);

    let elapsed = 0;
    timerInterval = setInterval(() => {
        elapsed++;
        document.getElementById('stopwatch').textContent = formatTime(elapsed);
    }, 1000);

    this.disabled = true;
    document.getElementById('stopBtn').disabled = false;
});

document.getElementById('stopBtn').addEventListener('click', function () {
    clearInterval(timerInterval);

    let endTime = new Date();
    document.getElementById('endTimeInput').value = toMySQLDatetime(endTime);

    let duration = Math.ceil((endTime - startTime) / 60000); // in minutes
    if (duration < 60) duration = 60; // minimal 1 jam (60 menit)

    document.getElementById('durationInput').value = duration;
    document.getElementById('upahInput').value = 15000 * Math.floor(duration / 60);
    document.getElementById('totalMinutes').textContent = duration;
    document.getElementById('totalUpah').textContent = 15000 * Math.floor(duration / 60);

    this.disabled = true;
    setTimeout(() => {
        document.getElementById('lemburForm').submit();
    }, 1500);
});
</script>
@endpush
