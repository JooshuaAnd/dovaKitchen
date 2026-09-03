<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'service_date',
    ];

    protected $casts = [
        'service_date' => 'date',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
