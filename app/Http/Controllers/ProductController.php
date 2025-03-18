<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductListResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function home()
    {
        $products = Product::query()
            ->published()
            ->paginate(12);
        // Render the 'Home' page with paginated product list
        return Inertia::render('Home', [
            'products' => ProductListResource::collection($products)
        ]);
    }

    public function show()
    {
        
    }
}
