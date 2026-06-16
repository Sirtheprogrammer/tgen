<?php

namespace Tests\Feature;

use App\Models\MobilipaAccount;
use App\Models\Page;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class MobilipaAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Session::start();
        session(['admin_authenticated' => true]);
    }

    public function test_it_lists_mobilipa_accounts(): void
    {
        $page = Page::create([
            'title' => 'Account Test Page',
            'slug' => 'account-test-page',
            'template' => 'template1',
            'price' => 5000,
            'payment_gateway' => 'mobilipa',
            'is_active' => true,
        ]);

        $account = MobilipaAccount::create([
            'name' => 'Test Account',
            'api_key' => 'test-api-key',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
        ]);

        Transaction::create([
            'page_id' => $page->id,
            'buyer_email' => 'test@example.com',
            'buyer_name' => 'Test',
            'buyer_phone' => '255712345678',
            'amount' => 5000,
            'currency' => 'TZS',
            'gateway' => 'mobilipa',
            'payment_status' => 'COMPLETED',
            'order_id' => 'ord_1',
            'mobilipa_account_id' => $account->id,
        ]);

        $response = $this->get(route('mobilipa-accounts.index'));

        $response->assertOk();
        $response->assertSee('Test Account');
        $response->assertSee('1 completed');
        $response->assertSee('TZS 5,000');
    }

    public function test_it_stores_a_new_mobilipa_account(): void
    {
        $response = $this->post(route('mobilipa-accounts.store'), [
            'name' => 'New Account',
            'api_key' => 'new-api-key-123',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('mobilipa_accounts', [
            'name' => 'New Account',
            'api_key' => 'new-api-key-123',
        ]);
    }

    public function test_it_updates_a_mobilipa_account(): void
    {
        $account = MobilipaAccount::create([
            'name' => 'Old Account',
            'api_key' => 'old-api-key',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
        ]);

        $response = $this->post(route('mobilipa-accounts.update', $account), [
            'name' => 'Updated Account',
            'api_key' => 'updated-api-key',
            'base_url' => 'https://api.mobilipa.store',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('mobilipa_accounts', [
            'id' => $account->id,
            'name' => 'Updated Account',
            'api_key' => 'updated-api-key',
        ]);
    }

    public function test_it_toggles_a_mobilipa_account(): void
    {
        $account = MobilipaAccount::create([
            'name' => 'Toggle Account',
            'api_key' => 'toggle-api-key',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
        ]);

        $response = $this->post(route('mobilipa-accounts.toggle', $account));

        $response->assertRedirect();
        $this->assertDatabaseHas('mobilipa_accounts', [
            'id' => $account->id,
            'is_active' => false,
        ]);
    }

    public function test_it_deletes_a_mobilipa_account(): void
    {
        $account = MobilipaAccount::create([
            'name' => 'Delete Account',
            'api_key' => 'delete-api-key',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
        ]);

        $response = $this->delete(route('mobilipa-accounts.destroy', $account));

        $response->assertRedirect();
        $this->assertDatabaseMissing('mobilipa_accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->post(route('mobilipa-accounts.store'), [
            'name' => '',
            'api_key' => 'short',
        ]);

        $response->assertSessionHasErrors(['name', 'api_key']);
    }

    public function test_guest_cannot_access_mobilipa_accounts(): void
    {
        session()->forget('admin_authenticated');

        $response = $this->get(route('mobilipa-accounts.index'));

        $response->assertRedirect('/login');
    }
}
