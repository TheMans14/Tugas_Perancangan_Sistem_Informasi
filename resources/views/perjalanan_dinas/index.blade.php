@extends('layouts.app')
@section('title', 'Perjalanan Dinas')
@section('content')
<div class="container-fluid mt--7">
    <div class="card shadow mb-4">
        <div class="card-header"><h3 class="mb-0">Ajukan Perjalanan Dinas</h3></div>
        <div class="card-body">
            <p class="text-muted">Isi kota tujuan dan estimasi waktu perjalanan (WIB). Pengajuan akan ditinjau oleh Manager.</p>
            @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form action="{{ route('perjalanan-dinas.store') }}" method="POST">
                @csrf
                <div class="form-group"><label for="kota">Kota tujuan</label><input class="form-control" id="kota" name="kota" maxlength="100" value="{{ old('kota') }}" required></div>
                <div class="row">
                    <div class="col-md-6 form-group"><label for="mulai_at">Estimasi berangkat (WIB)</label><input class="form-control" id="mulai_at" name="mulai_at" type="datetime-local" value="{{ old('mulai_at') }}" required></div>
                    <div class="col-md-6 form-group"><label for="selesai_at">Estimasi selesai (WIB)</label><input class="form-control" id="selesai_at" name="selesai_at" type="datetime-local" value="{{ old('selesai_at') }}" required></div>
                </div>
                <div class="form-group"><label for="alasan">Alasan / keperluan dinas</label><textarea class="form-control" id="alasan" name="alasan" rows="3" maxlength="2000" required>{{ old('alasan') }}</textarea></div>
                <button class="btn btn-primary" type="submit">Kirim Pengajuan</button>
            </form>
        </div>
    </div>
    <div class="card shadow">
        <div class="card-header"><h3 class="mb-0">Riwayat Perjalanan Dinas</h3></div>
        @include('perjalanan_dinas.table', ['review' => false])
        <div class="card-footer">{{ $trips->links() }}</div>
    </div>
</div>
@endsection
