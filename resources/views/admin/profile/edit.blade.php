<x-layouts.admin title="Profile">
    <div class="topbar">
        <div>
            <h1>Profile</h1>
            <p>Your name, headline, bio, photo and resume. These fill the hero, about and contact sections.</p>
        </div>

        <div class="topbar__actions">
            <a class="btn btn--ghost btn--sm" href="{{ route('home') }}" target="_blank" rel="noopener">View site</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert--error">Please fix the highlighted fields below.</div>
    @endif

    <form class="form" method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card">
            <p class="card__title">Basics</p>
            <p class="card__hint">The name and headline appear in the header, the hero and the page titles.</p>

            <div class="form__grid">
                <div class="field{{ $errors->has('full_name') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="full_name">Full name <span>*</span></label>
                    <input class="field__control" id="full_name" name="full_name" type="text"
                           value="{{ old('full_name', $profile->full_name) }}" required>
                    @include('admin.partials.error', ['field' => 'full_name'])
                </div>

                <div class="field{{ $errors->has('title') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="title">Job title <span>*</span></label>
                    <input class="field__control" id="title" name="title" type="text"
                           value="{{ old('title', $profile->title) }}" required>
                    @include('admin.partials.error', ['field' => 'title'])
                </div>

                <div class="field field--full">
                    <label class="field__label" for="role_short">Short role line</label>
                    <input class="field__control" id="role_short" name="role_short" type="text"
                           value="{{ old('role_short', $profile->role_short) }}">
                    <p class="field__hint">A compact version of the job title, used where space is tight.</p>
                    @include('admin.partials.error', ['field' => 'role_short'])
                </div>

                <div class="field field--full">
                    <label class="field__label" for="short_intro">Short intro</label>
                    <textarea class="field__control" id="short_intro" name="short_intro" rows="3">{{ old('short_intro', $profile->short_intro) }}</textarea>
                    <p class="field__hint">The lead paragraph in the hero. One or two sentences.</p>
                    @include('admin.partials.error', ['field' => 'short_intro'])
                </div>

                <div class="field field--full">
                    <label class="field__label" for="full_description">About me</label>
                    <textarea class="field__control" id="full_description" name="full_description" rows="9">{{ old('full_description', $profile->full_description) }}</textarea>
                    <p class="field__hint">Leave a blank line between paragraphs — each one is rendered separately.</p>
                    @include('admin.partials.error', ['field' => 'full_description'])
                </div>
            </div>
        </div>

        <div class="card">
            <p class="card__title">Contact</p>
            <p class="card__hint">Used by the contact section, the footer and the schema markup.</p>

            <div class="form__grid">
                <div class="field{{ $errors->has('email') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="email">Email</label>
                    <input class="field__control" id="email" name="email" type="email"
                           value="{{ old('email', $profile->email) }}">
                    @include('admin.partials.error', ['field' => 'email'])
                </div>

                <div class="field{{ $errors->has('phone') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="phone">Phone</label>
                    <input class="field__control" id="phone" name="phone" type="text"
                           value="{{ old('phone', $profile->phone) }}">
                    @include('admin.partials.error', ['field' => 'phone'])
                </div>

                <div class="field{{ $errors->has('location') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="location">Location</label>
                    <input class="field__control" id="location" name="location" type="text"
                           value="{{ old('location', $profile->location) }}">
                    @include('admin.partials.error', ['field' => 'location'])
                </div>

                <div class="field{{ $errors->has('years_experience') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="years_experience">Years of experience</label>
                    <input class="field__control" id="years_experience" name="years_experience" type="text"
                           placeholder="5+" value="{{ old('years_experience', $profile->years_experience) }}">
                    <p class="field__hint">Free text, so “5+” is fine.</p>
                    @include('admin.partials.error', ['field' => 'years_experience'])
                </div>
            </div>
        </div>

        <div class="card">
            <p class="card__title">Availability</p>
            <p class="card__hint">The small status pill under the hero headline.</p>

            <div class="form__grid">
                <div class="field{{ $errors->has('availability_text') ? ' field--invalid' : '' }}">
                    <label class="field__label" for="availability_text">Status text</label>
                    <input class="field__control" id="availability_text" name="availability_text" type="text"
                           value="{{ old('availability_text', $profile->availability_text) }}">
                    @include('admin.partials.error', ['field' => 'availability_text'])
                </div>

                <div class="field">
                    <span class="field__label">Status dot</span>
                    <label class="checkbox">
                        <input type="checkbox" name="availability_status" value="1"
                               @checked(old('availability_status', $profile->availability_status))>
                        Show the green “available” dot
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <p class="card__title">Photo &amp; resume</p>
            <p class="card__hint">JPG, PNG or WebP up to 8 MB. Resizes are generated automatically.</p>

            <div class="split">
                <div>
                    <div class="field{{ $errors->has('profile_image') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="profile_image">Profile photo</label>
                        <input class="field__control" id="profile_image" name="profile_image" type="file"
                               accept="image/jpeg,image/png,image/webp">
                        @include('admin.partials.error', ['field' => 'profile_image'])
                    </div>

                    @if ($profile->profileImageUrl())
                        <div class="image-item__row" style="margin-top: 12px;">
                            <img class="avatar-preview" src="{{ $profile->profileImageUrl() }}"
                                 alt="Current profile photo">
                            <label class="checkbox">
                                <input type="checkbox" name="remove_image" value="1">
                                Remove photo
                            </label>
                        </div>
                    @else
                        <p class="field__hint" style="margin-top: 10px;">No photo uploaded yet.</p>
                    @endif
                </div>

                <div>
                    <div class="field{{ $errors->has('cv') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="cv">Resume (PDF, up to 4 MB)</label>
                        <input class="field__control" id="cv" name="cv" type="file" accept="application/pdf">
                        @include('admin.partials.error', ['field' => 'cv'])
                    </div>

                    @if ($profile->hasCv())
                        <div class="image-item__row" style="margin-top: 12px;">
                            <a class="btn btn--ghost btn--sm" href="{{ route('resume') }}" target="_blank" rel="noopener">
                                @include('partials.icon', ['name' => 'download'])
                                Current PDF
                            </a>
                            <label class="checkbox">
                                <input type="checkbox" name="remove_cv" value="1">
                                Remove
                            </label>
                        </div>
                        <p class="field__hint" style="margin-top: 8px;">
                            Uploading a new file replaces this one.
                        </p>
                    @else
                        <p class="field__hint" style="margin-top: 10px;">
                            No resume uploaded. The “Get My Resume” buttons stay hidden until you add one.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">Save profile</button>
            <a class="btn btn--ghost" href="{{ route('admin.dashboard') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
