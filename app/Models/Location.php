<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent',
        'status',
        'is_deleted'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_deleted', '!=', 1);
    }

    public function scopeInActive($query)
    {
        return $query->where('is_deleted', '=', 1);
    }

    public function parentLocation()
    {
        return $this->belongsTo(Location::class, 'parent');
    }

    public function childLocations()
    {
        return $this->hasMany(Location::class, 'parent');
    }
}
