@extends('layout.main')

@section('title')
Workspaces
@endsection
@section('content')
    <div class="container">


        @if($workspaces->count() == 0)
            <h1>Not workspaces</h1>
        @endif
        @foreach($workspaces as $itm)

        <a href="/workspace/{{$itm->id}}">
            <div class="content">
                <h2>{{$itm->title}}</h2>
                <h2>{{$itm->description}}</h2>
                @if($itm->limit)
                    <h2>Quota Limit: {{$itm->limit->limit}}</h2>
                @endif
            </div>
        </a>
        @endforeach


            <h1>Create workspace</h1>
        <div class="content">
            <form method="post" action="/workspace/create">
                @csrf
                <input name="title" type="text" required>
                <input name="description" type="text">
                <button type="submit">Create</button>
            </form>
        </div>
    </div>
@endsection
