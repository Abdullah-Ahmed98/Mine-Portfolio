<?php

namespace App\Services;

use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\LatestWorkItem;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Single read entry point for everything the public site renders, so controllers
 * and views never assemble portfolio data by hand.
 */
class PortfolioService
{
    private ?Profile $profile = null;

    public function profile(): Profile
    {
        if ($this->profile === null) {
            $this->profile = Profile::current()->load('highlights');
        }

        return $this->profile;
    }

    /**
     * Only links that are both enabled and actually point somewhere.
     *
     * @return Collection<int, SocialLink>
     */
    public function socialLinks(): Collection
    {
        return SocialLink::query()
            ->where('is_visible', true)
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->orderBy('sort_order')
            ->get();
    }

    public function socialLink(string $platform): ?SocialLink
    {
        return $this->socialLinks()->firstWhere('platform', $platform);
    }

    /**
     * Skill categories that still have at least one visible skill.
     *
     * @return BaseCollection<int, SkillCategory>
     */
    public function skillGroups(): BaseCollection
    {
        return SkillCategory::query()
            ->whereHas('skills', fn ($query) => $query->where('is_visible', true))
            ->with(['skills' => fn ($query) => $query->where('is_visible', true)])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<int, Experience>
     */
    public function experiences(): Collection
    {
        return Experience::query()->ordered()->get();
    }

    /**
     * @return Collection<int, EducationEntry>
     */
    public function education(): Collection
    {
        return EducationEntry::query()->orderBy('sort_order')->get();
    }

    /**
     * The showcase sections, in the order the admin arranged them.
     *
     * Every category that still has a published project gets its own section on
     * the single-page site, so adding a category in the CMS adds a section.
     * Images are eager loaded because the cards list each project's gallery.
     *
     * @return BaseCollection<int, ProjectCategory>
     */
    public function showcaseSections(): BaseCollection
    {
        return ProjectCategory::query()
            ->whereHas('projects', fn ($query) => $query->published())
            ->with(['projects' => fn ($query) => $query->published()->with('images')])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * The "Latest work" strip, in the order the admin arranged it.
     *
     * Only visible items are read, and the section is skipped entirely when this
     * comes back empty, so an item switched off in the CMS leaves the front page
     * without a trace. Images are eager loaded for the same reason as the
     * showcase cards.
     *
     * @return Collection<int, LatestWorkItem>
     */
    public function latestWork(): Collection
    {
        return LatestWorkItem::query()
            ->visible()
            ->with(['category', 'images'])
            ->ordered()
            ->get();
    }
}
