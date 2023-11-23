<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Service - @yield('title')</title>

    <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
</head>
<body>
<header class="header">
    <div class="container d-flex h-content">
        <h1 class="h-h1">Billing Service</h1>
        @if($authCheck)
            <a href="/logout">Logout</a>
        @endif
    </div>
</header>
<div class="main container">
    <h1>@yield('title') -</h1>
</div>
@yield('content')


<script src="{{ asset('css/bootstrap.css') }}"></script>
</body>
</html>
