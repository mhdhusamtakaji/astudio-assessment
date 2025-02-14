<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    // A user can be assigned to many projects
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user');
    }

    // A user can have many timesheets
    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }
}
