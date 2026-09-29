<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\ProfileHighlight;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every GET screen behind the auth middleware, as a guest would request it.
     *
     * @return array<int, array<int, string>>
     */
    public static function guardedScreens(): array
    {
        return array_map(
            fn (string $path): array => [$path],
            [
                '/admin',
                '/admin/profile',
                '/admin/social-links',
                '/admin/social-links/create',
                '/admin/highlights',
                '/admin/highlights/create',
                '/admin/skill-categories',
                '/admin/skill-categories/create',
                '/admin/skills',
                '/admin/skills/create',
                '/admin/experiences',
                '/admin/experiences/create',
                '/admin/project-categories',
                '/admin/project-categories/create',
                '/admin/projects',
                '/admin/projects/create',
                '/admin/education',
                '/admin/education/create',
                '/admin/settings',
                '/admin/messages',
            ],
        );
    }

    #[DataProvider('guardedScreens')]
    public function test_guest_is_redirected_to_the_admin_login(string $path): void
    {
        $this->get($path)
            ->assertRedirect(route('admin.login'));
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function guardedWrites(): array
    {
        return [
            ['/admin/reorder/projects/1', ['direction' => 'up']],
            ['/admin/experiences/1', ['_method' => 'DELETE']],
            ['/admin/settings', ['_method' => 'PUT', 'settings' => ['nav.home' => 'Nope']]],
            ['/admin/projects/1', ['_method' => 'DELETE']],
            ['/admin/messages/1', ['_method' => 'DELETE']],
        ];
    }

    #[DataProvider('guardedWrites')]
    public function test_guest_cannot_write_to_the_admin(string $path, array $payload): void
    {
        $this->post($path, $payload)->assertRedirect(route('admin.login'));
    }

    public function test_login_screen_is_reachable_by_a_guest(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Sign in');
    }

    public function test_valid_credentials_sign_the_user_in(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected_with_an_error(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'not-the-password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited_after_five_failures(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong']);
        }

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertStatus(429);

        $this->assertGuest();
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_signed_in_user_can_open_every_admin_screen(): void
    {
        $profile = Profile::current();
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $socialLink = SocialLink::factory()->create();
        $highlight = ProfileHighlight::factory()->for($profile, 'profile')->create();
        $skillCategory = SkillCategory::factory()->create();
        $skill = Skill::factory()->for($skillCategory, 'category')->create();
        $experience = Experience::factory()->create();
        $projectCategory = ProjectCategory::factory()->create();
        $educationEntry = EducationEntry::factory()->create();
        $message = ContactMessage::factory()->create();

        $screens = [
            '/admin',
            '/admin/profile',
            '/admin/social-links',
            "/admin/social-links/{$socialLink->id}/edit",
            '/admin/social-links/create',
            '/admin/highlights',
            "/admin/highlights/{$highlight->id}/edit",
            '/admin/highlights/create',
            '/admin/skill-categories',
            "/admin/skill-categories/{$skillCategory->id}/edit",
            '/admin/skill-categories/create',
            '/admin/skills',
            "/admin/skills/{$skill->id}/edit",
            '/admin/skills/create',
            '/admin/experiences',
            "/admin/experiences/{$experience->id}/edit",
            '/admin/experiences/create',
            '/admin/project-categories',
            "/admin/project-categories/{$projectCategory->id}/edit",
            '/admin/project-categories/create',
            '/admin/projects',
            "/admin/projects/{$project->slug}/edit",
            '/admin/projects/create',
            '/admin/education',
            "/admin/education/{$educationEntry->id}/edit",
            '/admin/education/create',
            '/admin/settings',
            '/admin/messages',
            "/admin/messages/{$message->id}",
        ];

        foreach ($screens as $screen) {
            $this->actingAs($user)
                ->get($screen)
                ->assertOk("Expected {$screen} to render.");
        }
    }

    public function test_admin_screens_are_not_indexed(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('noindex', escape: false);
    }

    public function test_reorder_rejects_an_unknown_direction(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->from('/admin/projects')
            ->post("/admin/reorder/projects/{$project->id}", ['direction' => 'sideways'])
            ->assertSessionHasErrors('direction');
    }

    public function test_reorder_rejects_an_unknown_type_at_the_route_level(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/reorder/users/1', ['direction' => 'up'])
            ->assertNotFound();
    }
}
