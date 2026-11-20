<x-layouts.guest title="Sign in">
    <h1 class="h5 mb-3">Sign in</h1>

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <x-form.input name="email" type="email" label="Email address" required autofocus autocomplete="username" />
        <x-form.input name="password" type="password" label="Password" required autocomplete="current-password" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))>
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
        </div>

        <x-submit-button class="w-100">Sign in</x-submit-button>
    </form>
</x-layouts.guest>
