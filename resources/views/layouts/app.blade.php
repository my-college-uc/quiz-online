<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — Quiz Online</title>
</head>
<body>
    <h1>@yield('title')</h1>
    <p><small>{{ Route::currentRouteName() }}</small></p>
    <hr>

    <main>
        @yield('content')
    </main>

    <hr>
    <nav>
        <strong>Navigasi:</strong>
        @yield('nav')
    </nav>
</body>
</html>
