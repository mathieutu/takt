<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Export extends Page
{
    protected string $view = 'filament.pages.export';

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Export';

    protected static ?string $title = 'Export';

    protected ?string $heading = '';
}
