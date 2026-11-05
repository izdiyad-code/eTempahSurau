<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show register page.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Register a new Public Booker.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'booker_type' => ['required', 'in:muslim,non_muslim'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'booker_type' => $validated['booker_type'],
            'preferred_language' => 'en',
            'role' => 'public',
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with(
                'success',
                __('Registration successful. Welcome to e-TempahSurau!')
            );
    }

    /**
     * Show login page.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Login user.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (
            Auth::attempt(
                $credentials,
                $request->boolean('remember')
            )
        ) {
            $request->session()->regenerate();

            $user = $request->user();

            // The guest page may use English before the account's saved language is known.
            $locale = $request->session()->get('locale', $user->preferred_language);
            app()->setLocale(in_array($locale, ['en', 'ms'], true) ? $locale : 'en');

            /*
             * Public Booker:
             * Return to Home Page + Calendar.
             */
            if ($user->role === 'public') {
                return redirect()
                    ->route('home')
                    ->with(
                        'success',
                        __('Welcome back to e-TempahSurau!')
                    );
            }

            /*
             * Secretary / PIC:
             * Go to management dashboard.
             */
            if ($user->role === 'pic') {
                return redirect()
                    ->route('dashboard')
                    ->with(
                        'success',
                        __('Welcome to the Secretary / PIC panel.')
                    );
            }

            /*
             * Chairman:
             * Go to Chairman dashboard.
             */
            if ($user->role === 'chairman') {
                return redirect()
                    ->route('dashboard')
                    ->with(
                        'success',
                        __('Welcome to the Chairman dashboard.')
                    );
            }

            /*
             * Fallback.
             */
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => __('Your account role is invalid.'),
                ]);
        }

        return back()
            ->withErrors([
                'email' => __('Email or password is incorrect.'),
            ])
            ->onlyInput('email');
    }

    /**
     * Logout current user.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'success',
                __('You have been logged out successfully.')
            );
    }
}
