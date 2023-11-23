@extends('layout.main')

@section('title')
Login
@endsection
@section('content')
    <div class="main container container-half">
        <div class="content">
            <form action="/login" method="post">
                @csrf
                <input name="username" type="text" required />
                <input name="password" type="password" required />
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
@endsection
