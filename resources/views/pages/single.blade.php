@extends('components.layouts.app')

@section('content')
    {{-- Hero --}}
    <section class="hero" id="top">
        <div class="hero__glow" aria-hidden="true"></div>

        <div class="wrap hero__grid">
            <div class="hero__copy">
                <p class="hero__eyebrow">
                    @if ($profile->availability_status)
                        <span class="availability-dot" aria-hidden="true"></span>
                    @endif
                    {{ $profile->role_short ?: $profile->title }}
                </p>

                {{--
                    Each headline line is wrapped in its own overflow mask so the
                    text can rise out from under its own line rather than fading
                    in as one block.
                --}}
                <h1 class="display hero__title">
                    <span class="mask-line"><span class="mask-line__inner">{{ setting('hero.title_line_1', 'Building the teams') }}</span></span>
                    <span class="mask-line"><span class="mask-line__inner outline-text">{{ setting('hero.title_line_2', 'behind the interface.') }}</span></span>
                </h1>

                @if ($lead = $profile->short_intro)
                    <p class="lead hero__lead">{{ $lead }}</p>
                @endif

                <div class="hero__cta">
                    @if ($firstSection = $navItems[0] ?? null)
                        <a class="btn btn--primary" href="#{{ $firstSection['anchor'] }}" data-magnetic>
                            <span>{{ setting('hero.cta_primary', 'View My Work') }}</span>
                            @include('partials.icon', ['name' => 'arrow-up-right', 'class' => 'btn__icon'])
                        </a>
                    @endif

                    @if ($profile->hasCv())
                        <a class="btn btn--ghost" href="{{ route('resume') }}" target="_blank" rel="noopener" data-magnetic>
                            @include('partials.icon', ['name' => 'download', 'class' => 'btn__icon'])
                            {{ setting('hero.cta_secondary', 'Get My Resume') }}
                        </a>
                    @endif
                </div>

                {{--
                    The real figure is rendered server side so the number is
                    correct without JavaScript and under reduced motion; the
                    count-up only rewrites it on its way up from zero.
                --}}
                @if (array_filter($stats))
                    <dl class="hero-stats">
                        @if ($stats['years'])
                            <div class="hero-stats__item">
                                <dt>{{ setting('hero.stat_years_label', 'Years experience') }}</dt>
                                <dd class="display">
                                    <span data-count="{{ $stats['years'] }}">{{ $stats['years'] }}</span><span aria-hidden="true">+</span>
                                </dd>
                            </div>
                        @endif

                        @if ($stats['projects'])
                            <div class="hero-stats__item">
                                <dt>{{ setting('hero.stat_projects_label', 'Projects shipped') }}</dt>
                                <dd class="display">
                                    <span data-count="{{ $stats['projects'] }}">{{ $stats['projects'] }}</span>
                                </dd>
                            </div>
                        @endif

                        @if ($stats['roles'])
                            <div class="hero-stats__item">
                                <dt>{{ setting('hero.stat_roles_label', 'Roles held') }}</dt>
                                <dd class="display">
                                    <span data-count="{{ $stats['roles'] }}">{{ $stats['roles'] }}</span>
                                </dd>
                            </div>
                        @endif
                    </dl>
                @endif

                <div class="hero-meta">
                    @if ($profile->location)
                        <span>{{ $profile->location }}</span>
                    @endif
                    @if ($profile->availability_status && $profile->availability_text)
                        <span>{{ $profile->availability_text }}</span>
                    @endif
                </div>

                @if (($socialLinks ?? collect())->contains(fn ($link) => $link->isRenderable()))
                    <div style="margin-top: 28px; display: flex; justify-content: inherit;">
                        @include('partials.social-links', ['socialLinks' => $socialLinks])
                    </div>
                @endif
            </div>

            <div class="portrait-stage">
                <span class="portrait-ring portrait-ring--1" aria-hidden="true"></span>
                <span class="portrait-ring portrait-ring--2" aria-hidden="true"></span>

                @if ($profile->profileImageUrl())
                    <div class="portrait-frame" data-parallax-frame data-tilt>
                        <img src="{{ $profile->profileImageUrl() }}"
                             alt="{{ $profile->full_name }}"
                             width="1145"
                             height="1374"
                             fetchpriority="high"
                             decoding="async">
                    </div>
                @endif
            </div>
        </div>
    </section>

    @include('partials.marquee')

    {{-- About --}}
    <section class="section" id="about">
        <div class="wrap">
            <x-section-head :tag="setting('section.about.tag', 'About')" :title="setting('section.about.title', 'Where design leadership meets hands-on delivery')" />

            <div class="about-grid">
                <div class="about-copy reveal">
                    @foreach ($profile->descriptionParagraphs() as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach

                    @if ($profile->descriptionParagraphs() === [] && $profile->short_intro)
                        <p>{{ $profile->short_intro }}</p>
                    @endif
                </div>

                @if ($highlights->isNotEmpty())
                    <div class="trait-list reveal" data-delay="1">
                        @foreach ($highlights as $highlight)
                            <div class="trait">
                                <h3>{{ $highlight->title }}</h3>
                                <p>{{ $highlight->text }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Latest work. Managed in its own admin screen; skipped entirely when empty. --}}
    @include('partials.latest-work-section', ['items' => $latestWork ?? collect()])

    {{--
        One showcase section per project category, in the order the admin
        arranged them. The order below the showcase block (skills, experience,
        education, contact) is fixed; everything inside a section is CMS data.
    --}}
    @foreach ($showcase as $position => $category)
        @include('partials.showcase-section', ['category' => $category, 'position' => $position + 1])
    @endforeach

    {{-- Skills --}}
    @if ($skillGroups->isNotEmpty())
        <section class="section section--tight" id="skills">
            <div class="wrap">
                <x-section-head :tag="setting('section.skills.tag', 'Skills')" :title="setting('section.skills.title', 'What he works with')" />

                <div class="skill-grid">
                    @foreach ($skillGroups as $index => $group)
                        <div class="skill-group reveal" data-delay="{{ $index % 4 }}">
                            <h3>{{ $group->name }}</h3>

                            <div class="skill-list">
                                @foreach ($group->skills as $skill)
                                    @if ($skill->hasLevel())
                                        <div class="skill-item">
                                            <div class="skill-item__top">
                                                <span class="pill pill--accent">{{ $skill->name }}</span>
                                                <span class="muted" style="font-size: 0.75rem;">{{ $skill->level }}%</span>
                                            </div>
                                            <div class="skill-meter">
                                                <div class="skill-meter__fill" style="width: {{ $skill->level }}%"></div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="pill">{{ $skill->name }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Experience --}}
    @if ($experiences->isNotEmpty())
        <section class="section" id="experience">
            <div class="wrap">
                <x-section-head :tag="setting('section.experience.tag', 'Experience')" :title="setting('section.experience.title', 'Professional experience')" :lead="setting('section.experience.lead')" />

                <div class="timeline">
                    @foreach ($experiences as $experience)
                        @include('partials.experience-item', ['experience' => $experience])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Education --}}
    @php
        // The written introduction and the entry cards are edited separately, so
        // the section needs to survive either one being emptied.
        $educationIntro = setting_paragraphs('section.education.body');
    @endphp

    @if ($educationIntro !== [] || $education->isNotEmpty())
        <section class="section section--tight" id="education">
            <div class="wrap">
                <x-section-head :tag="setting('section.education.tag', 'Education & Achievements')" :title="setting('section.education.title', 'Foundations')" />

                @if ($educationIntro !== [])
                    {{-- A blank line starts a new paragraph; a single line break stays inside one. --}}
                    <div class="education-copy reveal">
                        @foreach ($educationIntro as $paragraph)
                            <p>{!! nl2br(e($paragraph)) !!}</p>
                        @endforeach
                    </div>
                @endif

                @if ($education->isNotEmpty())
                    <div class="info-grid">
                        @foreach ($education as $index => $entry)
                            <div class="card reveal" data-delay="{{ $index % 3 }}">
                                <h3>{{ $entry->title }}</h3>
                                @if ($meta = $entry->meta ?: $entry->institution)
                                    <p class="info-grid__meta">
                                        {{ $meta }}@if ($entry->start_date) · {{ $entry->dateRange() }}@endif
                                    </p>
                                @endif
                                @if ($entry->description)
                                    <p>{{ $entry->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Contact --}}
    <section class="section" id="contact">
        <div class="wrap">
            <div class="contact-box reveal">
                <h2 class="title">{{ setting('section.contact.title', "Let's build something meaningful.") }}</h2>
                <p class="contact-box__lead">{{ setting('section.contact.lead') }}</p>

                <div class="contact-links">
                    @if ($profile->email)
                        <a class="btn btn--primary" href="mailto:{{ $profile->email }}">
                            @include('partials.icon', ['name' => 'email', 'class' => 'btn__icon'])
                            {{ setting('contact.email_cta', 'Email Abdullah') }}
                        </a>
                    @endif

                    @if ($profile->hasCv())
                        <a class="btn btn--ghost" href="{{ route('resume') }}" target="_blank" rel="noopener">
                            @include('partials.icon', ['name' => 'download', 'class' => 'btn__icon'])
                            {{ setting('nav.resume', 'Get My Resume') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="wrap">
            <div class="contact-grid" style="margin-top: clamp(48px, 8vh, 90px);">
                <div class="reveal">
                    <p class="eyebrow">{{ setting('contact.heading', 'Contact') }}</p>
                    <h2 class="title" style="margin-bottom: 20px;">{{ setting('contact.body') }}</h2>

                    <div class="hero-meta" style="justify-content: flex-start;">
                        @if ($profile->location)
                            <span>{{ $profile->location }}</span>
                        @endif
                        @if ($profile->email)
                            <a href="mailto:{{ $profile->email }}">{{ $profile->email }}</a>
                        @endif
                        @if ($profile->availability_status && $profile->availability_text)
                            <span>{{ $profile->availability_text }}</span>
                        @endif
                    </div>

                    @if (($socialLinks ?? collect())->contains(fn ($link) => $link->isRenderable()))
                        <div style="margin-top: 24px;">
                            @include('partials.social-links', ['socialLinks' => $socialLinks])
                        </div>
                    @endif
                </div>

                <div class="reveal" data-delay="1">
                    @if (session('contact_sent'))
                        <div class="alert alert--ok" style="margin-bottom: 20px;">{{ session('status') }}</div>
                    @endif

                    <form class="form" method="POST" action="{{ route('contact.store') }}" novalidate>
                        @csrf

                        <div class="honeypot" aria-hidden="true">
                            <label for="website">Website</label>
                            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="form__row">
                            <div class="field{{ $errors->has('name') ? ' field--invalid' : '' }}">
                                <label class="field__label" for="name">{{ setting('contact.field_name', 'Name') }} <span>*</span></label>
                                <input class="field__control" type="text" id="name" name="name"
                                       value="{{ old('name') }}" required autocomplete="name">
                                @error('name')<p class="field__error">{{ $message }}</p>@enderror
                            </div>

                            <div class="field{{ $errors->has('email') ? ' field--invalid' : '' }}">
                                <label class="field__label" for="email">{{ setting('contact.field_email', 'Email') }} <span>*</span></label>
                                <input class="field__control" type="email" id="email" name="email"
                                       value="{{ old('email') }}" required autocomplete="email">
                                @error('email')<p class="field__error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="field">
                            <label class="field__label" for="subject">{{ setting('contact.field_subject', 'Subject') }}</label>
                            <input class="field__control" type="text" id="subject" name="subject"
                                   value="{{ old('subject') }}">
                            @error('subject')<p class="field__error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field{{ $errors->has('message') ? ' field--invalid' : '' }}">
                            <label class="field__label" for="message">{{ setting('contact.field_message', 'Message') }} <span>*</span></label>
                            <textarea class="field__control" id="message" name="message" required
                                      maxlength="4000"
                                      placeholder="{{ setting('contact.message_placeholder', 'Tell me about the project.') }}">{{ old('message') }}</textarea>
                            <p class="field__hint" data-char-count>0 / 4000</p>
                            @error('message')<p class="field__error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <button class="btn btn--primary" type="submit">
                                {{ setting('contact.submit_cta', 'Send message') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
