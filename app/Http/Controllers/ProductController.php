<?php

namespace App\Http\Controllers;

use App\Http\Resources\DepartmentResource;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Department;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function home(Request $request)
    {
        $keyword = $request->query('keyword');

        $products = Product::query()
            ->forWebsite()
            ->when($keyword, function ($query, $keyword) {
                // If a keyword exists, filter products by title or description matching the keyword
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'LIKE', "%{$keyword}%")
                          ->orWhere('description', 'LIKE', "%{$keyword}%");
                });
            })
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

    public function byDepartment(Request $request, Department $department)
    {
        // Abort with 404 if the department is not active
        abort_unless($department->active, 404);

        // Get the 'keyword' query parameter from the request (If any)
        $keyword = $request->query('keyword');
        
        // Build the query to fetch products belonging to the given department
        $products = Product::query()
                    ->forWebsite()
                    ->where('department_id', $department->id)
                    ->when($keyword, function ($query, $keyword) {
                        // If a keyword exists, filter products by title or description matching the keyword
                        $query->where(function ($query) use ($keyword) {
                            $query->where('title', 'LIKE', "%{$keyword}%")
                                  ->orWhere('description', 'LIKE', "%{$keyword}%");
                        });
                    })
                    ->paginate();
        // Return an Inertia response with the department info and the filtered products
        return Inertia::render("Department/Index", [
            'department' => new DepartmentResource($department),
            'products' => ProductListResource::collection($products),
        ]);

    }
}
