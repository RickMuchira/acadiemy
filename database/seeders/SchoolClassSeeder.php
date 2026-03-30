<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class SchoolClassSeeder extends Seeder
{
    public function run(): void
    {
        $streamedLevels = ['PP1', 'PP2', 'Grade1', 'Grade2', 'Grade3'];

        foreach ($streamedLevels as $level) {
            foreach (['GREEN', 'RED'] as $stream) {
                SchoolClass::firstOrCreate(
                    ['level' => $level, 'stream' => $stream],
                    ['class_teacher_name' => '']
                );
            }
        }

        foreach (['PG', 'Grade4', 'Grade5', 'Grade6'] as $level) {
            SchoolClass::firstOrCreate(
                ['level' => $level, 'stream' => null],
                ['class_teacher_name' => '']
            );
        }
    }
}
