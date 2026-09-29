<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\Project;
use App\Models\SocialLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the schema.org JSON-LD graph rendered in the public layout.
 *
 * The payload lives in PHP rather than in the template because Blade's
 * directive matcher treats the literal string '@context' as one of its own
 * directives and compiles it away, leaving raw PHP in the output.
 */
final class JsonLd
{
    private const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP;

    /**
     * The Person node describing the site owner, on its own.
     *
     * @param  Collection<int, SocialLink>  $socialLinks
     */
    public static function person(Profile $profile, ?Collection $socialLinks = null): string
    {
        return self::encode([
            '@context' => 'https://schema.org',
            ...self::personNode($profile, $socialLinks),
        ]);
    }

    /**
     * A graph of the Person node plus an ItemList of the showcase projects.
     *
     * The site is a single page, so each project has no URL of its own to be
     * indexed under. Listing them here is the only machine-readable way to
     * describe the work, and it keeps the whole payload in one script element.
     *
     * @param  Collection<int, SocialLink>  $socialLinks
     * @param  Collection<int, Project>  $projects
     */
    public static function graph(Profile $profile, ?Collection $socialLinks = null, ?Collection $projects = null): string
    {
        $nodes = [self::personNode($profile, $socialLinks)];

        $items = ($projects ?? collect())
            ->map(fn (Project $project): array => array_filter([
                '@type' => 'CreativeWork',
                'name' => $project->title,
                'description' => Str::limit((string) $project->short_description, 300) ?: null,
                'url' => $project->project_url ?: null,
                'dateCreated' => $project->completed_at?->toAtomString(),
            ], fn (mixed $value): bool => $value !== null))
            ->values()
            ->all();

        if ($items !== []) {
            $nodes[] = [
                '@type' => 'ItemList',
                'itemListElement' => array_map(
                    fn (array $item, int $index): array => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'item' => $item,
                    ],
                    $items,
                    array_keys($items),
                ),
            ];
        }

        return self::encode([
            '@context' => 'https://schema.org',
            '@graph' => $nodes,
        ]);
    }

    /**
     * @param  Collection<int, SocialLink>|null  $socialLinks
     * @return array<string, mixed>
     */
    private static function personNode(Profile $profile, ?Collection $socialLinks = null): array
    {
        return [
            '@type' => 'Person',
            'name' => $profile->full_name,
            'jobTitle' => $profile->title,
            'email' => $profile->email ? 'mailto:'.$profile->email : null,
            'url' => route('home'),
            'address' => $profile->location ? [
                '@type' => 'PostalAddress',
                'addressLocality' => $profile->location,
            ] : null,
            'image' => $profile->profileImageUrl() ? asset($profile->profileImageUrl()) : null,
            'sameAs' => ($socialLinks ?? collect())
                ->filter(fn (SocialLink $link): bool => $link->isRenderable()
                    && ! str_starts_with((string) $link->resolvedUrl(), 'mailto:'))
                ->map(fn (SocialLink $link): ?string => $link->resolvedUrl())
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function encode(array $payload): string
    {
        $json = json_encode($payload, self::FLAGS);

        return $json === false ? '{}' : $json;
    }
}
