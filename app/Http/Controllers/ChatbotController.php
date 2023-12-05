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
        $product_ids = collect($product_ids)->map(function($x){ return $x->product_id; })->toArray();

        $product_ids = $this->validateProducts($product_ids,count($category_ids));

        $products = DB::table('products')
                    ->select('*')
                    ->whereIn('id',$product_ids)
                    ->get();
        $all_quoted = DB::table('quoted_products')
                    ->select('count(*)')
                    ->count();
                // dd($all_quoted);
        $products = $this->countPercentageBestSeller($products,$all_quoted);
        

        return json_encode($products);
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

    public function validateProducts($ids,$count){
        $arr = [];
        $tmp = array_count_values($ids);
        foreach($ids as $id){
            if($tmp[$id] == $count){
                array_push($arr, $id);
            }
        }
        return $arr;
    }

    public function getProducts(){
        $products = DB::table('products')
                    ->select('*')
                    ->get();
        return json_encode($products);
    }

    public function saveQuote(Request $request){
        // dd($request['products']);
        $products = $request['products'];
        // dd($products);
        $quoted = '';
        $counter = sizeof($products);
        try{
        foreach($products as $key => $product){
            // dd(gettype($product['id']));
            // array_push($quoted,
            //     "'product_id' => ".$product['id'].""
            // );
            // $quoted.="['product_id' => ".$product['id']."]";
            // if($key < ($counter-1)) $quoted .= ",";
            DB::table('quoted_products')
            ->insert([
               'product_id' => $product['id']
            ]);
        }
        // dd($quoted);
        return json_encode("Success");
        }catch(Error $er){
            dd($er);
        }catch(Exception $ex){
            dd($ex);
        }
        
    }

    private function countPercentageBestSeller($datas,$count){
        foreach($datas as $key => $data){
            // dd($data);
           $cnt =  DB::table('quoted_products')
                ->select('*')
                ->where('product_id', $data->id)
                ->count();
            $percent = $cnt / $count;
            if($percent > .3){
                $datas[$key]->label = 'hot';
            }
            $datas[$key]->percent = $percent;
            $trusted_brands = DB::table('trusted_brands')
                                ->select('name')
                                ->get();
            $trusted_brands = collect($trusted_brands)->map(function($x){ return $x->name; })->toArray();
            foreach($trusted_brands as $brand){
                if(str_contains(strtolower($data->name), $brand)){
                    $datas[$key]->trusted = true;
                }
            }
        }
        return $datas;
    }
}
