<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('ProfilePage', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'github_id' => $user->github_id,
            ],
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

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $clientIds = Client::withTrashed()->where('user_id', $user->id)->pluck('id');
        $projectIds = Project::withTrashed()->whereIn('client_id', $clientIds)->pluck('id');

        TimesheetEntry::whereIn('project_id', $projectIds)->forceDelete();
        Invoice::whereIn('project_id', $projectIds)->forceDelete();
        Project::withTrashed()->whereIn('id', $projectIds)->forceDelete();
        Client::withTrashed()->whereIn('id', $clientIds)->forceDelete();

        $user->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')
            ->with('success', 'Votre compte a été supprimé.');
    }
}
