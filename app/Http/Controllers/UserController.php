<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * User directory.
 *  - PIC: sees public bookers only (never the Chairman).
 *  - Chairman: sees PIC and public bookers, and can add / remove PIC accounts.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $isChairman = $request->user()->isChairman();

        $query = User::withCount('bookings')->where('role', '!=', 'chairman');

        if (!$isChairman) {
            $query->where('role', 'public');
        } elseif (in_array($request->query('role'), ['pic', 'public'], true)) {
            $query->where('role', $request->query('role'));
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return view('staff.users', [
            'users' => $query->orderBy('role')->orderBy('name')->paginate(15)->withQueryString(),
            'isChairman' => $isChairman,
        ]);
    }

    public function storePic(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        User::create($data + [
            'role' => 'pic',
            'booker_type' => 'muslim',
            'preferred_language' => 'en',
        ]);

        return back()->with('success', __('PIC account created.'));
    }

    public function destroyPic(User $user)
    {
        abort_unless($user->role === 'pic', 403);

        $user->delete();

        return back()->with('success', __('PIC account removed.'));
    }
}
