<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;


class ShopifyController extends Controller
{
    // 🔹 Auto check + install
    public function index(Request $request)
    {
        $shop = $request->get('shop');
        $host = $request->get('host');

        if (!$shop) {
            return "Shop missing";
        }


        // 🔥 Check DB
        $shopData = DB::table('shops')->where('shop', $shop)->first();


        // ❗ Not installed → redirect to install
        if (!$shopData) {
            return redirect('/install?shop=' . $shop . '&host=' . $host);
        }


        // ✅ Already installed
        return "App running 🎉";
    }


    // 🔹 Install (OAuth start)
    public function install(Request $request)
    {
        $shop = $request->get('shop');
        $host = $request->get('host');

        if (!$shop) {
            return "Shop missing";
        }


        $state = bin2hex(random_bytes(16));
        session(['state' => $state]);


         $redirectUri = "https://unspongy-tawnya-noncontextually.ngrok-free.dev/callback?host=" . $host;


        $query = http_build_query([
            "client_id" => env('SHOPIFY_API_KEY'),
            "scope" => "read_products,write_products",
            "redirect_uri" => $redirectUri,
            "state" => $state,
        ]);


        return redirect("https://{$shop}/admin/oauth/authorize?" . $query);
    }


    // 🔹 Callback (DB save)
    public function callback(Request $request)
    {
        $shop = $request->get('shop');
        $code = $request->get('code');
        $state = $request->get('state');
    $host = $request->get('host'); 

        if ($state !== session('state')) {
            return "Invalid state";
        }


        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            "client_id" => env('SHOPIFY_API_KEY'),
            "client_secret" => env('SHOPIFY_API_SECRET'),
            "code" => $code
        ]);


        $data = $response->json();


        if (!isset($data['access_token'])) {
            return "Token error";
        }


        $accessToken = $data['access_token'];


        // 🔥 DB SAVE
        DB::table('shops')->updateOrInsert(
            ['shop' => $shop],
            [
                'access_token' => $accessToken,
                'updated_at' => now()
            ]
        );


        // 🔥 Redirect back to app
        return redirect('/?shop=' . $shop . '&host=' . $host);
    }
}
