<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;

class ProjectSeeder extends Seeder
{
    public function run()
    {
        // sample projects
        Project::create([
            'name'   => 'Project A',
            'status' => 'Open',
        ]);

        Project::create([
            'name'   => 'Project B',
            'status' => 'Closed',
        ]);
    }
}
