<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PaymentGateway;
use App\Models\SonicPesaAccount;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SonicPesaPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_sonicpesa_payment_order_with_sub_account(): void
    {
        $account = SonicPesaAccount::create([
            'name' => 'Sub-Account A',
            'api_key' => 'sub-account-api-key',
            'base_url' => 'https://api.sonicpesa.com/api/v1/payment',
            'is_active' => true,
        ]);

        PaymentGateway::create([
            'name' => 'sonicpesa',
            'display_name' => 'SonicPesa',
            'api_key' => 'main-gateway-key',
            'base_url' => 'https://api.sonicpesa.com/api/v1/payment',
            'is_active' => true,
            'description' => 'SonicPesa',
        ]);

        $page = Page::create([
            'title' => 'SonicPesa Page',
            'slug' => 'sonicpesa-sub-page',
            'template' => 'template1',
            'price' => 3000,
            'payment_gateway' => 'sonicpesa',
            'is_active' => true,
            'sonicpesa_account_id' => $account->id,
        ]);

        Mail::fake();

        Http::fake([
            'https://api.sonicpesa.com/api/v1/payment/create_order' => Http::response([
                'status' => 'success',
                'data' => [
                    'order_id' => 'sp_sub_xyz99',
                    'reference' => 'ref_sub_xyz99',
                    'transid' => 'trx_sub_xyz99',
                    'msisdn' => '255712345678',
                    'amount' => 3000,
                    'currency' => 'TZS',
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('payments.create-order'), [
            'page_id' => $page->id,
            'buyer_phone' => '0712345678',
            'buyer_name' => 'Jane Doe',
            'buyer_email' => 'jane@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.order_id', 'sp_sub_xyz99');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.sonicpesa.com/api/v1/payment/create_order'
                && $request->hasHeader('X-API-KEY')
                && $request->header('X-API-KEY')[0] === 'sub-account-api-key'
                && $request['buyer_phone'] === '255712345678'
                && $request['amount'] === 3000;
        });

        $this->assertDatabaseHas('transactions', [
            'page_id' => $page->id,
            'gateway' => 'sonicpesa',
            'order_id' => 'sp_sub_xyz99',
            'payment_status' => 'PENDING',
            'sonicpesa_account_id' => $account->id,
        ]);
    }

    public function test_it_falls_back_to_gateway_api_key_when_no_sub_account(): void
    {
        PaymentGateway::create([
            'name' => 'sonicpesa',
            'display_name' => 'SonicPesa',
            'api_key' => 'main-gateway-key-only',
            'base_url' => 'https://api.sonicpesa.com/api/v1/payment',
            'is_active' => true,
            'description' => 'SonicPesa',
        ]);

        $page = Page::create([
            'title' => 'SonicPesa No Sub',
            'slug' => 'sonicpesa-no-sub',
            'template' => 'template1',
            'price' => 2000,
            'payment_gateway' => 'sonicpesa',
            'is_active' => true,
            'sonicpesa_account_id' => null,
        ]);

        Mail::fake();

        Http::fake([
            'https://api.sonicpesa.com/api/v1/payment/create_order' => Http::response([
                'status' => 'success',
                'data' => [
                    'order_id' => 'sp_main_abc12',
                    'reference' => 'ref_main_abc12',
                    'transid' => 'trx_main_abc12',
                    'msisdn' => '255787654321',
                    'amount' => 2000,
                    'currency' => 'TZS',
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('payments.create-order'), [
            'page_id' => $page->id,
            'buyer_phone' => '0787654321',
            'buyer_name' => 'No Sub',
            'buyer_email' => 'nosub@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.sonicpesa.com/api/v1/payment/create_order'
                && $request->header('X-API-KEY')[0] === 'main-gateway-key-only';
        });

        $this->assertDatabaseHas('transactions', [
            'gateway' => 'sonicpesa',
            'sonicpesa_account_id' => null,
        ]);
    }

    public function test_it_checks_sonicpesa_payment_status_with_sub_account(): void
    {
        $account = SonicPesaAccount::create([
            'name' => 'Sub-Account B',
            'api_key' => 'sub-account-b-key',
            'base_url' => 'https://api.sonicpesa.com/api/v1/payment',
            'is_active' => true,
        ]);

        PaymentGateway::create([
            'name' => 'sonicpesa',
            'display_name' => 'SonicPesa',
            'api_key' => 'gateway-key-b',
            'base_url' => 'https://api.sonicpesa.com/api/v1/payment',
            'is_active' => true,
            'description' => 'SonicPesa',
        ]);

        $page = Page::create([
            'title' => 'SonicPesa Status',
            'slug' => 'sonicpesa-status',
            'template' => 'template1',
            'price' => 4000,
            'payment_gateway' => 'sonicpesa',
            'is_active' => true,
            'sonicpesa_account_id' => $account->id,
        ]);

        $transaction = Transaction::create([
            'page_id' => $page->id,
            'buyer_email' => 'status@example.com',
            'buyer_name' => 'Status Test',
            'buyer_phone' => '255765432109',
            'amount' => 4000,
            'currency' => 'TZS',
            'gateway' => 'sonicpesa',
            'payment_status' => 'PENDING',
            'order_id' => 'sp_stat_444',
            'sonicpesa_account_id' => $account->id,
        ]);

        Mail::fake();

        Http::fake([
            'https://api.sonicpesa.com/api/v1/payment/order_status' => Http::response([
                'status' => 'success',
                'data' => [
                    'transid' => 'sp_stat_444',
                    'payment_status' => 'COMPLETED',
                    'amount' => 4000,
                    'channel' => 'VODACOM',
                ],
            ]),
        ]);

        $response = $this->postJson(route('payments.check-status'), [
            'transaction_id' => $transaction->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('payment_status', 'COMPLETED');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.sonicpesa.com/api/v1/payment/order_status'
                && $request->header('X-API-KEY')[0] === 'sub-account-b-key';
        });

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'payment_status' => 'COMPLETED',
            'sonicpesa_account_id' => $account->id,
        ]);

        Mail::assertNothingSent();
    }

    public function test_it_rejects_sonicpesa_order_when_sub_account_is_inactive(): void
    {
        $account = SonicPesaAccount::create([
            'name' => 'Inactive Sub',
            'api_key' => 'inactive-sub-key',
            'base_url' => 'https://api.sonicpesa.com/api/v1/payment',
            'is_active' => false,
        ]);

        PaymentGateway::create([
            'name' => 'sonicpesa',
            'display_name' => 'SonicPesa',
            'api_key' => 'main-gateway-key',
            'base_url' => 'https://api.sonicpesa.com/api/v1/payment',
            'is_active' => true,
            'description' => 'SonicPesa',
        ]);

        $page = Page::create([
            'title' => 'SonicPesa Inactive',
            'slug' => 'sonicpesa-inactive',
            'template' => 'template1',
            'price' => 1000,
            'payment_gateway' => 'sonicpesa',
            'is_active' => true,
            'sonicpesa_account_id' => $account->id,
        ]);

        Http::fake();

        $response = $this->postJson(route('payments.create-order'), [
            'page_id' => $page->id,
            'buyer_phone' => '0711111111',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', 'error');
        $response->assertJsonPath('message', 'SonicPesa sub-account is not found or inactive.');

        $this->assertDatabaseMissing('transactions', [
            'gateway' => 'sonicpesa',
            'sonicpesa_account_id' => $account->id,
        ]);
    }
}
