<?php

namespace Database\Seeders;

use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;

class SchoolSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (SchoolSetting::count() === 0) {
            SchoolSetting::create([
                'school_name' => 'Circle Spring Academy',
                'motto' => 'My Character, My Destiny',
                'location' => 'Nairobi, Kenya',
                'logo_path' => null,
            ]);
        }
    }
}
