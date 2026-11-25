<table>
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>NRP</th>
            <th>Nama</th>
            <th>Jabatan</th>
            <th>Jam Masuk</th>
            <th>Jam Keluar</th>
            <th>Cuti</th>
            <th>Total Jam</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($presents as $present)
        <tr>
            <td>{{ date('d/m/Y', strtotime($present->tanggal)) }}</td>
            <td>{{ $present->user->nrp }}</td>
            <td>{{ $present->user->nama }}</td>
            <td>{{ $present->user->jabatan ?? '-' }}</td>
            <td>{{ $present->jam_masuk ? date('H:i:s', strtotime($present->jam_masuk)) : '-' }}</td>
            <td>{{ $present->jam_keluar ? date('H:i:s', strtotime($present->jam_keluar)) : '-' }}</td>
            <td>{{ $present->keterangan == 'cuti' ? 'Ya' : '-' }}</td>
            <td>
                @if ($present->jam_masuk && $present->jam_keluar)
                    {{ \App\Support\AttendanceShift::workedMinutes($present->jam_masuk, $present->jam_keluar) / 60 }}
                    @else
                    -
                    @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
