<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'subject' => 'A project for you',
            'message' => 'I would like to talk about a Webflow build for our team.',
        ], $overrides);
    }

    public function test_valid_submission_stores_an_unread_message_and_redirects_back(): void
    {
        Profile::factory()->create();

        $response = $this->from('/contact')->post('/contact', $this->payload());

        $response->assertRedirect('/contact')->assertSessionHas('contact_sent', true);

        $message = ContactMessage::query()->sole();

        $this->assertSame('Grace Hopper', $message->name);
        $this->assertSame('grace@example.com', $message->email);
        $this->assertSame('A project for you', $message->subject);
        $this->assertFalse($message->is_read);
        $this->assertNotNull($message->ip_address);
    }

    public function test_success_message_comes_from_settings(): void
    {
        Profile::factory()->create();
        Setting::put('contact.success', 'Got it, I will reply shortly.');

        $this->from('/contact')
            ->post('/contact', $this->payload())
            ->assertSessionHas('status', 'Got it, I will reply shortly.');
    }

    public function test_a_filled_honeypot_field_is_rejected(): void
    {
        Profile::factory()->create();

        $this->from('/contact')
            ->post('/contact', $this->payload(['website' => 'https://spam.example.com']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::query()->count());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function invalidPayloads(): array
    {
        return [
            'missing name' => [['name' => '']],
            'missing email' => [['email' => '']],
            'malformed email' => [['email' => 'not-an-email']],
            'message too short' => [['message' => 'hi']],
            'missing message' => [['message' => '']],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_submission_is_rejected_with_field_errors(array $overrides): void
    {
        Profile::factory()->create();

        $response = $this->from('/contact')->post('/contact', $this->payload($overrides));

        $response->assertSessionHasErrors(array_keys($overrides));

        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_submission_is_rate_limited_after_five_attempts_in_a_minute(): void
    {
        Profile::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/contact')->post('/contact', $this->payload());
        }

        $this->from('/contact')
            ->post('/contact', $this->payload())
            ->assertStatus(429);

        $this->assertSame(5, ContactMessage::query()->count());
    }

    public function test_submitted_content_is_escaped_when_rendered_in_the_admin_inbox(): void
    {
        Profile::factory()->create();

        $this->from('/contact')->post('/contact', $this->payload([
            'name' => "O'Reilly <script>alert('xss')</script>",
        ]));

        $message = ContactMessage::query()->sole();

        $this->actingAs(User::factory()->create());

        $this->get("/admin/messages/{$message->id}")
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;', escape: false)
            ->assertDontSee("<script>alert('xss')</script>", escape: false);
    }
}
