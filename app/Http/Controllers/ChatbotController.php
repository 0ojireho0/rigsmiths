<?php

namespace App\Http\Controllers;

use App\Models\products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\DB;

class ChatbotController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer 3YCQX37KSDKCBQE6SSSKUMOOIUFIZHNO',
        ])->get('https://api.wit.ai/message', [
            'q' => $request['q'],
        ]);
        // $reponse = Http::withUrlParameters([
        //     'endpoint' => 'https://api.wit.ai/message',
        // ])->get('{+endpoint}/{page}/{version}/{topic}');
        // return json_decode($response);

        $entities = $this->extractEntities($response);

        $category_ids = DB::table('categories')
                        ->select('id')
                        ->whereIn('category_name', $entities)
                        ->get();
        // $category_ids = array_map(function ($value) {
        //     return (array)$value;
        // }, $category_ids);
        
        // dd();
        $category_ids = collect($category_ids)->map(function($x){ return (array) $x; })->toArray();
        $product_ids = DB::table('product_categories')
                        ->select('product_id')
                        ->whereIn('category_id',$category_ids)
                        ->get();
        $product_ids = collect($product_ids)->map(function($x){ return (array) $x; })->toArray();
        $products = DB::table('products')
                    ->select('*')
                    ->whereIn('id',$product_ids)
                    ->get();
        return json_encode(['products'=>$products,'categories'=>$entities]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\products  $products
     * @return \Illuminate\Http\Response
     */
    public function show(products $products)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\products  $products
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, products $products)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\products  $products
     * @return \Illuminate\Http\Response
     */
    public function destroy(products $products)
    {
        //
    }

    public function extractEntities($response){
        $arr = [];
        foreach($response['entities'] as $key => $entity){
            if($entity[0]['confidence'] >= .9){
                array_push($arr, $entity[0]['name']);
            }
        }
        return $arr;
    }
}
