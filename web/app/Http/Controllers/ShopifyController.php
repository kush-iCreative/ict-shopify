<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Shop;
use App\Models\InstallationData;

class ShopifyController extends Controller
{
    public function redirectToShopify(Request $request)
    {
        $shop = $request->get('shop');

        if (!$shop) {
            return redirect()->back()->with('error', 'Missing shop parameter.');
        }

        // Get credentials from .env
        $apiKey = env('SHOPIFY_API_KEY');
        $scopes = env('SHOPIFY_SCOPES', 'write_products,read_orders');
        $redirectUri = env('APP_URL') . '/auth/callback';

        $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => $apiKey,
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
            'grant_options[]' => 'per-user',
        ]);

        Log::info("Redirecting to Shopify install URL for shop: {$shop}");
        return redirect($installUrl);
    }

    public function handleCallback(Request $request)
    {
        $shop = $request->get('shop');
        $code = $request->get('code');
        $hmac = $request->get('hmac');
        $host = $request->get('host');

        if (!$shop || !$code || !$hmac) {
            Log::error('Missing required OAuth parameters', ['shop' => $shop, 'code' => $code]);
            abort(400, 'Missing required OAuth parameters.');
        }

        if (!$this->verifyHmac($request->all(), $hmac)) {
            Log::error('Invalid HMAC signature for shop: ' . $shop);
            abort(400, 'Invalid HMAC signature.');
        }

        try {
            $accessToken = $this->getAccessToken($shop, $code);
            
            // Get shop info from Shopify API
            $shopInfo = $this->getShopInfo($shop, $accessToken);

            // Store or update shop data in database
            $shopRecord = Shop::updateOrCreate(
                ['shop' => $shop],
                [
                    'access_token' => $accessToken,
                    'app_url' => env('APP_URL'),
                    'app_version' => env('SHOPIFY_APP_VERSION', '1.0.0'),
                    'installed_at' => now(),
                    'shop_info' => $shopInfo,
                    'metadata' => [
                        'installation_type' => 'oauth',
                        'api_version' => '2026-07',
                        'user_agent' => $request->userAgent(),
                        'ip_address' => $request->ip(),
                    ],
                    'status' => 'active',
                ]
            );

            // Record installation event
            InstallationData::create([
                'shop_id' => $shopRecord->id,
                'installation_event' => 'app_installed',
                'event_data' => [
                    'shop_domain' => $shop,
                    'shop_info' => $shopInfo,
                    'scopes' => explode(',', env('SHOPIFY_SCOPES')),
                    'timestamp' => now()->toIso8601String(),
                    'host' => $host,
                ],
                'processed_at' => now(),
            ]);

            Log::info("App successfully installed for shop: {$shop}");

            return redirect()->route('app', [
                'shop' => $shop,
                'host' => $host
            ]);

        } catch (\Exception $e) {
            Log::error("Installation failed for shop: {$shop}", ['error' => $e->getMessage()]);
            abort(500, 'Installation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get shop information from Shopify API
     */
    private function getShopInfo($shop, $accessToken)
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
            ])->get("https://{$shop}/admin/api/2026-07/shop.json");

            if ($response->successful()) {
                $shopData = $response->json('shop');
                return [
                    'name' => $shopData['name'] ?? null,
                    'email' => $shopData['email'] ?? null,
                    'phone' => $shopData['phone'] ?? null,
                    'country' => $shopData['country_code'] ?? null,
                    'currency' => $shopData['currency'] ?? null,
                    'timezone' => $shopData['timezone'] ?? null,
                    'plan' => $shopData['plan_display_name'] ?? null,
                    'shop_owner' => $shopData['shop_owner'] ?? null,
                    'myshopify_domain' => $shopData['myshopify_domain'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning("Could not fetch shop info for {$shop}: " . $e->getMessage());
        }

        return [];
    }

    private function verifyHmac($params, $hmac)
    {
        $paramsToVerify = $params;
        unset($paramsToVerify['hmac'], $paramsToVerify['signature']);
        ksort($paramsToVerify);

        $encodedParams = http_build_query($paramsToVerify);
        $computedHmac = hash_hmac('sha256', $encodedParams, env('SHOPIFY_API_SECRET'));
        
        return hash_equals($hmac, $computedHmac);
    }

    private function getAccessToken($shop, $code)
    {
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => env('SHOPIFY_API_KEY'),
            'client_secret' => env('SHOPIFY_API_SECRET'),
            'code' => $code,
        ]);

        if ($response->failed()) {
            Log::error("Failed to get access token for shop: {$shop}", ['response' => $response->json()]);
            throw new \Exception('Failed to get access token from Shopify');
        }

        return $response->json()['access_token'];
    }
}
