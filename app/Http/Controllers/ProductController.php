<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
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

    public function show(Product $product)
    {
        $product->load('variations'); // Ensure variations are loaded
        return Inertia::render('Product/Show', [
            // Wrap the product in a ProductResource to ensure a structured API Response
            'product' => new ProductResource($product),
            // Retrieve variation options from the request, defaulting to an empty array if none 
            'variationOptions' => request('options', [])
        ]);
    }
}
