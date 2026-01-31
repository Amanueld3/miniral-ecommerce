<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\Products\ProductResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListProductActivities extends ListActivities
{
    protected static string $resource = ProductResource::class;
}
