<?php

namespace Tests\Feature;

use App\Models\SonicPesaAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SonicPesaAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateAdmin(): void
    {
        session(['admin_authenticated' => true]);
    }

    public function test_it_lists_sonicpesa_accounts(): void
    {
        $this->authenticateAdmin();

        SonicPesaAccount::create([
            'name' => 'Account One',
            'api_key' => 'key-one-abcdefghij',
            'is_active' => true,
        ]);

        SonicPesaAccount::create([
            'name' => 'Account Two',
            'api_key' => 'key-two-abcdefghij',
            'is_active' => false,
        ]);

        $response = $this->get(route('sonicpesa-accounts.index'));

        $response->assertOk();
        $response->assertSee('Account One');
        $response->assertSee('Account Two');
    }

    public function test_it_stores_a_new_sonicpesa_account(): void
    {
        $this->authenticateAdmin();

        $response = $this->post(route('sonicpesa-accounts.store'), [
            'name' => 'New Account',
            'api_key' => 'new-key-abcdefghij',
            'base_url' => 'https://api.sonicpesa.com/api/v1/payment',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('sonic_pesa_accounts', [
            'name' => 'New Account',
            'api_key' => 'new-key-abcdefghij',
            'is_active' => true,
        ]);
    }

    public function test_it_updates_a_sonicpesa_account(): void
    {
        $this->authenticateAdmin();

        $account = SonicPesaAccount::create([
            'name' => 'Old Name',
            'api_key' => 'old-key-abcdefghij',
            'is_active' => true,
        ]);

        $response = $this->post(route('sonicpesa-accounts.update', $account), [
            'name' => 'Updated Name',
            'api_key' => 'updated-key-abcdefghij',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('sonic_pesa_accounts', [
            'id' => $account->id,
            'name' => 'Updated Name',
            'api_key' => 'updated-key-abcdefghij',
        ]);
    }

    public function test_it_toggles_a_sonicpesa_account(): void
    {
        $this->authenticateAdmin();

        $account = SonicPesaAccount::create([
            'name' => 'Toggle Account',
            'api_key' => 'toggle-key-abcdefghij',
            'is_active' => true,
        ]);

        $response = $this->post(route('sonicpesa-accounts.toggle', $account));

        $response->assertRedirect();

        $this->assertDatabaseHas('sonic_pesa_accounts', [
            'id' => $account->id,
            'is_active' => false,
        ]);
    }

    public function test_it_deletes_a_sonicpesa_account(): void
    {
        $this->authenticateAdmin();

        $account = SonicPesaAccount::create([
            'name' => 'Delete Me',
            'api_key' => 'delete-key-abcdefghij',
            'is_active' => true,
        ]);

        $response = $this->delete(route('sonicpesa-accounts.destroy', $account));

        $response->assertRedirect();

        $this->assertDatabaseMissing('sonic_pesa_accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_validation_requires_name_and_api_key(): void
    {
        $this->authenticateAdmin();

        $response = $this->post(route('sonicpesa-accounts.store'), [
            'name' => '',
            'api_key' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'api_key']);
    }

    public function test_validation_requires_api_key_min_length(): void
    {
        $this->authenticateAdmin();

        $response = $this->post(route('sonicpesa-accounts.store'), [
            'name' => 'Too Short',
            'api_key' => 'short',
        ]);

        $response->assertSessionHasErrors(['api_key']);
    }

    public function test_guest_cannot_access_sonicpesa_accounts(): void
    {
        $response = $this->get(route('sonicpesa-accounts.index'));

        $response->assertRedirect('/login');
    }
}
