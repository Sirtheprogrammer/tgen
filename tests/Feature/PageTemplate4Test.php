<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTemplate4Test extends TestCase
{
    use RefreshDatabase;

    private function authenticateAdmin(): void
    {
        session(['admin_authenticated' => true]);
    }

    private function createFastLipaGateway(): void
    {
        PaymentGateway::create([
            'name' => 'fastlipa',
            'display_name' => 'FastLipa',
            'api_key' => 'fastlipa-test-token',
            'base_url' => 'https://api.fastlipa.com/api',
            'is_active' => true,
            'description' => 'FastLipa',
        ]);
    }

    private function createPage(array $attributes = []): Page
    {
        return Page::create(array_merge([
            'title' => 'WhatsApp Group Page',
            'slug' => 'whatsapp-group-page',
            'template' => 'template4',
            'price' => 2000,
            'payment_gateway' => 'fastlipa',
            'is_active' => true,
        ], $attributes));
    }

    public function test_admin_can_create_page_with_template4(): void
    {
        $this->createFastLipaGateway();
        $this->authenticateAdmin();

        $response = $this->post('/pages', [
            'title' => 'WhatsApp Group Page',
            'template' => 'template4',
            'price' => 2000,
            'payment_gateway' => 'fastlipa',
            'is_active' => true,
        ]);

        $response->assertRedirect('/pages');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'title' => 'WhatsApp Group Page',
            'template' => 'template4',
            'price' => 2000,
            'payment_gateway' => 'fastlipa',
            'is_active' => true,
        ]);
    }

    public function test_template4_page_without_cover_images_redirects_to_the_watch_url(): void
    {
        $page = $this->createPage();

        $response = $this->get('/'.$page->slug);

        $response->assertRedirect('/'.$page->slug.'/watch');
    }

    public function test_public_template4_page_is_served(): void
    {
        $this->createFastLipaGateway();

        $page = $this->createPage();

        $response = $this->get('/'.$page->slug.'/watch');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=utf-8');
        $response->assertSee('WhatsApp Group');
        $response->assertSee('/template-assets/template4/css/page2.css');
        $response->assertSee('/template-assets/template4/js/chat.js');
        $response->assertSee('window.pageId = '.$page->id);
        $response->assertSee('window.pagePrice = 2000');
        $response->assertSee('/api/payments/create-order');
        $response->assertSee('/api/payments/check-status');
    }

    public function test_template4_assets_exist_on_disk(): void
    {
        $assets = [
            'css/page2.css',
            'js/chat.js',
            'js/jquery-3.6.0.min.js',
            'images/payment.png',
            'images/pic1.jpg',
            'images/pic2.jpg',
            'images/profilepic.png',
            'images/placeholder-40.png',
            'images/placeholder-70.png',
        ];

        foreach ($assets as $asset) {
            $this->assertFileExists(public_path('template-assets/template4/'.$asset));
        }

        $this->assertFileExists(public_path('images/template4.png'));
        $this->assertFileExists(resource_path('views/templates/template4.html'));
    }

    public function test_inactive_template4_page_returns_404(): void
    {
        $page = $this->createPage([
            'title' => 'Inactive WhatsApp Page',
            'slug' => 'inactive-whatsapp-page',
            'is_active' => false,
        ]);

        $this->get('/'.$page->slug)->assertNotFound();
        $this->get('/'.$page->slug.'/watch')->assertNotFound();
    }

    public function test_template4_appears_in_dashboard_template_listing(): void
    {
        $this->authenticateAdmin();

        $response = $this->get('/templates');

        $response->assertOk();
        $response->assertSee('/images/template4.png');
    }
}
