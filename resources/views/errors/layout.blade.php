{{--
    Error pages are self-contained: no Vite assets, database queries or user
    data, so they still render when something else has gone wrong. They show
    a short message only; details go to the log, never to the browser.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f4f6f9; color: #1f2937; }
        main { max-width: 28rem; padding: 2rem; text-align: center; }
        .code { font-size: 3rem; font-weight: 700; color: #1f5fbf; margin: 0; }
        h1 { font-size: 1.25rem; margin: .5rem 0; }
        p { color: #4b5563; line-height: 1.5; }
        a { color: #1f5fbf; }
    </style>
</head>
<body>
    <main>
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <p><a href="{{ url('/') }}">Go to the dashboard</a></p>
    </main>
</body>
</html>
