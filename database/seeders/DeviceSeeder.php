<?php

namespace Database\Seeders;

use App\Models\Device;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        for ($i = 0; $i < 5; $i++) { // Generate 5 dummy devices
            Device::create([
                'name' => $faker->word.' Device',
                'ip_address' => $faker->unique()->localIpv4,
                'location' => $faker->city,
                'is_active' => $faker->boolean,
            ]);
        }
    }
}
