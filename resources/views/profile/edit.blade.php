<x-layouts.app title="My profile">
    <x-page-header title="My profile" subtitle="Your account details and password." />

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Account details</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <x-form.input name="name" label="Name" :value="$user->name" required autocomplete="name" />
                        <x-form.input name="email" type="email" label="Email address" :value="$user->email" required autocomplete="username" />

                        <dl class="row small text-body-secondary mb-3">
                            <dt class="col-sm-4 fw-normal">Role</dt>
                            <dd class="col-sm-8 mb-1">{{ $user->role->label() }}</dd>
                            <dt class="col-sm-4 fw-normal">Last sign-in</dt>
                            <dd class="col-sm-8 mb-0">{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Never' }}</dd>
                        </dl>

                        <x-submit-button>Save details</x-submit-button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Change password</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.password') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <x-form.input name="current_password" type="password" label="Current password" bag="updatePassword"
                                      required autocomplete="current-password" />
                        <x-form.input name="password" id="new_password" type="password" label="New password" bag="updatePassword"
                                      required autocomplete="new-password"
                                      help="At least 10 characters, with upper and lower case letters and a number." />
                        <x-form.input name="password_confirmation" type="password" label="Confirm new password" bag="updatePassword"
                                      required autocomplete="new-password" />

                        <x-submit-button>Change password</x-submit-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
