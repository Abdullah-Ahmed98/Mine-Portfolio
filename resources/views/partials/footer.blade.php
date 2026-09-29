<footer class="footer">
    <div class="wrap">
        <div class="footer__grid">
            <div>
                <p class="footer__cta">
                    {{ setting('footer.headline', setting('section.contact.title', "Let's build something meaningful.")) }}
                </p>

                @if ($profile->email)
                    <p class="muted" style="margin-top: 18px;">
                        <a href="mailto:{{ $profile->email }}">{{ $profile->email }}</a>
                    </p>
                @endif
            </div>

            <div class="footer__socials">
                @include('partials.social-links', ['socialLinks' => $socialLinks ?? collect(), 'iconOnly' => true])
            </div>
        </div>

        <div class="footer__meta">
            <span>{{ setting('footer.text', $profile->location) }}</span>
            <span>&copy; {{ date('Y') }} {{ $profile->full_name }}</span>
        </div>
    </div>

    <button class="to-top" type="button" data-to-top aria-label="Back to top" hidden>
        @include('partials.icon', ['name' => 'chevron-up', 'class' => 'to-top__icon'])
    </button>
</footer>
