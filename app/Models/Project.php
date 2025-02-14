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
}
