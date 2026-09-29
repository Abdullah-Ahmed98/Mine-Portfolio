<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\SocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_filled_link_renders_an_anchor_with_its_url(): void
    {
        Profile::factory()->create();

        SocialLink::factory()->forPlatform('linkedin', 'https://linkedin.com/in/ada')->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('https://linkedin.com/in/ada', escape: false);
    }

    public function test_a_link_without_a_url_renders_no_icon(): void
    {
        Profile::factory()->create();

        SocialLink::factory()->forPlatform('github')->withoutUrl()->create();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('aria-label="GitHub"')
            ->assertDontSee('<ul class="socials">');
    }

    public function test_a_hidden_link_renders_no_icon(): void
    {
        Profile::factory()->create();

        SocialLink::factory()->forPlatform('behance', 'https://behance.net/ada')->hidden()->create();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('aria-label="Behance"')
            ->assertDontSee('<ul class="socials">');
    }

    public function test_email_platform_prefixes_the_mailto_scheme(): void
    {
        $link = SocialLink::factory()->forPlatform('email', 'ada@example.com')->create();

        $this->assertSame('mailto:ada@example.com', $link->resolvedUrl());
    }

    public function test_email_platform_keeps_an_existing_mailto_prefix(): void
    {
        $link = SocialLink::factory()->forPlatform('email', 'mailto:ada@example.com')->create();

        $this->assertSame('mailto:ada@example.com', $link->resolvedUrl());
    }

    public function test_whatsapp_builds_a_wa_me_url_from_a_bare_number(): void
    {
        $link = SocialLink::factory()->forPlatform('whatsapp', '+92 300 1234567')->create();

        $this->assertSame('https://wa.me/923001234567', $link->resolvedUrl());
    }

    public function test_whatsapp_keeps_a_wa_me_url_as_is(): void
    {
        $link = SocialLink::factory()->forPlatform('whatsapp', 'https://wa.me/923001234567?text=Hi')->create();

        $this->assertSame('https://wa.me/923001234567?text=Hi', $link->resolvedUrl());
    }

    public function test_a_link_without_a_url_resolves_to_no_url(): void
    {
        $link = SocialLink::factory()->forPlatform('dribbble')->withoutUrl()->create();

        $this->assertNull($link->resolvedUrl());
        $this->assertFalse($link->hasDestination());
    }

    public function test_is_renderable_requires_both_visibility_and_a_destination(): void
    {
        $this->assertFalse(SocialLink::factory()->hidden()->make()->isRenderable());
        $this->assertFalse(SocialLink::factory()->withoutUrl()->make()->isRenderable());
        $this->assertTrue(SocialLink::factory()->make()->isRenderable());
    }

    public function test_links_render_in_their_sort_order(): void
    {
        Profile::factory()->create();

        SocialLink::factory()->forPlatform('github', 'https://github.com/ada')->create(['sort_order' => 2]);
        SocialLink::factory()->forPlatform('linkedin', 'https://linkedin.com/in/ada')->create(['sort_order' => 1]);
        SocialLink::factory()->forPlatform('behance', 'https://behance.net/ada')->create(['sort_order' => 0]);

        $content = (string) $this->get('/')->assertOk()->getContent();

        $this->assertTrue(
            strpos($content, 'behance.net') < strpos($content, 'linkedin.com'),
            'Expected Behance to render before LinkedIn.',
        );
        $this->assertTrue(
            strpos($content, 'linkedin.com') < strpos($content, 'github.com'),
            'Expected LinkedIn to render before GitHub.',
        );
    }
}
