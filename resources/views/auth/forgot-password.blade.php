<x-layouts.guest title="Forgot password">
    <h1 class="h5 mb-2">Reset your password</h1>
    <p class="text-body-secondary small">Enter your account email and we will send you a link to choose a new password.</p>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <x-form.input name="email" type="email" label="Email address" required autofocus autocomplete="username" />

        <x-submit-button class="w-100">Email reset link</x-submit-button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="small">Back to sign in</a>
    </div>
</x-layouts.guest>
