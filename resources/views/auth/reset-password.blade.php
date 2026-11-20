<x-layouts.guest title="Choose a new password">
    <h1 class="h5 mb-3">Choose a new password</h1>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-form.input name="email" type="email" label="Email address" :value="$email" required autocomplete="username" />
        <x-form.input name="password" type="password" label="New password" required autocomplete="new-password"
                      help="At least 10 characters, with upper and lower case letters and a number." />
        <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />

        <x-submit-button class="w-100">Save new password</x-submit-button>
    </form>
</x-layouts.guest>
