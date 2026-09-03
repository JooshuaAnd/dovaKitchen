<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'photo_path',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class, 'menu_allergens');
    }

    public function dailyMenus()
    {
        return $this->hasMany(DailyMenu::class);
    }
}
