<?php

namespace Database\Seeders;

use App\Models\MobilipaAccount;
use Illuminate\Database\Seeder;

class MobilipaAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MobilipaAccount::query()->delete();

        MobilipaAccount::create([
            'name' => 'Default',
            'api_key' => env('MOBILIPA_API_KEY', 'sk_live_YN0RUW4o2EQ3SxLNOiyyj25Ldfs37KyBHk1GSbda'),
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
        ]);
    }
}
