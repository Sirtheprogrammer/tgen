<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageVideoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Session::start();
        session(['admin_authenticated' => true]);
    }

    public function test_store_custom_page_with_video_upload(): void
    {
        $video = UploadedFile::fake()->create('test-video.mp4', 50000, 'video/mp4');

        $response = $this->post('/pages', [
            'title' => 'Custom Video Page',
            'template' => 'custom',
            'video' => $video,
            'is_active' => true,
        ]);

        $response->assertRedirect('/pages');
        $this->assertDatabaseHas('pages', [
            'title' => 'Custom Video Page',
            'template' => 'custom',
        ]);

        $page = Page::where('title', 'Custom Video Page')->first();
        $this->assertNotNull($page->video_path);
        Storage::disk('public')->assertExists($page->video_path);
    }

    public function test_update_custom_page_with_new_video(): void
    {
        $originalVideo = UploadedFile::fake()->create('original.mp4', 10000, 'video/mp4');
        $originalPath = $originalVideo->store('videos', 'public');

        $page = Page::create([
            'title' => 'Edit Video Page',
            'slug' => 'edit-video-page',
            'template' => 'custom',
            'video_path' => $originalPath,
            'is_active' => true,
        ]);

        $newVideo = UploadedFile::fake()->create('new-video.mp4', 20000, 'video/mp4');

        $response = $this->put("/pages/{$page->slug}", [
            'title' => 'Edit Video Page Updated',
            'video' => $newVideo,
        ]);

        $response->assertRedirect('/pages');

        $page->refresh();
        $this->assertEquals('Edit Video Page Updated', $page->title);
        $this->assertNotEquals($originalPath, $page->video_path);
        Storage::disk('public')->assertExists($page->video_path);
        Storage::disk('public')->assertMissing($originalPath);
    }

    public function test_update_custom_page_without_replacing_video(): void
    {
        $video = UploadedFile::fake()->create('keep.mp4', 10000, 'video/mp4');
        $videoPath = $video->store('videos', 'public');

        $page = Page::create([
            'title' => 'Keep Video Page',
            'slug' => 'keep-video-page',
            'template' => 'custom',
            'video_path' => $videoPath,
            'is_active' => true,
        ]);

        $response = $this->put("/pages/{$page->slug}", [
            'title' => 'Keep Video Page Renamed',
        ]);

        $response->assertRedirect('/pages');

        $page->refresh();
        $this->assertEquals('Keep Video Page Renamed', $page->title);
        $this->assertEquals($videoPath, $page->video_path);
        Storage::disk('public')->assertExists($videoPath);
    }

    public function test_store_rejects_non_video_file(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->post('/pages', [
            'title' => 'Invalid Video Page',
            'template' => 'custom',
            'video' => $file,
        ]);

        $response->assertSessionHasErrors('video');
        $this->assertDatabaseMissing('pages', ['title' => 'Invalid Video Page']);
    }

    public function test_store_rejects_oversized_video(): void
    {
        // 513000 KB = ~501MB, exceeds the 512000 KB (500MB) limit
        $video = UploadedFile::fake()->create('huge.mp4', 513000, 'video/mp4');

        $response = $this->post('/pages', [
            'title' => 'Oversized Video Page',
            'template' => 'custom',
            'video' => $video,
        ]);

        $response->assertSessionHasErrors('video');
        $this->assertDatabaseMissing('pages', ['title' => 'Oversized Video Page']);
    }

    public function test_store_accepts_large_but_within_limit_video(): void
    {
        // 400MB — well within the 500MB limit
        $video = UploadedFile::fake()->create('large.mp4', 409600, 'video/mp4');

        $response = $this->post('/pages', [
            'title' => 'Large Valid Video Page',
            'template' => 'custom',
            'video' => $video,
            'is_active' => true,
        ]);

        $response->assertRedirect('/pages');
        $this->assertDatabaseHas('pages', ['title' => 'Large Valid Video Page']);
    }
}
