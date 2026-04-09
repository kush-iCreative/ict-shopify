<?php 
namespace App\Http\Controllers; 

use Illuminate\Http\Request; 
use App\Models\Setting; 

class SettingsController extends Controller 
{ 
    public function index(Request $request) 
{ 
    // Get the domain and clean it (remove slashes/https)
    $domain = $request->query('shop_domain'); 
    
    $settings = Setting::where('shop_domain', 'LIKE', '%' . $domain . '%')->get(); 

    // REMOVE ALL print_r() LINES. ONLY RETURN THE JSON.
    return response()->json($settings)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
}


    public function store(Request $request) 
    { 
        $domain = $request->shop_domain; 
        Setting::updateOrCreate( 
            ['shop_domain' => $domain], 
            ['value' => $request->setting_value] 
        ); 
        return response()->json(['message' => 'Success'], 200)
            ->header('Access-Control-Allow-Origin', '*');
    } 
}
