<?php

namespace Tests\Feature;

use App\Models\MobilipaAccount;
use App\Models\Page;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MobilipaPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_mobilipa_payment_order_with_sub_account(): void
    {
        $account = MobilipaAccount::create([
            'name' => 'Sub-Account A',
            'api_key' => 'sub-account-api-key',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
        ]);

        PaymentGateway::create([
            'name' => 'mobilipa',
            'display_name' => 'Mobilipa',
            'api_key' => 'main-gateway-key',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
            'description' => 'Mobilipa',
        ]);

        $page = Page::create([
            'title' => 'Mobilipa Page',
            'slug' => 'mobilipa-sub-page',
            'template' => 'template1',
            'price' => 10000,
            'payment_gateway' => 'mobilipa',
            'is_active' => true,
            'mobilipa_account_id' => $account->id,
        ]);

        Mail::fake();

        Http::fake([
            'https://api.mobilipa.store/v1/payment/create_order' => Http::response([
                'status' => 'success',
                'message' => 'Payment order created successfully! Push USSD sent to your phone.',
                'data' => [
                    'order_id' => 'sp_sub_xyz99',
                    'reference' => 'S20376448003',
                    'amount' => 10000,
                    'currency' => 'TZS',
                    'payment_status' => 'PENDING',
                    'status' => 'PENDING',
                    'creation_date' => '2026-01-16 21:09:49',
                    'msisdn' => '255695123456',
                ],
            ], 201),
        ]);

        $response = $this->postJson(route('payments.create-order'), [
            'page_id' => $page->id,
            'buyer_phone' => '0695123456',
            'buyer_name' => 'John Doe',
            'buyer_email' => 'john@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.order_id', 'sp_sub_xyz99');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.mobilipa.store/v1/payment/create_order'
                && $request->hasHeader('X-API-KEY')
                && $request->header('X-API-KEY')[0] === 'sub-account-api-key'
                && $request['buyer_phone'] === '255695123456'
                && $request['amount'] === 10000;
        });

        $this->assertDatabaseHas('transactions', [
            'page_id' => $page->id,
            'gateway' => 'mobilipa',
            'order_id' => 'sp_sub_xyz99',
            'payment_status' => 'PENDING',
            'mobilipa_account_id' => $account->id,
        ]);
    }

    public function test_it_falls_back_to_gateway_api_key_when_no_sub_account(): void
    {
        PaymentGateway::create([
            'name' => 'mobilipa',
            'display_name' => 'Mobilipa',
            'api_key' => 'main-gateway-key-only',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
            'description' => 'Mobilipa',
        ]);

        $page = Page::create([
            'title' => 'Mobilipa No Sub',
            'slug' => 'mobilipa-no-sub',
            'template' => 'template1',
            'price' => 5000,
            'payment_gateway' => 'mobilipa',
            'is_active' => true,
            'mobilipa_account_id' => null,
        ]);

        Mail::fake();

        Http::fake([
            'https://api.mobilipa.store/v1/payment/create_order' => Http::response([
                'status' => 'success',
                'message' => 'Payment order created successfully! Push USSD sent to your phone.',
                'data' => [
                    'order_id' => 'sp_main_abc12',
                    'reference' => 'S20376448004',
                    'amount' => 5000,
                    'currency' => 'TZS',
                    'payment_status' => 'PENDING',
                    'status' => 'PENDING',
                    'creation_date' => '2026-01-16 21:09:49',
                    'msisdn' => '255787654321',
                ],
            ], 201),
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
            return $request->url() === 'https://api.mobilipa.store/v1/payment/create_order'
                && $request->hasHeader('X-API-KEY')
                && $request->header('X-API-KEY')[0] === 'main-gateway-key-only';
        });

        $this->assertDatabaseHas('transactions', [
            'gateway' => 'mobilipa',
            'mobilipa_account_id' => null,
        ]);
    }

    public function test_it_checks_a_mobilipa_payment_status_with_sub_account(): void
    {
        $account = MobilipaAccount::create([
            'name' => 'Sub-Account B',
            'api_key' => 'sub-account-b-key',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
        ]);

        PaymentGateway::create([
            'name' => 'mobilipa',
            'display_name' => 'Mobilipa',
            'api_key' => 'gateway-key-b',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
            'description' => 'Mobilipa',
        ]);

        $page = Page::create([
            'title' => 'Mobilipa Status',
            'slug' => 'mobilipa-status',
            'template' => 'template1',
            'price' => 10000,
            'payment_gateway' => 'mobilipa',
            'is_active' => true,
            'mobilipa_account_id' => $account->id,
        ]);

        $transaction = Transaction::create([
            'page_id' => $page->id,
            'buyer_email' => 'status@example.com',
            'buyer_name' => 'Status Test',
            'buyer_phone' => '255765432109',
            'amount' => 10000,
            'currency' => 'TZS',
            'gateway' => 'mobilipa',
            'payment_status' => 'PENDING',
            'order_id' => 'sp_stat_444',
            'mobilipa_account_id' => $account->id,
        ]);

        Mail::fake();

        Http::fake([
            'https://api.mobilipa.store/v1/payment/status' => Http::response([
                'status' => 'success',
                'message' => 'Order status retrieved successfully!',
                'data' => [
                    'order_id' => 'sp_stat_444',
                    'payment_status' => 'COMPLETED',
                    'transid' => '807399829307',
                    'reference' => '1540671137',
                    'amount' => '10000',
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
            return $request->url() === 'https://api.mobilipa.store/v1/payment/status'
                && $request->hasHeader('X-API-KEY')
                && $request->header('X-API-KEY')[0] === 'sub-account-b-key';
        });

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'payment_status' => 'COMPLETED',
            'mobilipa_account_id' => $account->id,
        ]);

        Mail::assertNothingSent();
    }

    public function test_it_handles_mobilipa_cancelled_status(): void
    {
        PaymentGateway::create([
            'name' => 'mobilipa',
            'display_name' => 'Mobilipa',
            'api_key' => 'sk_live_mobilipa_test',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
            'description' => 'Mobilipa',
        ]);

        $page = Page::create([
            'title' => 'Mobilipa Page',
            'slug' => 'mobilipa-cancelled-page',
            'template' => 'template1',
            'price' => 10000,
            'payment_gateway' => 'mobilipa',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'page_id' => $page->id,
            'buyer_email' => 'john@example.com',
            'buyer_name' => 'John Doe',
            'buyer_phone' => '255695123456',
            'amount' => 10000,
            'currency' => 'TZS',
            'gateway' => 'mobilipa',
            'payment_status' => 'PENDING',
            'order_id' => 'sp_696a7e8c101e3',
        ]);

        Mail::fake();

        Http::fake([
            'https://api.mobilipa.store/v1/payment/status' => Http::response([
                'status' => 'success',
                'message' => 'Order status retrieved successfully!',
                'data' => [
                    'order_id' => 'sp_696a7e8c101e3',
                    'payment_status' => 'CANCELLED',
                    'transid' => '807399829307',
                    'reference' => '1540671137',
                    'amount' => '10000',
                ],
            ]),
        ]);

        $response = $this->postJson(route('payments.check-status'), [
            'transaction_id' => $transaction->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('payment_status', 'CANCELLED');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'payment_status' => 'CANCELLED',
        ]);

        Mail::assertNothingSent();
    }

    public function test_it_rejects_mobilipa_order_when_sub_account_is_inactive(): void
    {
        $account = MobilipaAccount::create([
            'name' => 'Inactive Sub',
            'api_key' => 'inactive-sub-key',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => false,
        ]);

        PaymentGateway::create([
            'name' => 'mobilipa',
            'display_name' => 'Mobilipa',
            'api_key' => 'main-gateway-key',
            'base_url' => 'https://api.mobilipa.store',
            'is_active' => true,
            'description' => 'Mobilipa',
        ]);

        $page = Page::create([
            'title' => 'Mobilipa Inactive',
            'slug' => 'mobilipa-inactive',
            'template' => 'template1',
            'price' => 1000,
            'payment_gateway' => 'mobilipa',
            'is_active' => true,
            'mobilipa_account_id' => $account->id,
        ]);

        Http::fake();

        $response = $this->postJson(route('payments.create-order'), [
            'page_id' => $page->id,
            'buyer_phone' => '0711111111',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', 'error');
        $response->assertJsonPath('message', 'Mobilipa sub-account is not found or inactive.');

        $this->assertDatabaseMissing('transactions', [
            'gateway' => 'mobilipa',
            'mobilipa_account_id' => $account->id,
        ]);
    }
}
