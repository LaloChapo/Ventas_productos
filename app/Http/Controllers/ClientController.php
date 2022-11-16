<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect; 
use App\Models\Slider;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Client;
use Srmklive\PayPal\Services\ExpressCheckout;


use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Arr;
use App\Mail\SendMail;

use App\Cart;
use Session;
use Stripe\Charge;
use Stripe\Stripe;

use Illuminate\Http\Request;


class ClientController extends Controller
{
    //
    public function home(){
        $sliders=slider::All()->where('status',1);

        $products= product::All()->where('status',1);

        return view('client.home')->with('sliders',$sliders)->with('products',$products);
    }
    public function shop(){
        $categories=Category::All();

        $products= product::All()->where('status',1);

        return view('client.shop')->with('categories',$categories)->with('products',$products);
    }
    public function addtocard($id){
        $product = Product::find($id);

        $oldCart = Session::has('cart')? Session::get('cart'):null;
        $cart = new Cart($oldCart);
        $cart->add($product, $id);
        Session::put('cart', $cart);

        //(Session::get('cart'));
        return back();

    }

    public function update_qty(Request $request, $id){
        $oldCart = Session::has('cart')? Session::get('cart'):null;
        $cart = new Cart($oldCart);
        $cart->updateQty($id, $request->quantity);
        Session::put('cart', $cart);

        //dd(Session::get('cart'));
        return back();
    }

    public function remove_from_cart($id){

        $oldCart = Session::has('cart')? Session::get('cart'):null;
        $cart = new Cart($oldCart);
        $cart->removeItem($id);
       
        if(count($cart->items) > 0){
            Session::put('cart', $cart);
        }
        else{
            Session::forget('cart');
        }

        //dd(Session::get('cart'));
        return back();

    }

    public function cart(){
        if(!Session::has('cart')){
            return view('client.cart');
        }
        $oldCart = Session::has('cart')? Session::get('cart'):null;
        $cart = new Cart($oldCart);


        return view('client.cart',['products' => $cart->items]);
    }
    public function checkout(){
        if(!Session::has('client')){
            return view('client.login');
        }

        if(!Session::has('cart')){
            return view('client.cart');
        }


        return view('client.checkout');
    }
    public function login(){
        return view('client.login');
    }

    public function logout(){
        Session::forget('client');
        return redirect('/shop');
    }

    public function signup(){
        return view('client.signup');
    }

    public function create_account(Request $request){
        $this->validate($request,['email'=>'email|required|unique:clients',
                                'password'=>'required|min:5']);

        $client = new Client();
        $client->email=$request->input('email');
        $client->password= bcrypt($request->input('password'));

        $client->save();

        return back()->with('status','your account has been seccessfully created!!!');

    }

    public function  access_account(Request $request){
        $this->validate($request,['email'=>'email|required',
        'password'=>'required']);

       $client = Client::where('email',$request->input('email'))->first();

        if ($client) {
            if (Hash::check($request->input('password'),$client->password)) {

                Session::put('client',$client);
                return redirect('/shop');
                
            } else {
                return back()->with('status','Wrong  email or password');
            }
            
           
        } else {
            return back()->with('status','you do not have an account with  this email');
        }
        
      
    }

    public function postcheckout(Request $request){
    try{
         $oldCart = Session::has('cart')? Session::get('cart'):null;
        $cart = new Cart($oldCart);
        
        
        
             Stripe::setApiKey('sk_test_gHGtV3Z6vh4jCV25BRju3hCv');

                try{

                    $charge = Charge::create(array(
                        "amount" => $cart->totalPrice * 100,
                        "currency" => "usd",
                        "source" => $request->input('stripeToken'), // obtainded with Stripe.js
                        "description" => "Test Charge"
                    ));

                

                } catch(\Exception $e){
                    Session::put('error', $e->getMessage());
                    return redirect::to('/checkout');
                }

        $payer_id= time();

        $order= new Order();
        $order->name= $request->input('name');
        $order->address= $request->input('address');
        $order->cart=serialize($cart);
        

        Session::put('order',  $order);


        $checkoutData = $this->checkoutData();

        $provider = new ExpressCheckout();

        $response = $provider->setExpressCheckout($checkoutData);

        return redirect($response['paypal_link']);

    }
    catch(\Exception $e){
        return redirect('/checkout')->with('error', $e->getMessage());
    }


    
    }


    private function checkoutData(){

        $oldCart = Session::has('cart')? Session::get('cart'):null;
            $cart = new Cart($oldCart);
    
            $data['items'] = [];
    
            foreach($cart->items as $item ){
                    $itemDetails=[
                    'name' => $item['product_name'],
                    'price' => $item['product_price'],
                    'qty' => $item['qty']
                    ];
    
                $data['items'][] = $itemDetails;
                
        }
    
            $checkoutData = [
                'items' => $data['items'],
                'return_url' => url('/paiement-success'),
                'cancel_url' => url('/checkout'),
                'invoice_id' => uniqid(),
                'invoice_description' => "order description",
                'total' => Session::get('cart')->totalPrice
            ];
    
            return $checkoutData;
        }


        public function paiement_success(Request $request){

            try{
                $token = $request->get('token');
                $payerId = $request->get('PayerID');
                $checkoutData = $this->checkoutData();
    
                $provider = new ExpressCheckout();
                $response = $provider->getExpressCheckoutDetails($token);
                $response = $provider->doExpressCheckoutPayment($checkoutData, $token, $payerId);
                 
                $payer_id = $payerId.'_'.time();

                Session::get('order')->payer_id  = $payer_id;

                Session::get('order')->save();
      


                    $orders= Order::where('payer_id',$payer_id)->get(); 

                    $orders->transform(function($order,$key){
                        $order->cart= unserialize($order->cart); 

                        return $order;
                    });
                    
                    $email=Session::get('client')->email;

                    Mail::to($email)->send(new SendMail($orders));
                    //
                    Session::forget('cart');

                    return redirect('/cart')->with('status', 'your purchase has been seccessfully accomplished !!'); 
            }       
            catch(\Exception $e){
                    return redirect('/checkout')->with('error', $e->getMessage());
                }
        }
    

    public function orders(){
       

        $orders= Order::All(); 
         $orders->transform(function($order,$key){
            $order->cart= unserialize($order->cart); 

            return $order;
        });     
       return view('admin.orders')-> with('orders',$orders);
    }
}
