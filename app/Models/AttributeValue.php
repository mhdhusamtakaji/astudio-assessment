<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_id',
        'entity_id', // references projects.id
        'value'
    ];

    // Each attribute value belongs to a specific attribute
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    // if we decide to create the forign key, here is a direct relationship to Project
    // public function project()
    // {
    //     return $this->belongsTo(Project::class, 'entity_id');
    // }
}
