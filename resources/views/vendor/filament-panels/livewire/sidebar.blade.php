@php
    $account = filament()->auth()->user();
    $sidebarProps = [
        'user' => [
            'name'  => $account ? $account->getFilamentName() : '',
            'email' => $account?->email ?? '',
            'role'  => $account ? ucfirst($account->type->value) : '',
        ],
        'currentPath' => request()->getPathInfo(),
    ];
@endphp

<div>
    <aside
        x-data="{}"
        x-cloak="-lg"
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }"
        class="fi-sidebar fi-main-sidebar"
        style="min-height: 100vh;"
    >
        <div id="vue-sidebar" data-props="{{ json_encode($sidebarProps) }}" style="height: 100%; min-height: 100vh;"></div>
    </aside>

    <x-filament-actions::modals />
</div>
