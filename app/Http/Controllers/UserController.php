<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Users are deactivated rather than deleted, so stock history and activity
 * logs always point to a real account.
 */
class UserController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->search($filters['search'] ?? null)
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', ['users' => $users, 'filters' => $filters]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', ['user' => new User]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->only(['name', 'email', 'password']));
        $user->role = UserRole::from($request->validated('role'));
        $user->save();

        $this->activity->log('user.created', "Created user {$user->email}", $user, [
            'role' => $user->role->value,
        ]);

        return redirect()->route('users.index')->with('success', "User {$user->name} created.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email']));
        $user->role = UserRole::from($request->validated('role'));

        $passwordChanged = filled($request->validated('password'));

        if ($passwordChanged) {
            $user->password = $request->validated('password');
        }

        $user->save();

        $changes = $this->activity->changesOf($user);
        unset($changes['old']['password'], $changes['new']['password']);

        if ($changes['new'] !== [] || $passwordChanged) {
            $this->activity->log('user.updated', "Updated user {$user->email}", $user, [
                ...$changes,
                'password_reset' => $passwordChanged,
            ]);
        }

        return redirect()->route('users.index')->with('success', "User {$user->name} updated.");
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->authorize('changeAccess', $user);

        $user->is_active = ! $user->is_active;
        $user->save();

        $action = $user->is_active ? 'activated' : 'deactivated';
        $this->activity->log("user.{$action}", ucfirst($action)." user {$user->email}", $user);

        return back()->with('success', "User {$user->name} {$action}.");
    }
}
