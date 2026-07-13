<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lembur extends Model
{
    protected $fillable = ['user_id', 'start_time', 'end_time', 'duration_minutes', 'upah'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
