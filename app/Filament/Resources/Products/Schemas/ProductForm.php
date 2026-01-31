<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Tag;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic')
                ->columns(2)
                ->components([
                    Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('location_id')
                        ->relationship('location', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),

                    TextInput::make('slug')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->unique(ignoreRecord: true),


                    TextInput::make('price')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    TextInput::make('purity')
                        ->label('Purity (%)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->step(1)
                        ->default(100)
                        ->required()
                        ->rules(['integer', 'min:1', 'max:100']),

                    Select::make('status')
                        ->options([0 => 'Draft', 1 => 'Active', 2 => 'Inactive'])
                        ->default(0)
                        ->required(),

                    Select::make('tags')
                        ->label('Tags')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                            TextInput::make('slug')
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->unique(table: Tag::class, column: 'slug', ignoreRecord: true),
                        ])
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        // ->rows(5)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            // ✅ switched: Detail BEFORE Media
            Section::make('Detail (Key / Value)')
                ->components([
                    KeyValue::make('detail')
                        ->keyLabel('Key')
                        ->valueLabel('Value')
                        ->addActionLabel('Add detail')
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Media')
                ->columns(2)
                ->components([
                    SpatieMediaLibraryFileUpload::make('thumbnail')
                        ->disk('public')
                        ->collection('thumbnail')
                        ->image()
                        ->imageEditor()
                        ->maxSize(4096)
                        ->required(),

                    SpatieMediaLibraryFileUpload::make('images')
                        ->disk('public')
                        ->collection('images')
                        ->multiple()
                        ->image()
                        ->imageEditor()
                        ->reorderable()
                        ->maxSize(4096)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
