@extends('layouts.app')
@section('title', 'Rekap Absensi - Manager')
@section('content')
<div class="container-fluid mt--7">
    <div class="card shadow">
        <div class="card-header">
            <h3 class="mb-2">Rekap Absensi User dan Admin</h3>
            <p class="text-muted mb-3">Pilih bulan dan akun untuk melihat rekap kehadiran.</p>
            <form method="GET" action="{{ route('manager.rekap') }}" class="row align-items-end">
                <div class="col-md-3 form-group">
                    <label for="bulan">Bulan</label>
                    <input class="form-control" type="month" name="bulan" id="bulan" value="{{ $bulan }}" required>
                </div>
                <div class="col-md-5 form-group">
                    <label for="user_id">Akun</label>
                    <select class="form-control" name="user_id" id="user_id">
                        <option value="">Semua user dan admin</option>
                        @foreach ($users as $account)
                        <option value="{{ $account->id }}" {{ request('user_id') == $account->id ? 'selected' : '' }}>{{ $account->nama }} — {{ $account->nrp }} ({{ $account->role->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <button class="btn btn-primary" type="submit">Tampilkan</button>
                    <a class="btn btn-success" href="{{ route('manager.rekap.export', ['bulan' => $bulan, 'user_id' => request('user_id')]) }}">Export Excel</a>
                </div>
            </form>
            <div class="d-flex flex-wrap">
                <span class="badge badge-success mr-2 mb-2">Masuk: {{ $summary['masuk'] ?? 0 }}</span>
                <span class="badge badge-warning mr-2 mb-2">Telat: {{ $summary['telat'] ?? 0 }}</span>
                <span class="badge badge-info mr-2 mb-2">Cuti: {{ $summary['cuti'] ?? 0 }}</span>
                <span class="badge badge-danger mr-2 mb-2">Alpha: {{ $summary['alpha'] ?? 0 }}</span>
            </div>
            <a href="{{ route('admin.cuti.index') }}">Pengajuan cuti menunggu persetujuan: {{ $pendingCuti }}</a>
        </div>
        <div class="table-responsive">
            <table class="table align-items-center table-flush">
                <thead class="thead-light"><tr><th>Tanggal</th><th>NRP</th><th>Nama</th><th>Role</th><th>Masuk</th><th>Pulang</th><th>Keterangan</th><th>Durasi</th></tr></thead>
                <tbody>
                    @forelse ($presents as $present)
                    <tr>
                        <td>{{ date('d/m/Y', strtotime($present->tanggal)) }}</td>
                        <td>{{ $present->user->nrp }}</td><td>{{ $present->user->nama }}</td><td>{{ $present->user->role->role }}</td>
                        <td>{{ $present->jam_masuk ?? '-' }}</td><td>{{ $present->jam_keluar ?? '-' }}</td><td>{{ $present->keterangan }}</td>
                        <td>
                            @if ($present->jam_masuk && $present->jam_keluar)
                            @php($minutes = \App\Support\AttendanceShift::workedMinutes($present->jam_masuk, $present->jam_keluar))
                            {{ floor($minutes / 60) }} jam {{ $minutes % 60 }} menit
                            @else - @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4">Belum ada data absensi untuk pilihan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $presents->links() }}</div>
    </div>
</div>
@endsection
