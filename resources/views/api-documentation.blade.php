<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Interactive OpenAPI documentation for the IOMP platform API.">
    <title>IOMP API documentation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:600,700,800" rel="stylesheet">
    @vite('resources/js/swagger.js')
    <style>
        :root { color-scheme: light; --api-green: #075b3b; --api-green-deep: #043824; --api-gold: #e7b51d; --api-ink: #14231d; --api-muted: #65736d; --api-line: #dce4df; --api-page: #f4f7f5; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--api-page); color: var(--api-ink); font-family: 'DM Sans', sans-serif; letter-spacing: 0; }
        .api-doc-header { position: sticky; z-index: 20; top: 0; border-bottom: 1px solid rgba(255,255,255,.14); background: var(--api-green-deep); color: #fff; box-shadow: 0 8px 24px rgba(4,56,36,.16); }
        .api-doc-header__inner { width: min(100% - 2rem, 1400px); min-height: 68px; display: flex; align-items: center; gap: 1rem; margin-inline: auto; }
        .api-doc-brand { display: flex; align-items: center; gap: .75rem; min-width: 0; }
        .api-doc-brand__mark { width: 40px; height: 40px; display: grid; place-items: center; flex: 0 0 auto; border: 2px solid rgba(255,255,255,.5); border-radius: 6px; background: var(--api-green); color: #fff; font-family: 'Manrope', sans-serif; font-size: .65rem; font-weight: 800; }
        .api-doc-brand__text { display: grid; line-height: 1.15; }
        .api-doc-brand__text strong { font-family: 'Manrope', sans-serif; font-size: 1rem; }
        .api-doc-brand__text span { margin-top: .2rem; color: #bcd4c8; font-size: .7rem; }
        .api-doc-version { margin-left: auto; padding: .3rem .55rem; border: 1px solid rgba(255,255,255,.22); border-radius: 4px; color: #dfece6; font-size: .7rem; font-weight: 700; }
        .api-doc-header a { min-height: 38px; display: inline-flex; align-items: center; padding: .45rem .75rem; border: 1px solid var(--api-gold); border-radius: 4px; color: #ffe18a; font-size: .76rem; font-weight: 700; text-decoration: none; }
        .api-doc-header a:hover { background: var(--api-gold); color: #16251e; }
        .api-doc-loading { width: min(100% - 2rem, 1400px); margin: 1.5rem auto 0; color: var(--api-muted); font-size: .84rem; }
        .swagger-ready .api-doc-loading { display: none; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui { color: var(--api-ink); font-family: 'DM Sans', sans-serif; }
        .swagger-ui .wrapper { max-width: 1400px; padding: 0 1rem 3rem; }
        .swagger-ui .info { margin: 2.25rem 0 1.5rem; }
        .swagger-ui .info .title, .swagger-ui .opblock-tag { color: var(--api-ink); font-family: 'Manrope', sans-serif; letter-spacing: 0; }
        .swagger-ui .info .title { font-size: 2rem; }
        .swagger-ui .info .title small { top: -3px; border-radius: 3px; background: var(--api-green); }
        .swagger-ui .info p, .swagger-ui .info li, .swagger-ui .renderedMarkdown p, .swagger-ui .response-col_description { color: var(--api-muted); font-family: 'DM Sans', sans-serif; }
        .swagger-ui .scheme-container { margin: 0 0 1.5rem; padding: 1rem 0; border-block: 1px solid var(--api-line); background: #fff; box-shadow: none; }
        .swagger-ui .auth-wrapper .authorize { border-color: var(--api-green); border-radius: 4px; color: var(--api-green); }
        .swagger-ui .auth-wrapper .authorize svg { fill: var(--api-green); }
        .swagger-ui .filter-container { margin: 0 0 1.2rem; padding: 0; }
        .swagger-ui .filter .operation-filter-input { max-width: 440px; padding: .75rem .9rem; border: 1px solid #c8d5ce; border-radius: 4px; background: #fff; font-family: 'DM Sans', sans-serif; }
        .swagger-ui .opblock-tag { margin: 0 0 .5rem; border-bottom-color: var(--api-line); font-size: 1.25rem; }
        .swagger-ui .opblock { margin: 0 0 .75rem; border-radius: 4px; box-shadow: none; }
        .swagger-ui .opblock .opblock-summary { min-height: 52px; }
        .swagger-ui .opblock .opblock-summary-method { min-width: 76px; border-radius: 3px; font-family: 'Manrope', sans-serif; }
        .swagger-ui .opblock .opblock-summary-path, .swagger-ui .opblock .opblock-summary-description, .swagger-ui button, .swagger-ui input, .swagger-ui select, .swagger-ui textarea { font-family: 'DM Sans', sans-serif; }
        .swagger-ui .opblock.opblock-get { border-color: #2383a3; background: rgba(35,131,163,.055); }
        .swagger-ui .opblock.opblock-post { border-color: var(--api-green); background: rgba(7,91,59,.055); }
        .swagger-ui .opblock.opblock-put { border-color: #a36f13; background: rgba(163,111,19,.055); }
        .swagger-ui .btn { border-radius: 4px; box-shadow: none; }
        .swagger-ui .btn.execute { border-color: var(--api-green); background: var(--api-green); }
        .swagger-ui table tbody tr td { padding: .75rem 0; }
        .swagger-ui .model-box, .swagger-ui section.models { border-radius: 4px; background: #edf3ef; }
        .swagger-ui .highlight-code, .swagger-ui .microlight { border-radius: 4px; }
        .swagger-ui .responses-inner { padding: 1.25rem; }
        @media (max-width: 680px) {
            .api-doc-header__inner { min-height: 60px; }
            .api-doc-brand__text span, .api-doc-version { display: none; }
            .api-doc-header a { margin-left: auto; }
            .swagger-ui .info { margin-top: 1.5rem; }
            .swagger-ui .info .title { font-size: 1.55rem; }
            .swagger-ui .opblock .opblock-summary { align-items: flex-start; }
            .swagger-ui .opblock .opblock-summary-path { max-width: 100%; overflow-wrap: anywhere; }
            .swagger-ui .opblock .opblock-summary-description { display: none; }
            .swagger-ui .responses-inner { padding: 1rem .75rem; }
            .swagger-ui .responses-table { width: 100%; table-layout: fixed; padding: 0; }
            .swagger-ui .responses-table .response-col_status { width: 44px; }
            .swagger-ui .responses-table .response-col_description { width: auto; overflow-wrap: anywhere; }
            .swagger-ui .responses-table .response-col_links { display: none; }
            .swagger-ui .responses-table pre { max-width: 100%; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <header class="api-doc-header">
        <div class="api-doc-header__inner">
            <div class="api-doc-brand">
                <span class="api-doc-brand__mark" aria-hidden="true">IOMP</span>
                <span class="api-doc-brand__text"><strong>Developer API</strong><span>Investment Opportunities Management Platform</span></span>
            </div>
            <span class="api-doc-version">OpenAPI 3.1 &middot; v1</span>
            <a href="{{ route('home') }}">Return to platform</a>
        </div>
    </header>
    <p class="api-doc-loading" role="status">Loading API reference&hellip;</p>
    <div id="swagger-ui"></div>
</body>
</html>
