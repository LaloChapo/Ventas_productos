<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Category;
use Session;

class CategoryController extends Controller
{
    //
    public function addcategory(){
        return view('admin.addcategory');
    }
    /*public function categories(){
        return view('admin.categories');
    }*/ 
    public function savecategory(Request $request){
        $this->validate($request,['category_name'=>'required|unique:categories']);
        
        $category= new Category();
        $category->category_name=$request->input('category_name');
        $category->save();

        return back()->with('status','the category name has been succesfully save!!');

    }

    public function categories(){
        $categories=Category::All();

        return view('admin.categories')->with('categories',$categories);
    }

    public function edit_category(Request $request,$id){
       $category=Category::find($id);
       return view('admin.edit_category')->with('category',$category);
    }

    public function updatecategory(Request $request){

        $this->validate($request,['category_name'=>'required']);

        $category=Category::find($request->input('id'));

        $category->category_name = $request->input('category_name');

        $category->update();

        return redirect('/categories')->with('status','The category name has been successfully update!!');
    }

    public function delete_category($id){
       
        



      $category= Category::find($id);
      $category->delete();

      return back()->with('status','The category name has been successfully delete!!');


    }
}
