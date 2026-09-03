<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'date_of_birth',
        'birthday_reward_claimed_year',
        'password',
        'is_admin',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'birthday_reward_claimed_year' => 'integer',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class, 'user_allergens');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
