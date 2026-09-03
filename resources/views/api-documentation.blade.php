<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Interactive OpenAPI documentation for the IOMP platform API.">
    <title>IOMP API documentation</title>
    @vite('resources/js/swagger.js')
    <style>
        body { margin: 0; background: #f4f7f5; }
        .api-doc-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1.5rem; background: #063f2b; color: #fff; font: 600 14px sans-serif; }
        .api-doc-header strong { font-size: 16px; }
        .api-doc-header a { color: #f0c341; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { margin: 32px 0 20px; }
    </style>
</head>
<body>
    <header class="api-doc-header"><strong>IOMP API</strong><span>OpenAPI 3.1 &middot; Version 1</span><a href="{{ route('home') }}">Return to platform</a></header>
    <div id="swagger-ui"></div>
</body>
</html>
