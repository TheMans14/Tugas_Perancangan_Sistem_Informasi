@extends('layouts.app')
@section('title', 'Persetujuan Perjalanan Dinas')
@section('content')
<div class="container-fluid mt--7">
    <div class="card shadow">
        <div class="card-header"><h3 class="mb-2">Pengajuan Perjalanan Dinas</h3><p class="text-muted mb-0">Tinjau kota, estimasi waktu, dan keperluan perjalanan sebelum memberikan keputusan.</p></div>
        @include('perjalanan_dinas.table', ['review' => true])
        <div class="card-footer">{{ $trips->links() }}</div>
    </div>
</div>
@endsection
