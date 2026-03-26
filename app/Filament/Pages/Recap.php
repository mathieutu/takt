<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Recap extends Page
{
    protected string $view = 'filament.pages.recap';

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Récapitulatif';

    protected static ?string $title = 'Récapitulatif';

    protected ?string $heading = '';
}
