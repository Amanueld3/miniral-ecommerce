<?php

namespace App\Filament\Resources\Categories\Schemas;

use Dom\Text;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        $set('slug', Str::slug($state ?? ''));
                    }),

                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Image')
                    ->collection('categories')
                    // ->disk('public')
                    ->image()
                    ->nullable(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->disabled()
                    ->dehydrated() // still save it even though it's disabled
                    ->required()
                    ->unique(ignoreRecord: true),
                RichEditor::make('description')
                    ->label('Description')
                    ->nullable()
                    ->maxLength(65535)
                    ->columnSpanFull()
            ]);
    }
}
