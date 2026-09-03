<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Allergen extends Model
{
    protected $fillable = ['name', 'category'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_allergens');
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_allergens');
    }
}
