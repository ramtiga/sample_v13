<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>@yield('title', '所属マスタ管理')</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 1rem; }
        th, td { border: 1px solid #ccc; padding: 0.5rem; text-align: left; }
        .errors { color: #c00; margin-bottom: 1rem; }
        .actions a, .actions button { margin-right: 0.5rem; }
    </style>
</head>
<body>
    <main>
        @yield('content')
    </main>
</body>
</html>
