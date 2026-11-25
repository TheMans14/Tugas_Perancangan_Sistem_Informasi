<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Cuti;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_id',
        'nama',
        'nrp',
        'foto',
        'password',
        'jabatan',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function role()
    {
        return $this->belongsTo('App\Role');
    }

    public function hasRole($roles)
    {
        $currentRole = $this->role;
        if (!$currentRole) {
            return false;
        }
        foreach ((array) $roles as $role) {
            if (strcasecmp($currentRole->role, $role) === 0) {
                return true;
            }
        }
        return false;
    }

    public function cutis()
    {
        return $this->hasMany('App\Cuti');

    }


    public function presents()
    {
        return $this->hasMany('App\Present');
    }

}
