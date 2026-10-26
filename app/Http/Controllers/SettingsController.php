<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()->load('member');

        $defaultSettings = [
            'theme' => 'dark',
            'language' => 'id',
            'notifications' => [
                'email' => true,
                'browser' => true,
            ],
            'sidebar_collapsed' => false,
        ];

        $settings = array_merge($defaultSettings, $user->settings ?? []);

        return Inertia::render('Settings/Index', [
            'user' => $user,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['nullable', 'in:light,dark,system'],
            'language' => ['nullable', 'in:id,en'],
            'notifications.email' => ['nullable', 'boolean'],
            'notifications.browser' => ['nullable', 'boolean'],
            'sidebar_collapsed' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $currentSettings = $user->settings ?? [];
        $user->settings = array_merge($currentSettings, $validated);
        $user->save();

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->back()->with('success', 'Kata sandi berhasil diubah.');
    }
}
