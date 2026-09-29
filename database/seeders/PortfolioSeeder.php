<?php

namespace Database\Seeders;

use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Services\CoverArtService;
use App\Services\MediaService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortfolioSeeder extends Seeder
{
    public function run(): void
    {
        // Start from a clean slate so re-seeding never leaves orphaned media
        // behind from a previous run.
        Storage::disk('public')->deleteDirectory('portfolio');

        $profile = $this->seedProfile();
        $this->seedHighlights($profile);
        $this->seedSocialLinks();
        $this->seedSkills();
        $this->seedExperiences();
        $this->seedEducation();
        $this->seedProjects();
        $this->seedSettings();

        Setting::flushCache();
    }

    private function seedProfile(): Profile
    {
        $media = app(MediaService::class);
        $disk = Storage::disk('public');

        $profile = Profile::current();

        // The portrait is re-encoded through GD, which strips EXIF and any
        // embedded payload while writing the responsive widths.
        $profileImagePath = $media->store(
            new UploadedFile(
                database_path('seeders/assets/profile-pic.jpeg'),
                'profile-pic.jpeg',
                'image/jpeg',
                null,
                true,
            ),
            'portfolio/profile',
        );

        // The name is needed before the row is filled in, because it names the
        // resume file. Profile::current() may have created a placeholder, so
        // the real name is defined here rather than read back from the model.
        $fullName = 'Abdullah Ahmed';

        $cvPath = 'portfolio/cv/'.Str::slug($fullName).'.pdf';

        $disk->put($cvPath, file_get_contents(database_path('seeders/assets/resume.pdf')));

        $profile->forceFill([
            'full_name' => $fullName,
            'title' => 'Team Manager, Webflow & UI/UX Developer',
            'role_short' => 'Team Manager · Webflow & UI/UX Developer',
            'short_intro' => 'Five years leading front-end design and development teams — currently running Germany-facing web projects at I Love Design GBR, from client alignment to hiring to quality control, based in Wah Cantt, Pakistan.',
            'full_description' => implode("\n\n", [
                'Abdullah is a Team Manager with 5+ years of experience leading front-end design and development teams, managing daily operations, and overseeing technical computing tasks. He currently leads at I Love Design GBR, a Germany-based company, where he\'s responsible for team performance, daily reporting to leadership, recruitment, and AI-driven workflow automation for German-market web projects — dealing directly with German-based clients and stakeholders to keep priorities aligned and delivery on time.',
                'His background combines hands-on Webflow and UI/UX expertise with proven people-management and process-oversight skills, giving him strong international work exposure across design, development, and operations.',
            ]),
            'email' => 'abdullahhahmed1234@gmail.com',
            'phone' => null,
            'location' => 'Wah Cantt, Pakistan',
            'years_experience' => '5+',
            'availability_text' => 'Available for new projects',
            'availability_status' => true,
            'profile_image' => $profileImagePath,
            'cv_path' => $cvPath,
        ])->save();

        return $profile;
    }

    private function seedHighlights(Profile $profile): void
    {
        $profile->highlights()->delete();

        foreach ([
            ['Focus', 'Leading Webflow & UI/UX delivery for German-market web projects.'],
            ['Where he works from', 'Wah Cantt, Pakistan — coordinating daily with teams and clients in Germany.'],
            ['Key strengths', 'Hiring & onboarding, QA oversight, AI-driven workflow automation, client communication.'],
        ] as $index => [$title, $text]) {
            $profile->highlights()->create([
                'title' => $title,
                'text' => $text,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedSocialLinks(): void
    {
        SocialLink::query()->delete();

        // Every platform the admin panel offers is seeded as a row. The ones with
        // no URL are left empty on purpose: an empty link never renders an icon.
        $links = [
            ['whatsapp', 'WhatsApp', null],
            ['linkedin', 'LinkedIn', 'https://linkedin.com/in/abdullah-ahmed-96a250144'],
            ['github', 'GitHub', null],
            ['behance', 'Behance', null],
            ['dribbble', 'Dribbble', null],
            ['email', 'Email', 'abdullahhahmed1234@gmail.com'],
        ];

        foreach ($links as $index => [$platform, $label, $url]) {
            SocialLink::create([
                'platform' => $platform,
                'label' => $label,
                'url' => $url,
                'icon' => $platform,
                'sort_order' => $index,
                'is_visible' => true,
            ]);
        }
    }

    private function seedSkills(): void
    {
        Skill::query()->delete();
        SkillCategory::query()->delete();

        $categories = [
            ['Design & Platforms', ['Webflow', 'UI/UX Design', 'Front-End App Design', 'WordPress', 'Shopify']],
            ['Development', ['Python', 'Java', 'SQL', 'CSS', 'Android']],
            ['AI & Automation', ['AI Tools & Automation Management', 'Machine Learning', 'Deep Learning & YOLO', 'Automation Workflows']],
            ['Leadership & Operations', ['Team Leadership & QA', 'Technical Computing Support']],
            ['Languages', ['English', 'Urdu']],
        ];

        foreach ($categories as $categoryIndex => [$name, $skills]) {
            $category = SkillCategory::create([
                'name' => $name,
                'slug' => SkillCategory::slugFor($name),
                'sort_order' => $categoryIndex,
            ]);

            foreach ($skills as $skillIndex => $skillName) {
                $category->skills()->create([
                    'name' => $skillName,
                    'level' => null,
                    'is_visible' => true,
                    'sort_order' => $skillIndex,
                ]);
            }
        }
    }

    private function seedExperiences(): void
    {
        Experience::query()->delete();

        $experiences = [
            [
                'company' => 'I Love Design GBR',
                'position' => 'Team Manager',
                'location' => 'Germany',
                'start_date' => '2025-01-01',
                'end_date' => null,
                'is_current' => true,
                'date_label' => '2025 – 2026',
                'description' => null,
                'responsibilities' => [
                    'Oversee daily operations of the front-end design and development team, including task allocation, progress tracking, and end-of-day reporting to leadership.',
                    'Deal directly with German-based clients and internal stakeholders to support project requirements and timely communication.',
                    'Manage deadline-driven web projects, tracking milestones through to final delivery.',
                    'Lead large, cross-functional teams across multiple departments.',
                    'Coordinate hiring: attending job fairs, screening candidates, and managing onboarding.',
                    'Oversee design and development of German-market websites on Webflow, ensuring UI/UX consistency and brand standards.',
                    'Monitor quality control by arranging and reviewing website testing before client handoff.',
                    'Drive adoption of AI-powered tools to streamline repetitive design/development tasks.',
                    'Support lead-generation and business-development initiatives.',
                ],
                'technologies' => ['Webflow', 'UI/UX', 'AI Tools'],
            ],
            [
                'company' => 'Kreativ Ally',
                'position' => 'Team Lead — Webflow & UI/UX Designer',
                'location' => 'Islamabad, Pakistan',
                'start_date' => '2021-01-01',
                'end_date' => '2025-01-01',
                'is_current' => false,
                'date_label' => '2021 – 2025',
                'description' => null,
                'responsibilities' => [
                    'Led and managed a team of front-end designers and developers, overseeing workflow and performance.',
                    'Represented the company at job fairs and recruitment events to source and onboard new hires.',
                    'Designed and built German-market websites and front-end applications on Webflow, with a strong UI/UX focus.',
                    'Built AI-based tools to automate repetitive design and development workflows.',
                    'Conducted QA checks and technical reviews across projects.',
                ],
                'technologies' => ['Webflow', 'UI/UX', 'AI Tools'],
            ],
            [
                'company' => 'Colorem Digital',
                'position' => 'WordPress Developer',
                'location' => 'Islamabad, Pakistan',
                'start_date' => '2020-08-01',
                'end_date' => '2022-01-01',
                'is_current' => false,
                'date_label' => 'Aug 2020 – 2022',
                'description' => null,
                'responsibilities' => [
                    'Developed business, corporate, and medical websites using WordPress.',
                    'Built and customized e-commerce storefronts for clients.',
                ],
                'technologies' => ['WordPress'],
            ],
            [
                'company' => 'Pakistan Ordnance Factory',
                'position' => 'Intern',
                'location' => 'Wah Cantt, Pakistan',
                'start_date' => '2019-06-01',
                'end_date' => '2019-07-01',
                'is_current' => false,
                'date_label' => '2019 · 1 month',
                'description' => null,
                'responsibilities' => [
                    'Gained foundational exposure to databases, networking, and inventory management systems.',
                ],
                'technologies' => ['SQL'],
            ],
        ];

        foreach ($experiences as $index => $experience) {
            Experience::create($experience + ['sort_order' => $index]);
        }
    }

    private function seedEducation(): void
    {
        EducationEntry::query()->delete();

        EducationEntry::create([
            'title' => 'B.S. in Computer Science',
            'institution' => 'University of Wah',
            'meta' => 'University of Wah',
            'description' => 'Undergraduate degree covering the coursework and academic projects listed above.',
            'start_date' => '2016-09-01',
            'end_date' => '2020-07-01',
            'sort_order' => 0,
        ]);

        EducationEntry::create([
            'title' => '1st Position',
            'institution' => 'University of Wah',
            'meta' => 'Speed Programming & Web Designing Competition',
            'description' => 'Placed first at University of Wah\'s speed programming and web designing competition.',
            'start_date' => null,
            'end_date' => null,
            'sort_order' => 1,
        ]);
    }

    private function seedProjects(): void
    {
        Project::query()->delete();
        ProjectCategory::query()->delete();

        $covers = app(CoverArtService::class);

        /*
         * Each category becomes a showcase section on the single page, rendered
         * in this order. Both are managed in the CMS: renaming, reordering,
         * adding or removing a category changes the site without a code change.
         */
        $categories = [
            [
                'name' => 'UI/UX Design',
                'description' => 'Interface work grounded in research — flows, wireframes, design systems and the front-end builds that prove them out.',
                'sort_order' => 0,
            ],
            [
                'name' => 'Development',
                'description' => 'Machine learning, web platforms and Android builds — the engineering behind the interfaces.',
                'sort_order' => 1,
            ],
        ];

        $categoryIds = [];

        foreach ($categories as $category) {
            $categoryIds[$category['name']] = ProjectCategory::create([
                'name' => $category['name'],
                'description' => $category['description'],
                'slug' => ProjectCategory::slugFor($category['name']),
                'sort_order' => $category['sort_order'],
            ])->id;
        }

        /*
         * PLACEHOLDER COPY. The four entries dated from his university and
         * volunteer work are his real projects. The three undated entries are
         * written to be plausible for his skill set and carry no client claims —
         * they exist so both showcase sections have something to show, and
         * should be replaced with real work from the admin.
         */
        $projects = [
            // ---------------------------------------------------------- UI/UX
            [
                'title' => 'Design System & Component Library',
                'category' => 'UI/UX Design',
                'client_name' => 'Placeholder — replace with a real client',
                'client_role' => 'Design system',
                'short_description' => 'A reusable Figma library and matching Webflow build so new marketing pages ship in days rather than weeks.',
                'full_description' => "A single source of truth for type, spacing, colour and components, defined in Figma and rebuilt as Webflow components so design and production stop drifting apart.\n\nThe library covers typography, buttons, form fields, cards and navigation, with documented usage rules so anyone on the team can build a new page without asking for a design review first.",
                'completed_at' => null,
                'technologies' => ['Figma', 'Webflow', 'Design Systems'],
                'is_featured' => true,
            ],
            [
                'title' => 'Research & Interface Redesign',
                'category' => 'UI/UX Design',
                'client_name' => 'Placeholder — replace with a real client',
                'client_role' => 'Product design',
                'short_description' => 'Usability testing and an interface rebuild around what people actually did, not what the brief assumed.',
                'full_description' => "Interviewed users, watched them complete the core task, and rebuilt the flows around the friction that showed up.\n\nThe redesign cut the path from landing to signup, replaced a multi-step form with a single progressive flow, and gave the front-end team annotated Figma frames instead of static mockups.",
                'completed_at' => null,
                'technologies' => ['Figma', 'Prototyping', 'User Research', 'Wireframing'],
                'is_featured' => true,
            ],
            [
                'title' => 'Marketing Site — Webflow Build',
                'category' => 'UI/UX Design',
                'client_name' => 'Placeholder — replace with a real client',
                'client_role' => 'Front-end build',
                'short_description' => 'A responsive marketing site designed and built in Webflow, from wireframe through to a launch-ready build.',
                'full_description' => "Designed the page in Figma, then built it in Webflow with a CMS collection for case studies and blog posts, so the client publishes without touching a template.\n\nResponsive behaviour was specified per breakpoint rather than left to shrink, and the build ships clean Lighthouse scores with no third-party script bloat.",
                'completed_at' => null,
                'technologies' => ['Webflow', 'Figma', 'CSS', 'Responsive Design'],
                'is_featured' => false,
            ],

            // ---------------------------------------------------- Development
            [
                'title' => 'Traffic Sign Analytics Using Deep Learning',
                'category' => 'Development',
                'client_name' => 'University of Wah',
                'client_role' => 'Final Year Project',
                'short_description' => 'Built a real-time traffic sign detection system using the YOLO object detection algorithm in Python.',
                'full_description' => "A final year project that detects and classifies traffic signs from a live camera feed in real time.\n\nBuilt on the YOLO object detection algorithm in Python, with a trained model, a live inference loop, and a simple interface for reviewing detections frame by frame.",
                'completed_at' => '2020-01-01',
                'technologies' => ['Python', 'YOLO', 'Deep Learning'],
                'is_featured' => true,
            ],
            [
                'title' => 'Applied ML, Web & App Builds',
                'category' => 'Development',
                'client_name' => 'University of Wah',
                'client_role' => 'Academic Projects',
                'short_description' => 'Applied machine learning algorithms on randomized datasets in Python; built dynamic websites with WordPress, custom CSS, and Shopify; designed Android applications in Java and an Inventory Management System using SQL.',
                'full_description' => "A set of academic builds spanning the stack: machine learning models trained and evaluated on randomized datasets, dynamic websites in WordPress and Shopify with hand-written CSS, Android applications in Java, and a relational Inventory Management System backed by SQL.\n\nEach project covered the full path from problem definition to a working, demonstrated result.",
                'completed_at' => '2020-07-01',
                'technologies' => ['Python', 'Machine Learning', 'WordPress', 'CSS', 'Shopify', 'Java', 'Android', 'SQL'],
                'is_featured' => true,
            ],
            [
                'title' => 'Face Recognition Project',
                'category' => 'Development',
                'client_name' => 'Stanford University',
                'client_role' => 'Volunteer',
                'short_description' => 'Contributed to a Stanford University project on face recognition using Python.',
                'full_description' => "Volunteer contribution to a Stanford University research project on face recognition, working in Python on the data preparation and evaluation side of the pipeline.\n\nWorked inside the project's existing codebase, so the contribution had to follow their conventions rather than impose new ones.",
                'completed_at' => null,
                'technologies' => ['Python', 'Machine Learning'],
                'is_featured' => false,
            ],
            [
                'title' => 'Cloud Computing Workshop',
                'category' => 'Development',
                'client_name' => null,
                'client_role' => 'Volunteer',
                'short_description' => 'Organized and led a workshop on cloud computing.',
                'full_description' => "Organised and delivered a hands-on cloud computing workshop, covering deployment, storage and the shared-responsibility model behind managed services.\n\nBuilt the session around practical exercises on a live environment rather than slides, so participants left having actually deployed something.",
                'completed_at' => null,
                'technologies' => [],
                'is_featured' => false,
            ],
        ];

        foreach ($projects as $index => $project) {
            $title = $project['title'];

            Project::create([
                'project_category_id' => $categoryIds[$project['category']],
                'title' => $title,
                'slug' => Project::slugFor($title),
                'client_name' => $project['client_name'],
                'client_role' => $project['client_role'],
                'short_description' => $project['short_description'],
                'full_description' => $project['full_description'],
                'featured_image' => $covers->generate($title),
                'project_url' => null,
                'github_url' => null,
                'completed_at' => $project['completed_at'],
                'technologies' => $project['technologies'],
                'is_featured' => $project['is_featured'],
                'is_published' => true,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedSettings(): void
    {
        Setting::query()->delete();

        foreach (self::settings() as $index => [$key, $value, $group, $label]) {
            Setting::create([
                'key' => $key,
                'value' => $value,
                'group' => $group,
                'type' => 'text',
                'label' => $label,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Every CMS setting as key => [value, group, label].
     *
     * Public so a setting can be added to an existing database without re-seeding
     * everything: the seeder is a full reset, and copying the value by hand is
     * how a live database and a fresh install drift apart.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function settings(): array
    {
        return [
            // Navigation. These label the fixed sections. The showcase sections
            // take their labels from the project categories, so adding a category
            // adds a navigation entry with no code change.
            ['nav.about', 'About', 'navigation', 'About link'],
            ['nav.latest_work', 'Latest Work', 'navigation', 'Latest work link'],
            ['nav.experience', 'Experience', 'navigation', 'Experience link'],
            ['nav.skills', 'Skills', 'navigation', 'Skills link'],
            ['nav.education', 'Education', 'navigation', 'Education link'],
            ['nav.contact', 'Contact', 'navigation', 'Contact link'],
            ['nav.resume', 'Get My Resume', 'navigation', 'Resume button label'],
            ['nav.menu_open', 'Open menu', 'navigation', 'Mobile menu open label'],
            ['nav.menu_close', 'Close menu', 'navigation', 'Mobile menu close label'],

            // Hero
            ['hero.title_line_1', 'Building the teams', 'hero', 'Hero heading, first line'],
            ['hero.title_line_2', 'behind the interface.', 'hero', 'Hero heading, second line'],
            ['hero.cta_primary', 'View My Work', 'hero', 'Hero primary button'],
            ['hero.cta_secondary', 'Get My Resume', 'hero', 'Hero resume button'],

            // Section headings. The showcase sections are not listed here: each
            // project category carries its own heading and intro.
            ['section.about.tag', 'About', 'sections', 'About eyebrow'],
            ['section.about.title', 'Where design leadership meets hands-on delivery', 'sections', 'About heading'],
            /*
             * The heading is one oversized line: a solid first word and a second
             * drawn as an outline. Only the two halves are editable, because a
             * section built this way has no room for an eyebrow or an intro.
             */
            ['section.latest_work.title', 'Latest', 'sections', 'Latest work heading, solid half'],
            ['section.latest_work.title_outline', 'work', 'sections', 'Latest work heading, outlined half'],
            ['section.experience.tag', 'Experience', 'sections', 'Experience eyebrow'],
            ['section.experience.title', 'Professional experience', 'sections', 'Experience heading'],
            ['section.experience.lead', 'A path from hands-on WordPress development to managing cross-functional design teams.', 'sections', 'Experience intro'],
            ['section.skills.tag', 'Skills', 'sections', 'Skills eyebrow'],
            ['section.skills.title', 'What he works with', 'sections', 'Skills heading'],
            ['section.education.tag', 'Education & Achievements', 'sections', 'Education eyebrow'],
            ['section.education.title', 'Foundations', 'sections', 'Education heading'],
            /*
             * The written introduction under the heading.
             *
             * Deliberately short. The degree, the final year project and the 1st
             * position are all left to the entry cards below, which carry their
             * own dates, institution and detail, so repeating them here in prose
             * would only say the same thing twice.
             */
            ['section.education.body', 'My academic journey began with a strong foundation in Computer Science, building my knowledge across software development, web technologies, databases, machine learning, and application development.', 'sections', 'Education intro'],
            ['section.contact.title', "Let's build something meaningful.", 'sections', 'Contact heading'],
            ['section.contact.lead', 'Open to conversations about design leadership, Webflow projects, and cross-border teams.', 'sections', 'Contact intro'],

            // Showcase cards
            ['projects.featured_badge', 'Featured', 'projects', 'Badge on featured projects'],
            ['projects.visit_cta', 'Visit project', 'projects', 'Project link label'],
            ['projects.source_cta', 'View source', 'projects', 'Source code link label'],

            // Marquee
            ['marquee.words', 'UI/UX Design, Webflow, Front-End Development, Team Leadership, Machine Learning', 'marquee', 'Scrolling words, comma separated'],

            // Contact
            ['contact.heading', 'Start a conversation', 'contact', 'Contact block heading'],
            ['contact.body', 'Open to conversations about design leadership, Webflow projects, and cross-border teams.', 'contact', 'Contact block intro'],
            ['contact.email_cta', 'Email Abdullah', 'contact', 'Contact email button'],
            ['contact.field_name', 'Name', 'contact', 'Form name field'],
            ['contact.field_email', 'Email', 'contact', 'Form email field'],
            ['contact.field_subject', 'Subject', 'contact', 'Form subject field'],
            ['contact.field_message', 'Message', 'contact', 'Form message field'],
            ['contact.message_placeholder', 'Tell me about the project.', 'contact', 'Form message placeholder'],
            ['contact.submit_cta', 'Send message', 'contact', 'Form submit button'],
            ['contact.success', "Thanks — your message is in. I'll reply shortly.", 'contact', 'Form success message'],
            ['contact.error', 'Something went wrong. Please try again or email me directly.', 'contact', 'Form error message'],

            // Footer
            ['footer.headline', 'Let’s build something meaningful.', 'general', 'Footer headline'],
            ['footer.text', 'Wah Cantt, Pakistan · abdullahhahmed1234@gmail.com', 'general', 'Footer contact line'],

            // SEO
            ['seo.site_name', 'Abdullah Ahmed', 'seo', 'Site name, used for Open Graph'],
            ['seo.title', 'Abdullah Ahmed — Team Manager, Webflow & UI/UX Developer', 'seo', 'Default meta title'],
            ['seo.description', 'Five years leading front-end design and development teams, running Germany-facing web projects at I Love Design GBR from client alignment to hiring to quality control.', 'seo', 'Meta description'],
            ['seo.keywords', 'Webflow developer, UI/UX designer, team manager, front-end developer, Webflow Pakistan', 'seo', 'Meta keywords'],
        ];
    }
}
