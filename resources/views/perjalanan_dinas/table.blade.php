<div class="table-responsive">
    <table class="table align-items-center table-flush">
        <thead class="thead-light"><tr>
            @if ($review)<th>Pemohon</th>@endif
            <th>Kota</th><th>Estimasi waktu (WIB)</th><th>Durasi</th><th>Alasan</th><th>Status</th><th>{{ $review ? 'Keputusan' : 'Ditinjau' }}</th>
        </tr></thead>
        <tbody>
            @forelse ($trips as $trip)
            <tr>
                @if ($review)<td>{{ $trip->user->nama }}<br><small>{{ $trip->user->nrp }} · {{ $trip->user->role->role }}</small></td>@endif
                <td>{{ $trip->kota }}</td>
                <td>{{ $trip->mulai_at->format('d/m/Y H:i') }}<br>sampai {{ $trip->selesai_at->format('d/m/Y H:i') }}</td>
                <td>{{ floor($trip->estimasi_menit / 60) }} jam {{ $trip->estimasi_menit % 60 }} menit</td>
                <td style="white-space:normal;min-width:180px">{{ $trip->alasan }}</td>
                <td><span class="badge badge-{{ $trip->status === 'pending' ? 'warning' : ($trip->status === 'disetujui' ? 'success' : 'danger') }}">{{ $trip->status === 'pending' ? 'Menunggu' : ucfirst($trip->status) }}</span></td>
                <td>
                    @if ($review && $trip->status === 'pending')
                    <form class="d-inline" method="POST" action="{{ route('manager.perjalanan-dinas.approve', $trip) }}">@csrf<button class="btn btn-sm btn-success" type="submit">Setujui</button></form>
                    <form class="d-inline" method="POST" action="{{ route('manager.perjalanan-dinas.reject', $trip) }}">@csrf<button class="btn btn-sm btn-danger" type="submit">Tolak</button></form>
                    @else
                    {{ $trip->reviewed_at ? $trip->reviewed_at->format('d/m/Y H:i') : '-' }}
                    @if ($review && $trip->reviewer)<br><small>{{ $trip->reviewer->nama }}</small>@endif
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="{{ $review ? 7 : 6 }}" class="text-center py-4">Belum ada pengajuan perjalanan dinas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
