<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'divisions';

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'created_at',
        'updated_at',
    ];

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function citiesThroughDistricts()
    {
        return $this->hasManyThrough(City::class, District::class, 'division_id', 'district_id', 'id', 'id');
    }
}
