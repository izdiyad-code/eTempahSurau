<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $viewer = $request->user();
        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');
        $allowedSorts = ['name', 'email', 'role', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'created_at';
        if (!in_array($direction, ['asc', 'desc'], true)) $direction = 'desc';

        $query = User::query();
        // PIC can manage/view public users and PIC accounts, but never Chairman accounts.
        if ($viewer->isPic()) {
            $query->whereIn('role', ['public', 'pic']);
        }

        if ($role = $request->query('role')) {
            $allowedRoles = $viewer->isChairman() ? ['public', 'pic', 'chairman'] : ['public', 'pic'];
            if (in_array($role, $allowedRoles, true)) $query->where('role', $role);
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy($sort, $direction)->paginate(15)->withQueryString();
        return view('staff.users', compact('users', 'sort', 'direction'));
    }

    public function storePic(Request $request)
    {
        abort_unless($request->user()->isChairman(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'temporary_password' => ['required', 'string', 'min:8', 'max:100'],
            'preferred_language' => ['required', Rule::in(['en', 'ms'])],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => 'pic',
            'booker_type' => 'muslim',
            'preferred_language' => $data['preferred_language'],
            'password' => Hash::make($data['temporary_password']),
        ]);

        return redirect()->route('staff.users.index')
            ->with('success', __('PIC account created. Share the temporary password with the new PIC privately.'));
    }

    public function sendPasswordReset(Request $request, User $user)
    {
        // A PIC must never be able to access a Chairman account, even by guessing its URL.
        abort_if($request->user()->isPic() && $user->isChairman(), 403);

        $status = Password::sendResetLink(['email' => $user->email]);
        return back()->with($status === Password::RESET_LINK_SENT ? 'success' : 'error', __($status));
    }
}
