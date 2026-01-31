<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\SpatieLaravelMediaLibraryPlugin\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn as ColumnsSpatieMediaLibraryImageColumn;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnsSpatieMediaLibraryImageColumn::make('thumbnail')
                    ->label('')
                    ->collection('thumbnail')
                    ->circular()
                    ->width(44),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->description(fn($record) => $record->slug, position: 'below'),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('location.name')
                    ->label('Location')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('price')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->separator(', ')
                    ->limitList(3)
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ((int) $state) {
                        0 => 'Draft',
                        1 => 'Active',
                        2 => 'Inactive',
                        default => 'Unknown',
                    })
                    ->color(fn($state) => match ((int) $state) {
                        0 => 'gray',
                        1 => 'success',
                        2 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->options([
                        0 => 'Draft',
                        1 => 'Active',
                        2 => 'Inactive',
                    ]),

                SelectFilter::make('tags')
                    ->label('Tag')
                    ->relationship('tags', 'name')
                    ->searchable()
                    ->preload(),

                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('activities')
                    ->icon('heroicon-o-clock')
                    ->url(fn($record) => ProductResource::getUrl('activities', ['record' => $record])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
