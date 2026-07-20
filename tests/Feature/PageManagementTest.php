<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    public function test_page_index_generates_admin_actions_with_the_page_id_when_slug_is_missing(): void
    {
        $page = new Page;
        $page->setRawAttributes([
            'id' => 42,
            'title' => 'Legacy Page',
            'slug' => null,
            'template' => 'template1',
            'price' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], true);

        $html = view('dashboard.pages.index', [
            'pages' => new Collection([$page]),
        ])->render();

        $this->assertStringContainsString('/pages/42/edit', $html);
        $this->assertStringContainsString('action="http://localhost/pages/42/toggle"', $html);
        $this->assertStringContainsString('action="http://localhost/pages/42"', $html);
    }
}
