@extends('layouts.app')
@section('content')

<div class="container text-center">
    <h3>Lembur Hari Ini</h3>
    
    <div id="stopwatch" class="my-3 display-4">00:00:00</div>

    <button id="startBtn" class="btn btn-success">Start</button>
    <button id="stopBtn" class="btn btn-danger" disabled>Stop</button>

    <form id="lemburForm" method="POST" action="{{ route('lembur.store') }}">
        @csrf
        <input type="hidden" name="start_time" id="start_time">
        <input type="hidden" name="end_time" id="end_time">
        <input type="hidden" name="duration_minutes" id="duration_minutes">
        <input type="hidden" name="upah" id="upah">
    </form>

    <div class="mt-3" id="result" style="display:none;"></div>
</div>

<script>
let timer, seconds = 0;
let startTimestamp, endTimestamp;

function format(seconds) {
    let hrs = String(Math.floor(seconds / 3600)).padStart(2, '0');
    let mins = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
    let secs = String(seconds % 60).padStart(2, '0');
    return `${hrs}:${mins}:${secs}`;
}

document.getElementById('startBtn').onclick = function () {
    seconds = 0;
    startTimestamp = new Date();
    document.getElementById('start_time').value = startTimestamp.toISOString();

    timer = setInterval(() => {
        seconds++;
        document.getElementById('stopwatch').textContent = format(seconds);
    }, 1000);

    this.disabled = true;
    document.getElementById('stopBtn').disabled = false;
};

document.getElementById('stopBtn').onclick = function () {
    clearInterval(timer);
    endTimestamp = new Date();
    document.getElementById('end_time').value = endTimestamp.toISOString();

    // hitung durasi dalam menit
    let diffMin = Math.ceil((endTimestamp - startTimestamp) / 60000);
    let durasi = Math.max(diffMin, 60); // minimal 1 jam
    let upah = Math.ceil(durasi / 60) * 15000;

    document.getElementById('duration_minutes').value = durasi;
    document.getElementById('upah').value = upah;

    document.getElementById('result').style.display = 'block';
    document.getElementById('result').innerHTML = `<h4>Total Lembur: ${durasi} menit</h4><h5>Upah: Rp ${upah.toLocaleString('id-ID')}</h5>`;

    this.disabled = true;

    setTimeout(() => {
        document.getElementById('lemburForm').submit();
    }, 3000);
};
</script>

@endsection
