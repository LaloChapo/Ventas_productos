<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Slider;
class SliderController extends Controller
{
    //
    public function addslider(){
        return view('admin.addslider');
    }
    public function sliders(){
        $sliders=Slider::All();
        return view('admin.sliders')->with('sliders',$sliders);
    }
    public function saveslider(Request $request){
                $this->validate($request,['description1'=> 'required',
                                        'description2'=> 'required',
                                        'slider_image'=> 'image|nullable|max:1999|required']);
        print($request->input('slider_name'));

     
        //se obtiene el imagen
        $fileNamewithExt= $request->file('slider_image')->getClientOriginalName();
        //se obtine el nombre del archivo
        $filename=pathinfo( $fileNamewithExt,PATHINFO_FILENAME);
        //extension del archivo
        $extension = $request->file('slider_image')->getClientOriginalExtension();
        //nombre del archivo 
        $fileNameToStore=$filename.'_'.time().'.'.$extension;
        //upload imagen
        $path=$request->file('slider_image')->storeAs('public/silder_images',$fileNameToStore);

        
        $slider=new Slider();
        $slider->description1 = $request->input('description1');
        $slider->description2= $request->input('description2');
        $slider->slider_image=  $fileNameToStore;
        $slider->status=1;

        $slider->save();
        return back()->with('status','The slider name has been successfully saved!!'); 

    }
    public function edit_slider($id){
    $slider=Slider::find($id);

    return view('admin.edit_slider')->with('slider',$slider);
    }

    public function updateslider(Request $request){
        $this->validate($request,['description1'=> 'required',
                                'description2'=> 'required',
                                'slider_image'=> 'image|nullable|max:1999']);

        $slider= Slider::find($request->input('id'));
        $slider->description1 = $request->input('description1');
        $slider->description2= $request->input('description2');
     
     
        if($request->hasFile('slider_image')){
            //se obtiene el imagen
            $fileNamewithExt= $request->file('slider_image')->getClientOriginalName();
              //se obtine el nombre del archivo
            $filename=pathinfo( $fileNamewithExt,PATHINFO_FILENAME);
             //extension del archivo
            $extension = $request->file('slider_image')->getClientOriginalExtension();
            //nombre del archivo 
            $fileNameToStore=$filename.'_'.time().'.'.$extension;
            //upload imagen
            $path=$request->file('slider_image')->storeAs('public/silder_images',$fileNameToStore);

           
                Storage::delete('public/silder_images/'.$slider->slider_image);
            
            $slider->slider_image=  $fileNameToStore;
        }

        
        $slider->update();
        return redirect('sliders')->with('status','The sliders name has been successfully saved!! update'); 
    }
    
    public function delete_slider($id){


        $slider= Slider::find($id);
        
            Storage::delete('public/silder_images/'.$slider->slider_image);
        

        $slider->delete();

        return back()->with('status')->with('status','The slider name has been successfully saved!! delete'); 

    }

    public function activate_slider ($id){
        $slider=Slider::find($id);
         
        $slider->status=1;
        $slider->update();

        return back()->with('status')->with('status','The slider name has been successfully saved!! activate'); 

    }

    public function unactivate_slider ($id){
        $slider=Slider::find($id);
         
        $slider->status=0;
        $slider->update();

        return back()->with('status')->with('status','The product name has been successfully saved!! unactivate'); 
    }
}
