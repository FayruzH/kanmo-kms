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
        'email',
        'nip',
        'password',
        'role',
        'department',
        'entity',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
    ];

    public static function normalizeNip(string $nip): string
    {
        $value = trim($nip);

        if ($value !== '' && ctype_digit($value) && strlen($value) <= 10) {
            return str_pad($value, 10, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    public function setNipAttribute(?string $value): void
    {
        $this->attributes['nip'] = $value === null ? null : self::normalizeNip($value);
    }
}
