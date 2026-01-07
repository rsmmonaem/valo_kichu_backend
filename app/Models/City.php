<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $guarded = [];

    protected $fillable = [
        'name',
        'district_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'district_id' => 'integer',
        'created_at',
        'updated_at',
    ];

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function division()
    {
        return $this->hasOneThrough(Division::class, District::class, 'id', 'id', 'district_id', 'division_id');
    }
}
