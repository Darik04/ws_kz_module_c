@extends('layout.main')

@section('title')
Register
@endsection
@section('content')
    <div class="main container container-half">
        <div class="content">
            <form action="/register" method="post">
                @csrf
                <input name="username" type="text" required />
                <input name="password" type="password" required />
                <button type="submit">Register</button>
            </form>
        </div>
    </div>
@endsection
