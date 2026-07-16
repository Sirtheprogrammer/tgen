<?php

namespace Database\Seeders;

use App\Models\SonicPesaAccount;
use Illuminate\Database\Seeder;

class SonicPesaAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SonicPesaAccount::query()->delete();

        SonicPesaAccount::create([
            'name' => 'Default',
            'api_key' => env('SONICPESA_API_KEY', 'sk_live_TU7Q0bYOQT5rC4zhOPB3JZRAvtJB82tKczIkhfVc'),
            'base_url' => 'https://api.sonicpesa.com/api/v1/payment',
            'is_active' => true,
        ]);
    }
}
