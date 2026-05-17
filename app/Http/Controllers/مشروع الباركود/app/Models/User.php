<?php
namespace App\Models;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,hasApiTokens;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
     public function barcodes()
    {
        return $this->hasMany(Barcode::class);
    }
    public function device_tokens()
{
    return $this->hasMany(Device_tokens::class);
}
    public function notifications()
    {
         return $this->hasMany(Notificationn::class);
    }
    public function categories()
{
    return $this->belongsToMany(Category::class);
}
    protected $fillable = [
        'name',
        'email',
        'password',
        'points',
        'points_total',
        'passwordForPoints',
        'phone_number',
        'code',
        'role',
        'country',
        'city',
        'numberOfNotifications',
        'numberOfFollowers',
        'numberOfScan',
        'numberOfPointsAllowed',
        'pointsConsumed',
        'numberOfPrint'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
