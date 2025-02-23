<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\CampaignCall;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $campaign = Campaign::factory()->create();
        $calls = CampaignCall::factory(50)->for($campaign)->create();
        foreach ($calls as $call) {
            if (random_int(0, 100) < 33) {
                Appointment::factory()->for($call, 'call')->create();
            }
        }
    }
}
