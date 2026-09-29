<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Models\User;
use App\Support\JsonLd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pull the JSON-LD block out of the rendered page and decode it.
     *
     * @return array<string, mixed>
     */
    private function graph(string $html): array
    {
        $matched = preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        $this->assertSame(1, $matched, 'Expected exactly one JSON-LD block on the page.');

        $decoded = json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true);

        $this->assertIsArray($decoded, 'The JSON-LD block was not valid JSON: '.$matches[1]);

        $this->assertSame('https://schema.org', $decoded['@context'] ?? null);
        $this->assertIsArray($decoded['@graph'] ?? null);

        return $decoded['@graph'];
    }

    /**
     * @return array<string, mixed>
     */
    private function nodeOfType(string $html, string $type): array
    {
        foreach ($this->graph($html) as $node) {
            if (($node['@type'] ?? null) === $type) {
                return $node;
            }
        }

        $this->fail("The JSON-LD graph has no {$type} node.");
    }

    public function test_json_ld_is_valid_json_and_not_leaked_php(): void
    {
        Profile::factory()->create(['full_name' => 'Ada Lovelace']);

        $person = $this->nodeOfType((string) $this->get('/')->assertOk()->getContent(), 'Person');

        $this->assertSame('Ada Lovelace', $person['name']);
    }

    public function test_json_ld_describes_the_profile(): void
    {
        Profile::factory()->create([
            'full_name' => 'Ada Lovelace',
            'title' => 'Lead Front-End Engineer',
            'email' => 'ada@example.com',
            'location' => 'London, UK',
        ]);

        $person = $this->nodeOfType((string) $this->get('/')->assertOk()->getContent(), 'Person');

        $this->assertSame('Lead Front-End Engineer', $person['jobTitle']);
        $this->assertSame('mailto:ada@example.com', $person['email']);
        $this->assertSame('PostalAddress', $person['address']['@type']);
        $this->assertSame('London, UK', $person['address']['addressLocality']);
        $this->assertSame(route('home'), $person['url']);
    }

    public function test_json_ld_same_as_lists_only_public_profile_urls(): void
    {
        Profile::factory()->create();

        SocialLink::factory()->forPlatform('github', 'https://github.com/ada')->create();
        SocialLink::factory()->forPlatform('email', 'ada@example.com')->create();
        SocialLink::factory()->forPlatform('dribbble')->withoutUrl()->create();
        SocialLink::factory()->forPlatform('x', 'https://x.com/ada')->hidden()->create();

        $person = $this->nodeOfType((string) $this->get('/')->assertOk()->getContent(), 'Person');

        $this->assertSame(['https://github.com/ada'], $person['sameAs']);
    }

    /**
     * A value containing markup must not be able to close the script element.
     */
    public function test_json_ld_escapes_markup_in_profile_values(): void
    {
        Profile::factory()->create([
            'full_name' => '</script><script>alert(1)</script>',
        ]);

        $html = (string) $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('</script><script>alert(1)</script>', $html);

        $this->assertSame('</script><script>alert(1)</script>', $this->nodeOfType($html, 'Person')['name']);
    }

    public function test_the_showcase_projects_are_described_as_structured_data(): void
    {
        Profile::factory()->create();

        $category = ProjectCategory::factory()->create();

        Project::factory()->for($category, 'category')->create([
            'title' => 'Signal Dashboard',
            'short_description' => 'A dashboard.',
        ]);
        Project::factory()->draft()->for($category, 'category')->create(['title' => 'Unfinished Dashboard']);

        $list = $this->nodeOfType((string) $this->get('/')->assertOk()->getContent(), 'ItemList');

        $names = array_map(
            fn (array $entry): string => $entry['item']['name'],
            $list['itemListElement'],
        );

        $this->assertSame(['Signal Dashboard'], $names);
        $this->assertSame(1, $list['itemListElement'][0]['position']);
    }

    public function test_no_item_list_is_emitted_when_there_is_no_work(): void
    {
        Profile::factory()->create();

        $types = array_map(
            fn (array $node): string => (string) ($node['@type'] ?? ''),
            $this->graph((string) $this->get('/')->assertOk()->getContent()),
        );

        $this->assertSame(['Person'], $types);
    }

    public function test_the_page_title_comes_from_the_seo_setting(): void
    {
        Profile::factory()->create();

        Setting::put('seo.title', 'Ada Lovelace - Portfolio');

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Ada Lovelace - Portfolio</title>', escape: false);
    }

    public function test_the_meta_description_comes_from_the_seo_setting(): void
    {
        Profile::factory()->create();

        Setting::put('seo.description', 'Building calm, fast interfaces.');

        $this->get('/')
            ->assertOk()
            ->assertSee('Building calm, fast interfaces.');
    }

    public function test_the_canonical_url_points_at_the_home_page(): void
    {
        Profile::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('rel="canonical"', escape: false)
            ->assertSee(route('home'), escape: false);
    }

    public function test_open_graph_metadata_is_rendered(): void
    {
        Profile::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('property="og:title"', escape: false)
            ->assertSee('property="og:description"', escape: false)
            ->assertSee('property="og:type"', escape: false)
            ->assertSee('property="og:url"', escape: false);
    }

    public function test_the_public_page_is_indexable_and_the_admin_is_not(): void
    {
        Profile::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('name="robots" content="index, follow"', escape: false);

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', escape: false);
    }

    public function test_json_ld_builder_returns_an_empty_object_when_encoding_fails(): void
    {
        // A malformed UTF-8 sequence is the one input json_encode refuses.
        $profile = Profile::factory()->create();
        $profile->forceFill(['full_name' => "\xB1\x31"])->saveQuietly();

        $this->assertSame('{}', JsonLd::person($profile->fresh()));
        $this->assertSame('{}', JsonLd::graph($profile->fresh()));
    }
}
