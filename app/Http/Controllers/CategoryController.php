<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Categories
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $categories = Category::all();

        return view(
            'categories.index',
            compact('categories')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Form
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view('categories.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Store Category
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([

            'name' =>
                'required|string|max:255'

        ]);

        Category::create([

            'name' =>
                $request->name,

            'description' =>
                $request->description

        ]);

        return redirect('/categories')
            ->with(
                'success',
                'Category created'
            );
    }
}