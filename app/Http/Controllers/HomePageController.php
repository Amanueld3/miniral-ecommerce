<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class HomePageController
{
    public function getCategories()
    {
        return response()->json([
            'categories' => Category::all(),
        ]);
    }
}
