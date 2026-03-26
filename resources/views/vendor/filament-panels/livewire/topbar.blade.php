@php
    $account = filament()->auth()->user();

    $activeLabel = collect(filament()->getNavigation())
        ->flatMap(fn (\Filament\Navigation\NavigationGroup $group) => $group->getItems())
        ->first(fn (\Filament\Navigation\NavigationItem $item) => $item->isActive())
        ?->getLabel() ?? '';

    $headerProps = [
        'title' => $activeLabel,
        'user'  => [
            'name' => $account ? $account->getFilamentName() : '',
            'role' => $account ? ucfirst($account->type->value) : '',
        ],
    ];
@endphp

<div>
    <div id="vue-header" data-props="{{ json_encode($headerProps) }}"></div>
    <x-filament-actions::modals />
</div>
