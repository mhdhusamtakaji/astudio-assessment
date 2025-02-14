<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timesheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'task_name',
        'date',
        'hours',
    ];

    // Each timesheet record belongs to one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Each timesheet record belongs to one project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
