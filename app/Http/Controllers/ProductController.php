<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    //
    public function products(){
        $products = Product::All();
        return view('admin.products')->with('products',$products);
    }

    public function addproduct(){
        $categories=Category::All()->pluck('category_name','category_name');

        return view('admin.addproduct')->with('categories',$categories);
    }

    public function saveproduct(Request $request){
        $this->validate($request,['product_name'=> 'required',
                                'product_price'=> 'required',
                                'product_category'=> 'required',
                                'product_image'=> 'image|nullable|max:1999']);
        print($request->input('product_name'));

        if($request->hasFile('product_image')){
            //se obtiene el imagen
            $fileNamewithExt= $request->file('product_image')->getClientOriginalName();
              //se obtine el nombre del archivo
            $filename=pathinfo( $fileNamewithExt,PATHINFO_FILENAME);
             //extension del archivo
            $extension = $request->file('product_image')->getClientOriginalExtension();
            //nombre del archivo 
            $fileNameToStore=$filename.'_'.time().'.'.$extension;
            //upload imagen
            $path=$request->file('product_image')->storeAs('public/product_images',$fileNameToStore);

        }else{
            $fileNameToStore='noimage.jpg';
        }
        $product=new Product();
        $product->Product_name = $request->input('product_name');
        $product->Product_price= $request->input('product_price');
        $product->Product_category= $request->input('product_category');
        $product->Product_image=  $fileNameToStore;
        $product->status=1;

        $product->save();
        return back()->with('status','The product name has been successfully saved!!'); 
        
    }
    public function edit_product($id){

       $product=Product::find($id);
       
       $categories=Category::All()->pluck('category_name','category_name');

       return view('admin.editproduct')->with('product',$product)->with('categories',$categories);
    }
    public function updateproduct(Request $request){
        $this->validate($request,['product_name'=> 'required',
        'product_price'=> 'required',
        'product_category'=> 'required',
        'product_image'=> 'image|nullable|max:1999']);

        $product= Product::find($request->input('id'));
        $product->Product_name = $request->input('product_name');
        $product->Product_price= $request->input('product_price');
        $product->Product_category= $request->input('product_category');
     
        if($request->hasFile('product_image')){
            //se obtiene el imagen
            $fileNamewithExt= $request->file('product_image')->getClientOriginalName();
              //se obtine el nombre del archivo
            $filename=pathinfo( $fileNamewithExt,PATHINFO_FILENAME);
             //extension del archivo
            $extension = $request->file('product_image')->getClientOriginalExtension();
            //nombre del archivo 
            $fileNameToStore=$filename.'_'.time().'.'.$extension;
            //upload imagen
            $path=$request->file('product_image')->storeAs('public/product_images',$fileNameToStore);

            if($product->Product_image !='noimage.jpg'){
                Storage::delete('public/product_images/'.$product->product_image);
            }
            $product->Product_image=  $fileNameToStore;
        }

        
        $product->update();
        return redirect('products')->with('status','The product name has been successfully saved!! update'); 
        
    }

    public function delete_product($id){
        $product= Product::find($id);

        if($product->Product_image !='noimage.jpg'){
            Storage::delete('public/product_images/'.$product->product_image);
        }

        $product->delete();

        $product->update();
        return back()->with('status')->with('status','The product name has been successfully saved!! delete'); 
    }
  
    public function activate_product($id){

        $product=Product::find($id);
         
        $product->status=1;
        $product->update();

        return back()->with('status')->with('status','The product name has been successfully saved!! activate'); 



    }
    public function unactivate_product($id){
        $product=Product::find($id);
         
        $product->status=0;
        $product->update();

        return back()->with('status')->with('status','The product name has been successfully saved!! unactivate'); 
    }

    public function view_product_by_category($category_name){
        $products= Product::All()->where('Product_category',$category_name)->where('status',1);

        $categories = Category::All();


        return view('client.shop')->with('products',$products)->with('categories',$categories);
    }
}
