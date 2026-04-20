<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use Illuminate\Database\Seeder;

class FallbackOrientationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Prepa if it doesn't exist
        $prepa = AcademicLevel::firstOrCreate(
            ['slug' => 'prepa'],
            ['name' => 'Prepa', 'order' => 0]
        );

        // 2. Fetch other levels
        $bts = AcademicLevel::where('slug', 'bts')->first();
        $bachelor = AcademicLevel::where('slug', 'bachelor')->first();
        $mastere = AcademicLevel::where('slug', 'mastere')->first();

        // 3. Set fallbacks
        if ($mastere && $bachelor) {
            $mastere->update(['fallback_level_id' => $bachelor->id]);
        }

        if ($bachelor && $bts) {
            $bachelor->update(['fallback_level_id' => $bts->id]);
        }

        if ($bts && $prepa) {
            $bts->update(['fallback_level_id' => $prepa->id]);
        }
    }
}
