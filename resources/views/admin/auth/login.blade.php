<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>Sign in — {{ setting('seo.site_name', config('app.name')) }}</title>

    @vite(['resources/css/admin.css'])
</head>
<body>
    <div class="login-page">
        <div class="login">
            <div class="login__brand">
                <span class="sidebar__mark">{{ Str::upper(Str::substr(setting('seo.site_name', 'P'), 0, 1)) }}</span>
                <span>{{ setting('seo.site_name', config('app.name')) }}</span>
            </div>

            <div class="card">
                <h1 class="login__title">Sign in</h1>
                <p class="login__hint">Use the admin account to manage the site.</p>

                @if (session('status'))
                    <div class="alert alert--ok">{{ session('status') }}</div>
                @endif

                <form class="form" method="POST" action="{{ route('admin.login') }}">
                    @csrf

                    <div class="field{{ $errors->has('email') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="email">Email</label>
                        <input class="field__control" id="email" name="email" type="email"
                               value="{{ old('email') }}" required autofocus autocomplete="username">
                        @include('admin.partials.error', ['field' => 'email'])
                    </div>

                    <div class="field{{ $errors->has('password') ? ' field--invalid' : '' }}">
                        <label class="field__label" for="password">Password</label>
                        <input class="field__control" id="password" name="password" type="password"
                               required autocomplete="current-password">
                        @include('admin.partials.error', ['field' => 'password'])
                    </div>

                    <label class="checkbox">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        Keep me signed in
                    </label>

                    <div class="form__actions">
                        <button class="btn btn--primary" type="submit">Sign in</button>
                        <a class="btn btn--ghost" href="{{ route('home') }}">Back to site</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
