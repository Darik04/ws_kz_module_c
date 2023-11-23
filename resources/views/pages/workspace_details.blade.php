@extends('layout.main')

@section('title')
    Workspace - {{$workspace->title}}
@endsection
@section('content')
    <div class="container container-half">
        <main class="main">
            <table class="table border">
                <tr>
                    <th>Name</th>
                    <th>Token</th>
                    <th>Created at</th>
                    <th>Deactivated</th>
                </tr>
                @foreach($tokens as $token)
                    <tr>
                        <td>{{$token->name}}</td>
                        <td>
                            @if($token_id == $token->id)
                                {{$token->token}}
                            @else
                                hided
                            @endif
                        </td>
                        <td>{{$token->created_at}}</td>
                        @if($token->deactivated)
                            <td>
                                {{$token->deactivated_at}}
                                <form method="post" action="/token/{{$token->id}}/activate">
                                    @csrf
                                    <button class="btn btn-success" type="submit">Activate</button>
                                </form>
                            </td>

                        @else
                            <td>
                                <form method="post" action="/token/{{$token->id}}/deactivate">
                                    @csrf
                                    <button class="btn btn-danger" type="submit">Deactivate</button>
                                </form>
                            </td>
                        @endif

                    </tr>
                @endforeach
            </table>
            <h1>Create token</h1>
            <div class="content">
                <form method="post" action="/workspace/{{$workspace->id}}/create/token">
                    @csrf
                    <input name="name" type="text" required>
                    <button type="submit">Create</button>
                </form>
            </div>

            <h1>Billing Quota</h1>
            <div class="content">

                @if($quota)
                    <h2>
                        Limit: ${{$quota->limit}}
                    </h2>
                    <form method="post" action="/quota/{{$workspace->id}}/delete">
                        @csrf
                        <button class="btn btn-danger" type="submit">Delete quota</button>
                    </form>
                @else
                    <form method="post" action="/quota/{{$workspace->id}}/create">
                        @csrf
                        <input name="limit" type="text" required>
                        <button class="btn btn-success" type="submit">Create quota</button>
                    </form>
                @endif
            </div>
            <h1>Bills</h1>
            <form action="/workspace/{{$workspace->id}}" method="get">
                <select name="month">
                    @foreach($months as $m)
                        @if($selectedDateTime->month == $m->month)
                            <option selected value="{{$m}}">{{$m->format('Y M')}}</option>
                        @else
                            <option value="{{$m}}">{{$m->format('Y M')}}</option>
                        @endif
                    @endforeach
                </select>
                <button class="btn btn-success" type="submit">Filter</button>
            </form>
            <div class="content">
                <div class="content-row">
                    <h5>Token</h5>
                    <div class="d-flex">
                        <h5>Time</h5>
                        <h5>Per sec.</h5>
                        <h5>Total</h5>
                    </div>
                </div>
                <hr class="my-2">
                @foreach($tokenBillings as $tokenBilling)
                    <div class="content-item">
                        <h5>{{$tokenBilling['token']}}</h5>

                        @foreach($tokenBilling['billings'] as $billing)

                            <div class="content-row">
                                <p>Service #{{$billing->id}}</p>
                                <p>Token</p>
                                <div class="d-flex mb-2">
                                    <p>{{$billing->time_process}}</p>
                                    <p>{{$billing->price_per_second}}</p>
                                    <p>{{$billing->total_cost}}</p>
                                </div>
                            </div>
                        @endforeach

                    </div>
                @endforeach








                <div class="d-flex content-result">
                    <h4>Total:</h4>

                    @if($quota)
                        <h4>${{$quota->limit}}/${{$totalForMonth}}</h4>
                    @else
                        <h4>${{$totalForMonth}}</h4>
                    @endif
                </div>
            </div>
        </main>
    </div>
@endsection
