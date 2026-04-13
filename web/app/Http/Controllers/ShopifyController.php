<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ShopifyController extends Controller {

    public function index(Request $request) {
        $shop = $request->get('shop');
        $host = $request->get('host');

        if (!$shop) return "Please open this app from Shopify Admin.";

        // Check if we have this shop in our table
        $shopData = DB::table('shops')->where('shop', $shop)->first();

        if (!$shopData) {
            // WE MUST REDIRECT THE WHOLE PAGE (window.top)
            // This is the ONLY way to fix the "Missing Host" and "Null Shop" errors.
            $installUrl = url("/install?shop=$shop&host=$host");
            return "<script>window.top.location.href = '$installUrl';</script>";
        }

        return "Success! App is running for $shop. Data is in your shops table.";
    }

    public function install(Request $request) {
        $shop = $request->get('shop');
        
        $query = http_build_query([
            "client_id" => env('SHOPIFY_API_KEY'),
            "scope" => "read_products,write_products",
            "redirect_uri" => "https://unspongy-tawnya-noncontextually.ngrok-free.dev/callback",
        ]);

        return redirect("https://{$shop}/admin/oauth/authorize?" . $query);
    }

    public function callback(Request $request) {
        $shop = $request->get('shop');
        $code = $request->get('code');
        $host = $request->get('host');

        // Exchange code for token
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            "client_id" => env('SHOPIFY_API_KEY'),
            "client_secret" => env('SHOPIFY_API_SECRET'),
            "code" => $code
        ]);

        $data = $response->json();

        if (isset($data['access_token'])) {
            // SAVE TO YOUR TABLE
            DB::table('shops')->updateOrInsert(
                ['shop' => $shop],
                ['access_token' => $data['access_token'], 'updated_at' => now()]
            );

            // Redirect back to index with host
            return redirect('/?shop=' . $shop . '&host=' . $host);
        }

        return "OAuth Failed. Check your API Keys.";
    }
}
