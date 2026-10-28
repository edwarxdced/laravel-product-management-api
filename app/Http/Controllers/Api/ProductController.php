<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use  Symfony\Component\HttpFoundation\Response as HttpResponse;

use App\Models\Product;


use App\Http\Requests\PaginationRequest;
use App\Http\Requests\StoreProductRequest;  
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\ProductIdRequest;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PaginationRequest $request)
    {
        return Product::with('currency')
            ->paginate($request->per_page());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        $product = Product::create($validatedData);

        return response()->json($product, HttpResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductIdRequest $request)
    {
        return Product::with(['currency', 'prices.currency'])
            ->findOrFail($request->validated('product_id'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request)
    {
        $validatedData = $request->validated();

        $product = Product::findOrFail($validatedData['product_id']);
        unset($validatedData['product_id']);
        $product->update($validatedData);

        return response()->json($product, HttpResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductIdRequest $request)
    {
        $product = Product::findOrFail($request->validated('product_id'));
        $product->delete();

        return response()->noContent();
    }
}
