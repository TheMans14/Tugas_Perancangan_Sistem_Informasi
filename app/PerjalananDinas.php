<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PerjalananDinas extends Model
{
    protected $table = 'perjalanan_dinas';

    protected $fillable = ['user_id', 'kota', 'mulai_at', 'selesai_at', 'alasan'];

    protected $casts = ['mulai_at' => 'datetime', 'selesai_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getEstimasiMenitAttribute()
    {
        return $this->mulai_at->diffInMinutes($this->selesai_at);
    }

    public function apiData()
    {
        return [
            'id' => $this->id, 'kota' => $this->kota, 'alasan' => $this->alasan,
            'mulai_at' => $this->mulai_at->format('Y-m-d H:i:s'),
            'selesai_at' => $this->selesai_at->format('Y-m-d H:i:s'),
            'estimasi_menit' => $this->estimasi_menit, 'status' => $this->status,
            'reviewed_at' => $this->reviewed_at ? $this->reviewed_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
