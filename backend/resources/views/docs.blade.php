<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Todo API — Documentation</title>
    <link rel="icon" href="data:,">
    <style>
        body { margin: 0; padding: 0; }
    </style>
</head>
<body>
    {{-- Renders the canonical docs/openapi.yaml served by the /api/openapi.yaml route. --}}
    <redoc spec-url="{{ url('api/openapi.yaml') }}"></redoc>
    {{-- Redoc bundle is vendored locally (public/vendor/redoc) so the page works offline. --}}
    <script src="{{ asset('vendor/redoc/redoc.standalone.js') }}"></script>
</body>
</html>
