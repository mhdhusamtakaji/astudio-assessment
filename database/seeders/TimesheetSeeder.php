<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Timesheet;

class TimesheetSeeder extends Seeder
{
    public function run()
    {
        // Link the test user (ID=1) to Project A (ID=1) with an example timesheet
        Timesheet::create([
            'user_id'    => 1,    // The "test@demo.com" user
            'project_id' => 1,    // "Project A"
            'task_name'  => 'Initial Setup',
            'date'       => '2025-01-01',
            'hours'      => 3.5,
        ]);
    }
}
