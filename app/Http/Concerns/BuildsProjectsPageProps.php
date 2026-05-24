<?php

namespace App\Http\Concerns;

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
            'projects' => function () use ($request, $search, $clientId, $withTrashed, $sort) {
                $query = $request->user()->projects()->withExists('sharer');

                if ($withTrashed) {
                    $query->withTrashed();
                }

                if ($search) {
                    $query->where(fn ($q) => (
                        $q->where('projects.name', 'like', "%{$search}%")
                            ->orWhereRelation('client', 'clients.name', 'like', "%{$search}%")
                    ));
                }

                if ($clientId) {
                    $query->where('client_id', $clientId);
                }

                $query->orderBy(
                    str_starts_with($sort, 'rate') ? 'projects.daily_rate' : 'projects.created_at',
                    str_ends_with($sort, 'asc') ? 'asc' : 'desc',
                );

                return $query->get()->map->export([
                    'id',
                    'name',
                    'description',
                    'daily_rate',
                    'max_budget',
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

                return $query->get()->map->export([
                    'id',
                    'name',
                    'daily_rate',
                    'created_at',
                    'deleted_at',
                ]);
            },
            'search' => $search,
            'client_id' => $clientId,
            'with_trashed' => $withTrashed,
            'sort' => $sort,
        ];
    }
}
