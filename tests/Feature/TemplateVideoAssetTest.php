<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemplateVideoAssetTest extends TestCase
{
    public function test_template1_renders_real_uhondo_video_sources(): void
    {
        $html = view('templates.template1')->render();

        $this->assertStringContainsString('https://uhondo.online/storage/videos/oGi67lmcZ9KSvyJh8JLjkMeImtB1VnhMOUfulIw4.mp4', $html);
        $this->assertStringContainsString('https://uhondo.online/storage/videos/xoZg0vhBXo6tfgwzLkGHt4QM8xAfquLt9E3HPwGc.mp4', $html);
    }

    public function test_template2_renders_real_uhondo_video_sources(): void
    {
        $html = view('templates.template2')->render();

        $this->assertStringContainsString('https://uhondo.online/storage/videos/oGi67lmcZ9KSvyJh8JLjkMeImtB1VnhMOUfulIw4.mp4', $html);
        $this->assertStringContainsString('https://uhondo.online/storage/videos/xoZg0vhBXo6tfgwzLkGHt4QM8xAfquLt9E3HPwGc.mp4', $html);
    }
}
