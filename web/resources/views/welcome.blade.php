<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- ✅ Correct App Bridge CDN URL -->
    <script src="https://shopify.com" 
            data-api-key="{{ env('SHOPIFY_API_KEY') }}"></script>

    <title>My Shopify App</title>
</head>

<body>
    <h1>App running 🎉</h1>
    <p>Shop: {{ request('shop') }}</p>
    
    <!-- 2. The host param is automatically handled by the script above -->
</body>
</html>
