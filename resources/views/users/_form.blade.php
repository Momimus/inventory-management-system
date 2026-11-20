@php
    $editingSelf = $user->exists && $user->is(auth()->user());
@endphp

<x-form.input name="name" label="Name" :value="$user->name" required maxlength="255" />
<x-form.input name="email" type="email" label="Email address" :value="$user->email" required maxlength="255" />

<x-form.select name="role" label="Role" :options="\App\Enums\UserRole::options()" :value="$user->role" required
               :disabled="$editingSelf"
               :help="$editingSelf ? 'You cannot change your own role.' : 'Administrators can manage users, categories, suppliers, adjustments and reports.'" />
@if ($editingSelf)
    {{-- Disabled inputs are not submitted; the server also rejects any change to your own role. --}}
    <input type="hidden" name="role" value="{{ $user->role->value }}">
@endif

<hr class="my-4">

<x-form.input name="password" type="password" :label="$user->exists ? 'New password' : 'Password'"
              :required="! $user->exists" autocomplete="new-password"
              :help="$user->exists ? 'Leave blank to keep the current password.' : 'At least 10 characters, with upper and lower case letters and a number.'" />
<x-form.input name="password_confirmation" type="password" label="Confirm password" :required="! $user->exists" autocomplete="new-password" />
