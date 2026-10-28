<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

use App\Models\ProductPrice;
use App\Models\Product;
use App\Http\Requests\StoreProductPriceRequest;
use App\Http\Requests\ProductIdRequest;

class ProductPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductIdRequest $request)
    {
        $product = Product::findOrFail(
            $request->validated('product_id')
        );

        return $product->prices()
            ->with('currency')
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductPriceRequest $request)
    {
        $validated = $request->validated();

        $product = Product::findOrFail(
            $validated['product_id']
        );

        $price = $product->prices()->create([
            'currency_id' => $validated['currency_id'],
            'price' => $validated['price'],
        ]);

        return response()->json(
            $price->load('currency'),
            HttpResponse::HTTP_CREATED
        );
    }
}
