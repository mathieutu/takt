<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('ProfilePage', [
            'user' => $user->export([
                'name',
                'email',
                'github_id',
                'api_token',
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ]);

        $request->user()->update($validated);

        return redirect()
            ->route('profile')
            ->with('success', 'Votre profil a été mis à jour.');
    }

    public function regenerateToken(Request $request): RedirectResponse
    {
        $request->user()->update(['api_token' => Str::random(80)]);

        return redirect()->route('profile')
            ->with('success', 'Token API régénéré.');
    }

    public function deleteToken(Request $request): RedirectResponse
    {
        $request->user()->update(['api_token' => null]);

        return redirect()->route('profile')
            ->with('success', 'Token API supprimé.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Logging out before deleting matters: Auth::logout() cycles the remember_token via
        // $user->save(), and calling that on an already-deleted (exists: false) model instance
        // would re-insert the row Eloquent just deleted.
        Auth::logout();

        $clientIds = Client::withTrashed()->where('user_id', $user->id)->pluck('id');
        $projectIds = Project::query()->whereIn('client_id', $clientIds)->pluck('id');

        TimesheetEntry::whereIn('project_id', $projectIds)->forceDelete();
        Invoice::whereIn('project_id', $projectIds)->forceDelete();
        Project::query()->whereIn('id', $projectIds)->delete();
        Client::withTrashed()->whereIn('id', $clientIds)->forceDelete();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')
            ->with('success', 'Votre compte a été supprimé.');
    }
}
