<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ReportEntry extends Page
{
    protected string $view = 'filament.pages.report-entry';

    protected static ?string $slug = 'cra';

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return null;
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
