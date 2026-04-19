<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::factory()->create([
            "name" => "Free Trial",
            "description" => "Try KaraSync for 1 hour.",
            "code" => "trial",
            "price" => 0,
            "days" => 0.05,
            "recommended" => false,
            "bottom_description" => "Trial",
        ]);

        Plan::factory()->create([
            "name" => "1 Day Access",
            "description" => "Perfect for quick sessions.",
            "code" => "one",
            "price" => 150,
            "days" => 1,
            "recommended" => false,
            "bottom_description" => "Buy 1 Day",
        ]);

        Plan::factory()->create([
            "name" => "3 Day Access",
            "description" => "Great for events.",
            "code" => "three",
            "price" => 350,
            "days" => 3,
            "recommended" => true,
            "bottom_description" => "Buy 3 Days",
        ]);
        
        Plan::factory()->create([
            "name" => "1 Week Access",
            "description" => "Karaoke for 7 days.",
            "code" => "seven",
            "price" => 700,
            "days" => 7,
            "recommended" => false,
            "bottom_description" => "Buy 7 Days",
        ]);
    }
}
