<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Support\Paragraphs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The blank line convention every long-text field in the CMS is read back with.
 */
class ParagraphsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_blank_lines_separate_paragraphs(): void
    {
        $this->assertSame(
            ['One.', 'Two.', 'Three.'],
            Paragraphs::split("One.\n\nTwo.\n\nThree.")
        );
    }

    /**
     * Line endings differ by where the copy was written, and this is Windows.
     */
    #[Test]
    public function test_blank_lines_separate_whatever_the_line_ending(): void
    {
        $this->assertSame(['One.', 'Two.'], Paragraphs::split("One.\r\n\r\nTwo."));
        $this->assertSame(['One.', 'Two.'], Paragraphs::split("One.\n\nTwo."));
        $this->assertSame(['One.', 'Two.'], Paragraphs::split("One.\n\n\n\nTwo."));
    }

    /**
     * A single line break is not a paragraph break, and an author may rely on
     * that to put a title and its institution on two lines.
     */
    #[Test]
    public function test_a_single_line_break_stays_inside_the_paragraph(): void
    {
        $this->assertSame(
            ['One line.'."\n".'Another line.'],
            Paragraphs::split("One line.\nAnother line.")
        );
    }

    #[Test]
    public function test_blank_lines_at_the_edges_are_dropped(): void
    {
        $this->assertSame(['One.'], Paragraphs::split("\n\n  \n  One.  \n\n\n"));
    }

    #[Test]
    public function test_nothing_in_nothing_out(): void
    {
        $this->assertSame([], Paragraphs::split(null));
        $this->assertSame([], Paragraphs::split(''));
        $this->assertSame([], Paragraphs::split("   \n\n  \n "));
    }

    /**
     * The profile has always read its long description this way, and moving the
     * logic must not have changed a single paragraph.
     */
    #[Test]
    public function test_the_profile_reads_its_description_the_same_way(): void
    {
        $profile = Profile::factory()->create([
            'full_description' => "One.\n\nTwo.\nThree.",
        ]);

        $this->assertSame(['One.', "Two.\nThree."], $profile->descriptionParagraphs());
    }
}
