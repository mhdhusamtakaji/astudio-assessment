<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type'];

    // An attribute can have many attribute values
    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
