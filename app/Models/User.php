<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'password',
        'terms_accepted_at',
        'max_requests',
        'module_id',
        'temperature',
        'max_response_tokens',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'id',
        'ref_id',
        'max_requests',
        'module_id',
        'temperature',
        'max_response_tokens',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'terms_accepted_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasManyThrough(
            Messages::class,
            Conversations::class,
            '',
            'conversation_id'
        );
    }

    public function conversations()
    {
        return $this->hasMany(Conversations::class, 'user_id');
    }

    public function module()
    {
        return $this->belongsTo(Modules::class, 'module_id');
    }
}
