<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
    ];

    // A project can have many users
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user');
    }

    // A project can have many timesheets
    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class, 'entity_id');
    }
    
    /**
     * Helper to retrieve an attribute value by attribute name.
     */
    public function getDynamicAttribute($attributeName)
    {
        // E.g. find the attribute ID by name, then find the value
        $attribute = Attribute::where('name', $attributeName)->first();
        if (!$attribute) {
            return null;
        }
        $attrValue = $this->attributeValues()
                          ->where('attribute_id', $attribute->id)
                          ->first();
        return $attrValue ? $attrValue->value : null;
    }

}
