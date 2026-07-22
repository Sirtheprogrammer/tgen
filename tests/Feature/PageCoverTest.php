<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageCoverTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateAdmin(): void
    {
        session(['admin_authenticated' => true]);
    }

    private function createPage(array $attributes = []): Page
    {
        return Page::create(array_merge([
            'title' => 'My Test Page',
            'slug' => 'my-test-page',
            'template' => 'template1',
            'price' => 1000,
            'is_active' => true,
        ], $attributes));
    }

    public function test_cover_page_is_shown_at_page_url_when_cover_images_exist(): void
    {
        $page = $this->createPage([
            'cover_images' => ['covers/first.jpg', 'covers/second.jpg'],
        ]);

        $response = $this->get('/'.$page->slug);

        $response->assertOk();
        $response->assertSee('storage/covers/first.jpg');
        $response->assertSee('storage/covers/second.jpg');
        $response->assertSee('Watch More');
        $response->assertSee('/'.$page->slug.'/watch');
    }

    public function test_page_without_cover_images_redirects_to_watch_page(): void
    {
        $page = $this->createPage();

        $response = $this->get('/'.$page->slug);

        $response->assertRedirect(route('page.show', ['page' => $page->slug]));
    }

    public function test_watch_page_serves_the_template(): void
    {
        $page = $this->createPage([
            'cover_images' => ['covers/first.jpg'],
        ]);

        $response = $this->get('/'.$page->slug.'/watch');

        $response->assertOk();
    }

    public function test_cover_page_returns_404_when_page_is_inactive(): void
    {
        $page = $this->createPage([
            'is_active' => false,
            'cover_images' => ['covers/first.jpg'],
        ]);

        $this->get('/'.$page->slug)->assertNotFound();
        $this->get('/'.$page->slug.'/watch')->assertNotFound();
    }

    public function test_admin_can_upload_cover_images_when_creating_a_page(): void
    {
        Storage::fake('public');
        $this->authenticateAdmin();

        $response = $this->post('/pages', [
            'title' => 'Covered Page',
            'template' => 'template1',
            'price' => 500,
            'cover_images' => [
                UploadedFile::fake()->image('cover-one.jpg'),
                UploadedFile::fake()->image('cover-two.png'),
            ],
        ]);

        $response->assertRedirect('/pages');

        $page = Page::where('slug', 'covered-page')->firstOrFail();

        $this->assertCount(2, $page->cover_images);

        foreach ($page->cover_images as $coverImage) {
            Storage::disk('public')->assertExists($coverImage);
        }
    }

    public function test_cover_upload_is_rejected_when_more_than_four_images_are_sent(): void
    {
        Storage::fake('public');
        $this->authenticateAdmin();

        $response = $this->post('/pages', [
            'title' => 'Too Many Covers',
            'template' => 'template1',
            'cover_images' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
                UploadedFile::fake()->image('three.jpg'),
                UploadedFile::fake()->image('four.jpg'),
                UploadedFile::fake()->image('five.jpg'),
            ],
        ]);

        $response->assertSessionHasErrors('cover_images');
        $this->assertDatabaseMissing('pages', ['slug' => 'too-many-covers']);
    }

    public function test_admin_can_remove_and_add_cover_images_when_updating_a_page(): void
    {
        Storage::fake('public');
        $this->authenticateAdmin();

        Storage::disk('public')->put('covers/keep.jpg', 'keep');
        Storage::disk('public')->put('covers/remove.jpg', 'remove');

        $page = $this->createPage([
            'cover_images' => ['covers/keep.jpg', 'covers/remove.jpg'],
        ]);

        $response = $this->put('/pages/'.$page->id, [
            'title' => $page->title,
            'price' => $page->price,
            'remove_cover_images' => ['covers/remove.jpg'],
            'cover_images' => [
                UploadedFile::fake()->image('new-cover.jpg'),
            ],
        ]);

        $response->assertRedirect('/pages');

        $page->refresh();

        $this->assertCount(2, $page->cover_images);
        $this->assertContains('covers/keep.jpg', $page->cover_images);
        $this->assertNotContains('covers/remove.jpg', $page->cover_images);

        Storage::disk('public')->assertExists('covers/keep.jpg');
        Storage::disk('public')->assertMissing('covers/remove.jpg');
    }

    public function test_deleting_a_page_removes_its_cover_images_from_storage(): void
    {
        Storage::fake('public');
        $this->authenticateAdmin();

        Storage::disk('public')->put('covers/to-delete.jpg', 'delete-me');

        $page = $this->createPage([
            'cover_images' => ['covers/to-delete.jpg'],
        ]);

        $this->delete('/pages/'.$page->id)->assertRedirect('/pages');

        Storage::disk('public')->assertMissing('covers/to-delete.jpg');
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }
}
