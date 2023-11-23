<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillingQuota;
use App\Models\Token;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function importCSV(Request $request){
        $request->validate([
            'file' => 'required'
        ]);

        $file = $request->file('file');
        $fileContents = file($file->getPathname());

        foreach ($fileContents as $line) {
            $data = str_getcsv($line);
            if($data[0] != 'username'){
                error_log($data[0]);
            }
        }

        return response()->json([
            'status' => 'success'
        ], 201);
    }



    public function mainPage(Request $request){
        $items = Workspace::all()->where('user', $request->user()->id);

        return view('pages.main', [
            'authCheck' => Auth::check(),
            'workspaces' => $items
        ]);
    }
    public function loginPage(Request $request){
        if(Auth::check()){
            return redirect('/');
        }
        return view('pages.login', ['authCheck' => Auth::check()]);
    }
    public function registerPage(Request $request){
        if(Auth::check()){
            return redirect('/');
        }
        return view('pages.register', ['authCheck' => Auth::check()]);
    }

    public function login(Request $request){
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $cred = $request->only('username', 'password');
        error_log('start log');
        if(Auth::attempt($cred)){
            error_log('logged');
            return redirect('/');
        }
        error_log('error log');
        return redirect('/login');
    }

    public function register(Request $request){
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $exist_user = User::all()->where('username', $request->get('username'))->first();

        if($exist_user){
            return redirect('/register');
        }
        $new_user = new User();
        $new_user->username = $request->get('username');
        $new_user->password = Hash::make($request->get('password'));
        $new_user->save();

        $cred = $request->only('username', 'password');
        if(Auth::attempt($cred)){
            return redirect('/');
        }

        return redirect('/register');
    }


    public function logout(Request $request){
        Auth::logout();
        return redirect('/login');
    }










    public function createWorkspace(Request $request){
        $request->validate([
            'title' => 'required',
        ]);
        $new_workspace = new Workspace();
        $new_workspace->title = $request->get('title');
        if($request->get('description')){
            $new_workspace->description = $request->get('description');
        }
        $new_workspace->user = $request->user()->id;
        $new_workspace->save();

        return redirect('/');
    }


    public function workSpacePage(Request $request, Workspace $workspace){

        $tokens = Token::all()->where('workspace', $workspace->id);

        $months = [];
        $selectedDateTime = Carbon::parse($request->get('month', now()->toString()));

        $allBillings = Bill::query()->whereMonth('created_at', $selectedDateTime->month)->get();
        $tokenBillings = [];
        foreach ($tokens as $token){

            $billings = Bill::query()->whereMonth('created_at', $selectedDateTime->month)->where('token', $token->id)->get();

            if($billings->count() != 0){
                $tokenBillings[] = [
                    'token' => $token->name,
                    'billings' => $billings
                ];
            }
        }


        for ($i = 0; $i <= 10; $i++){
            $newDate = now()->subtract('month', $i);
            array_push($months, $newDate);
        }


        $totalCost = 0;
        foreach ($allBillings as $billing){
            $totalCost = $totalCost + $billing->total_cost;
        }


//        $nb1 = new Bill();
//        $nb1->token = $tokens[1]->id;
//        $nb1->price_per_second = 0.005;
//        $nb1->time_process = 9.4;
//        $nb1->total_cost = 9.4 * 0.005;
//        $nb1->created_at = $selectedDateTime;
//        $nb1->save();

        return view('pages.workspace_details', [
            'authCheck' => Auth::check(),
            'workspace' => $workspace,
            'tokens' => $tokens,
            'token_id' => Session::get('token_id'),
            'quota' => BillingQuota::all()->where('workspace', $workspace->id)->first(),
            'months' => $months,
            'tokenBillings' => $tokenBillings,
            'selectedDateTime' => $selectedDateTime,
            'totalForMonth' => $totalCost
        ]);
    }




    public function createToken(Request $request, Workspace $workspace){

        $request->validate([
            'name' => 'required'
        ]);
        $new_token = new Token();
        $new_token->name = $request->get('name');
        $new_token->token = Str::random(60);
        $new_token->workspace = $workspace->id;
        $new_token->save();

        return redirect()->back()->with('token_id', $new_token->id);
    }


    public function deactivateToken(Request $request, Token $token){

        $token->deactivated = true;
        $token->deactivated_at = now();
        $token->save();

        return redirect()->back();
    }

    public function activateToken(Request $request, Token $token){

        $token->deactivated = false;
        $token->deactivated_at = null;
        $token->save();

        return redirect()->back();
    }



    public function createQuota(Request $request, Workspace $workspace){
        $request->validate([
            'limit' => 'required'
        ]);
        $new_quota = new BillingQuota();
        $new_quota->limit = ((float)$request->get('limit'));
        $new_quota->user = $request->user()->id;
        $new_quota->workspace = $workspace->id;
        $new_quota->save();

        return redirect()->back();
    }

    public function deleteQuota(Request $request, Workspace $workspace){
        $quota = BillingQuota::all()->where('workspace', $workspace->id)->first();
        if($quota){
            $quota->delete();
        }
        return redirect()->back();
    }
}
