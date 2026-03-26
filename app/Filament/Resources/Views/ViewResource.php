<?php

namespace App\Filament\Resources\Views;

use App\Filament\Resources\Views\Pages\CreateView;
use App\Filament\Resources\Views\Pages\EditView;
use App\Filament\Resources\Views\Pages\ListViews;
use App\Filament\Resources\Views\Pages\ViewView;
use App\Filament\Resources\Views\Schemas\ViewForm;
use App\Filament\Resources\Views\Schemas\ViewInfolist;
use App\Filament\Resources\Views\Tables\ViewsTable;
use App\Models\View;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ViewResource extends Resource
{
    protected static ?string $model = View::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ViewForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ViewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListViews::route('/'),
            'create' => CreateView::route('/create'),
            'view' => ViewView::route('/{record}'),
            'edit' => EditView::route('/{record}/edit'),
        ];
    }
}
