<?php

namespace App\Http\Concerns;

use App\Models\Client;
use App\Models\Project;
use App\Models\ReceivedShare;
use App\Models\Share;
use Illuminate\Http\Request;

trait BuildsProjectsPageProps
{
    protected function projectsPageProps(Request $request): array
    {
        $search = $request->string('search')->toString();
        $clientId = $request->string('client_id')->toString();
        $withTrashed = $request->boolean('with_trashed');
        $sort = $request->string('sort', 'date_desc')->toString();

        return [
            'has_trashed' => fn () => (
                $request->user()->projects()->onlyTrashed()->exists()
                || $request->user()->clients()->onlyTrashed()->exists()
            ),

            'has_active' => fn () => $request->user()->projects()->exists(),

            'projects' => function () use ($request, $search, $clientId, $withTrashed, $sort) {
                $user = $request->user();

                $query = $withTrashed ?
                    Project::withTrashed()
                        ->with(['client' => fn ($q) => $q->withTrashed()])
                        ->whereIn('client_id', $user->clients()->withTrashed()->pluck('id'))
                    : $user->projects();

                if ($search) {
                    $clientsQuery = $withTrashed
                        ? $user->clients()->withTrashed()
                        : $user->clients();

                    $query->where(fn ($q) => $q
                        ->where('projects.name', 'like', "%{$search}%")
                        ->orWhereIn('projects.client_id', $clientsQuery->where('name', 'like', "%{$search}%")->select('id'))
                    );
                }

                if ($clientId) {
                    $query->where('client_id', $clientId);
                }

                $query->orderBy(
                    str_starts_with($sort, 'rate') ? 'projects.daily_rate' : 'projects.created_at',
                    str_ends_with($sort, 'asc') ? 'asc' : 'desc',
                );

                return $query->withExists('sharer')->get()->map->export([
                    'id',
                    'name',
                    'description',
                    'daily_rate',
                    'max_month_budget',
                    'client' => ['id', 'name'],
                    'created_at',
                    'deleted_at',
                    'sharer_exists as is_shared',
                ]);
            },

            'clients' => function () use ($request, $search, $withTrashed, $sort) {
                $query = $request->user()->clients();

                if ($withTrashed) {
                    $query->withTrashed();
                }

                if ($search) {
                    $query->where('name', 'like', "%{$search}%");
                }

                $query->orderBy(
                    str_starts_with($sort, 'rate') ? 'daily_rate' : 'created_at',
                    str_ends_with($sort, 'asc') ? 'asc' : 'desc',
                );

                return $query->withExists('sharer')->get()->map->export([
                    'id',
                    'name',
                    'daily_rate',
                    'created_at',
                    'deleted_at',
                    'sharer_exists as is_shared',
                ]);
            },

            'shares_received' => fn () => $request->user()->receivedShares()
                ->with(['share.shareable.user'])
                ->get()
                ->map(fn (ReceivedShare $rs) => [
                    'id' => $rs->id,
                    'share_id' => $rs->share->id,
                    'type' => $rs->share->shareable_type,
                    'name' => $rs->share->shareable?->name,
                    'client_name' => $rs->share->shareable_type === 'projects'
                        ? $rs->share->shareable?->client?->name
                        : null,
                    'client_id' => $rs->share->shareable_type === 'projects'
                        ? $rs->share->shareable?->client?->id
                        : $rs->share->shareable?->id,
                    'daily_rate' => $rs->share->shareable?->daily_rate,
                    'description' => $rs->share->shareable_type === 'projects'
                        ? $rs->share->shareable?->description
                        : null,
                    'created_at' => $rs->share->shareable?->created_at,
                    'deleted_at' => $rs->share->shareable_type === 'projects'
                        ? $rs->share->shareable?->deleted_at
                        : null,
                    'shared_by' => $rs->share->shareable->user->name,
                ]),

            'search' => $search,
            'client_id' => $clientId,
            'with_trashed' => $withTrashed,
            'sort' => $sort,

            'shared_client_projects' => function () use ($request) {
                $clientShareIds = Share::whereIn('id', $request->user()->receivedShares()->select('share_id'))
                    ->where('shareable_type', 'clients')
                    ->pluck('shareable_id', 'id');

                if ($clientShareIds->isEmpty()) {
                    return collect();
                }

                $receivedShareIds = $request->user()->receivedShares()
                    ->whereIn('share_id', $clientShareIds->keys())
                    ->pluck('id', 'share_id');

                $clients = Client::with(['projects' => fn ($q) => $q->whereNull('deleted_at'), 'user'])
                    ->whereIn('id', $clientShareIds->values())
                    ->get()
                    ->keyBy('id');

                return $clientShareIds->flatMap(function (string $clientId, string $shareId) use ($clients, $receivedShareIds) {
                    $client = $clients->get($clientId);
                    if (! $client) {
                        return [];
                    }

                    return $client->projects->map(fn (Project $p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'description' => $p->description,
                        'daily_rate' => $p->daily_rate,
                        'created_at' => $p->created_at,
                        'deleted_at' => $p->deleted_at,
                        'client_id' => $client->id,
                        'client_name' => $client->name,
                        'share_id' => $shareId,
                        'shared_by' => $client->user->name,
                        'received_share_id' => $receivedShareIds->get($shareId),
                    ]);
                })->values();
            },
        ];
    }
}
