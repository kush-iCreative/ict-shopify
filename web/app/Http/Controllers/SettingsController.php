<?php 
namespace App\Http\Controllers; 

use Illuminate\Http\Request; 
use App\Models\Setting; 

class SettingsController extends Controller 
{ 
  public function index(Request $request) 
{
    $domain = $request->query('shop_domain');
    $settings = Setting::where('shop_domain', 'LIKE', '%' . $domain . '%')->get();

    return response()->json($settings)
        ->header('Access-Control-Allow-Origin', '*') 
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, ngrok-skip-browser-warning');
}


    public function store(Request $request) 
    { 
        // 1. Get domain from session (more secure) or request
        $session = $request->get('shopifySession');
        $shopDomain = $session ? $session->getShop() : $request->shop_domain;

        // 2. Use create() to always insert a NEW record (no overriding)
        Setting::create([
            'shop_domain' => $shopDomain,
            'value'       => $request->setting_value
        ]); 

        return response()->json(['message' => 'New record created successfully'], 200);
    } 
}
