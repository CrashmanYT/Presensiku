<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Carbon\Carbon;
use Faker\Factory as Faker;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        for ($i = 0; $i < 10; $i++) { // Generate 10 holidays
            $startDate = Carbon::instance($faker->dateTimeBetween('-6 months', '+6 months'));
            $endDate = (clone $startDate)->addDays($faker->numberBetween(0, 5)); // Holiday can be 0-5 days long

            Holiday::create([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'description' => $faker->sentence(3) . ' Nasional',
            ]);
        }
    }
}
