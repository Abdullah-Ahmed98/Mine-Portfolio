<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->actingAs(User::factory()->create());
    }

    // --- profile photo ----------------------------------------------------

    public function test_uploading_a_profile_photo_stores_it_with_responsive_variants(): void
    {
        $profile = Profile::factory()->create();

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'profile_image' => UploadedFile::fake()->image('portrait.jpg', 1200, 1600),
        ])->assertSessionHasNoErrors();

        $path = $profile->fresh()->profile_image;

        $this->assertNotNull($path);
        $this->assertStringStartsWith('portfolio/profile/', $path);

        $disk = Storage::disk('public');

        $this->assertTrue($disk->exists($path));

        // The master is capped at 2000px wide, so a 1200px upload is untouched.
        [$width, $height] = getimagesizefromstring($disk->get($path));
        $this->assertSame(1200, $width);
        $this->assertSame(1600, $height);

        foreach ([480, 960] as $expected) {
            $variant = dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME)."@{$expected}.webp";
            $this->assertTrue($disk->exists($variant), "Missing variant {$variant}.");
            $this->assertSame($expected, imagesx(imagecreatefromstring($disk->get($variant))));
        }

        // 1600px is wider than the source, so it is never generated.
        $this->assertFalse(
            $disk->exists(dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME).'@1600.webp'),
        );
    }

    public function test_replacing_a_profile_photo_deletes_the_previous_files(): void
    {
        $profile = Profile::factory()->create();

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'profile_image' => UploadedFile::fake()->image('first.jpg', 1200, 1200),
        ])->assertSessionHasNoErrors();

        $first = $profile->fresh()->profile_image;

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'profile_image' => UploadedFile::fake()->image('second.jpg', 1200, 1200),
        ])->assertSessionHasNoErrors();

        $second = $profile->fresh()->profile_image;

        $this->assertNotSame($first, $second);
        $this->assertFalse(Storage::disk('public')->exists($first));
        $this->assertFalse(
            Storage::disk('public')->exists(dirname($first).'/'.pathinfo($first, PATHINFO_FILENAME).'@960.webp'),
        );
        $this->assertTrue(Storage::disk('public')->exists($second));
    }

    public function test_removing_a_profile_photo_clears_the_column_and_the_disk(): void
    {
        $profile = Profile::factory()->create();

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'profile_image' => UploadedFile::fake()->image('portrait.jpg', 1200, 1200),
        ])->assertSessionHasNoErrors();

        $path = $profile->fresh()->profile_image;

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'remove_image' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull($profile->fresh()->profile_image);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_a_file_that_is_not_an_image_cannot_be_used_as_a_profile_photo(): void
    {
        $profile = Profile::factory()->create();

        $this->from('/admin/profile')
            ->put('/admin/profile', [
                'full_name' => $profile->full_name,
                'title' => $profile->title,
                'profile_image' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
            ])
            ->assertSessionHasErrors('profile_image');
    }

    public function test_the_uploaded_photo_is_re_encoded_so_metadata_is_dropped(): void
    {
        $profile = Profile::factory()->create();

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'profile_image' => UploadedFile::fake()->image('portrait.jpg', 1200, 1200),
        ])->assertSessionHasNoErrors();

        $contents = Storage::disk('public')->get($profile->fresh()->profile_image);

        $this->assertStringNotContainsString('exif', strtolower($contents));
        $this->assertStringNotContainsString('Adobe', $contents);
    }

    // --- resume -----------------------------------------------------------

    public function test_uploading_a_resume_stores_the_pdf_and_serves_it(): void
    {
        $profile = Profile::factory()->withoutCv()->create();

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'cv' => UploadedFile::fake()->create('resume.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $path = $profile->fresh()->cv_path;

        $this->assertNotNull($path);
        $this->assertTrue(Storage::disk('public')->exists($path));

        $this->get('/resume')->assertOk();
    }

    public function test_a_file_that_is_not_a_pdf_cannot_be_used_as_a_resume(): void
    {
        $profile = Profile::factory()->withoutCv()->create();

        $this->from('/admin/profile')
            ->put('/admin/profile', [
                'full_name' => $profile->full_name,
                'title' => $profile->title,
                'cv' => UploadedFile::fake()->image('resume.jpg', 100, 100),
            ])
            ->assertSessionHasErrors('cv');

        $this->assertNull($profile->fresh()->cv_path);
    }

    public function test_replacing_a_resume_deletes_the_previous_file(): void
    {
        $profile = Profile::factory()->withoutCv()->create();

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'cv' => UploadedFile::fake()->create('first.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $first = $profile->fresh()->cv_path;

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'cv' => UploadedFile::fake()->create('second.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertNotSame($first, $profile->fresh()->cv_path);
        $this->assertFalse(Storage::disk('public')->exists($first));
    }

    public function test_removing_a_resume_clears_the_column_and_the_disk(): void
    {
        $profile = Profile::factory()->withoutCv()->create();

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'cv' => UploadedFile::fake()->create('resume.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $path = $profile->fresh()->cv_path;

        $this->put('/admin/profile', [
            'full_name' => $profile->full_name,
            'title' => $profile->title,
            'remove_cv' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull($profile->fresh()->cv_path);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    // --- project cover and gallery ----------------------------------------

    public function test_uploading_a_project_cover_stores_it(): void
    {
        $project = Project::factory()->create();

        $this->put("/admin/projects/{$project->slug}", [
            'title' => $project->title,
            'featured_image' => UploadedFile::fake()->image('cover.png', 1400, 900),
        ])->assertSessionHasNoErrors();

        $path = $project->fresh()->featured_image;

        $this->assertNotNull($path);
        $this->assertStringEndsWith('.png', $path);
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    public function test_deleting_a_project_removes_its_cover_and_gallery_from_the_disk(): void
    {
        $project = Project::factory()->create();

        $this->put("/admin/projects/{$project->slug}", [
            'title' => $project->title,
            'featured_image' => UploadedFile::fake()->image('cover.jpg', 1200, 900),
        ])->assertSessionHasNoErrors();

        $cover = $project->fresh()->featured_image;

        $this->post("/admin/projects/{$project->slug}/images", [
            'images' => [
                UploadedFile::fake()->image('one.jpg', 1200, 900),
                UploadedFile::fake()->image('two.jpg', 1200, 900),
            ],
            'alt' => 'Project screenshots',
        ])->assertSessionHasNoErrors();

        $paths = $project->images()->pluck('path')->all();
        $this->assertCount(2, $paths);

        $this->delete("/admin/projects/{$project->slug}")->assertSessionHas('status');

        foreach ([...$paths, $cover] as $path) {
            $this->assertFalse(Storage::disk('public')->exists($path), "Expected {$path} to be deleted.");
        }
    }

    public function test_uploading_gallery_images_shares_the_alt_text_and_orders_them(): void
    {
        $project = Project::factory()->create();

        $this->post("/admin/projects/{$project->slug}/images", [
            'images' => [
                UploadedFile::fake()->image('one.jpg', 1200, 900),
                UploadedFile::fake()->image('two.jpg', 1200, 900),
                UploadedFile::fake()->image('three.jpg', 1200, 900),
            ],
            'alt' => 'The dashboard, empty',
        ])->assertSessionHasNoErrors();

        $images = $project->images()->orderBy('sort_order')->get();

        $this->assertCount(3, $images);
        $this->assertSame([0, 1, 2], $images->pluck('sort_order')->all());
        $this->assertSame(['The dashboard, empty'], $images->pluck('alt')->unique()->values()->all());
        $this->assertFalse($images->contains('is_cover', true));
    }

    public function test_a_gallery_upload_needs_at_least_one_image(): void
    {
        $project = Project::factory()->create();

        $this->from("/admin/projects/{$project->slug}/edit")
            ->post("/admin/projects/{$project->slug}/images", ['images' => []])
            ->assertSessionHasErrors('images');

        $this->assertSame(0, $project->images()->count());
    }

    public function test_a_gallery_rejects_a_non_image_file(): void
    {
        $project = Project::factory()->create();

        $this->from("/admin/projects/{$project->slug}/edit")
            ->post("/admin/projects/{$project->slug}/images", [
                'images' => [UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf')],
            ])
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, $project->images()->count());
    }

    public function test_promoting_a_gallery_image_to_cover_clears_the_others(): void
    {
        $project = Project::factory()->create();

        $first = ProjectImage::factory()->for($project)->cover()->create();
        $second = ProjectImage::factory()->for($project)->create();

        $this->put("/admin/projects/images/{$second->id}", ['is_cover' => '1'])
            ->assertSessionHas('status');

        $this->assertFalse($first->fresh()->is_cover);
        $this->assertTrue($second->fresh()->is_cover);
        $this->assertSame(1, $project->images()->where('is_cover', true)->count());
    }

    public function test_deleting_a_gallery_image_removes_its_files(): void
    {
        $project = Project::factory()->create();

        $path = $this->storeGalleryImage($project, 'portfolio/projects');

        $image = $project->images()->sole();

        $this->delete("/admin/projects/images/{$image->id}")->assertSessionHas('status');

        $this->assertSame(0, $project->images()->count());
        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertFalse(
            Storage::disk('public')->exists(dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME).'@480.webp'),
        );
    }

    // --- MediaService -----------------------------------------------------

    public function test_a_source_narrower_than_a_variant_width_is_not_upscaled(): void
    {
        $path = app(MediaService::class)->store(
            UploadedFile::fake()->image('small.jpg', 300, 200),
            'portfolio/profile',
        );

        $disk = Storage::disk('public');

        [$width] = getimagesizefromstring($disk->get($path));
        $this->assertSame(300, $width);

        foreach (MediaService::WIDTHS as $variant) {
            $this->assertFalse($disk->exists(dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME)."@{$variant}.webp"));
        }
    }

    public function test_deleting_a_missing_path_is_a_no_op(): void
    {
        app(MediaService::class)->delete(null);
        app(MediaService::class)->delete('');

        $this->assertTrue(true);
    }

    public function test_srcset_lists_only_the_variants_that_exist(): void
    {
        $service = app(MediaService::class);

        $this->assertNull($service->srcset(null));

        $path = $service->store(UploadedFile::fake()->image('wide.jpg', 2000, 1000), 'portfolio/projects');

        $srcset = (string) $service->srcset($path);

        $this->assertStringContainsString('480w', $srcset);
        $this->assertStringContainsString('960w', $srcset);
        $this->assertStringContainsString('1600w', $srcset);
        $this->assertStringEndsWith($path, $srcset);
    }

    private function storeGalleryImage(Project $project, string $directory): string
    {
        $this->post("/admin/projects/{$project->slug}/images", [
            'images' => [UploadedFile::fake()->image('one.jpg', 1200, 900)],
        ])->assertSessionHasNoErrors();

        return $project->images()->sole()->path;
    }
}
