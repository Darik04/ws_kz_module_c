<?php

namespace App\Http\Helpers;


use App\Models\Bill;
use App\Models\BillingQuota;
use App\Models\Token;
use App\Models\Workspace;
use Illuminate\Http\Request;

class MyAPIHelper{

    public static $patternOfEOF = '/<EOF> Заняло (\d+) мс/';
    public static function getErrorResponse(string $status){
        return response()->json(MyAPIHelper::getResponseDataByStatus($status), $status);
    }


    public static function getResponseDataByStatus(string $status){
        if($status == '400'){
            return [
                "type" => "/problem/types/400",
                "title" => "Bad Request",
                "status" => 400,
                "detail" => "The request is invalid."
            ];
        }elseif($status == '401'){
            return [
                "type" => "/problem/types/401",
                "title" => "Unauthorized",
                "status" => 401,
                "detail" => "The header X-API-TOKEN is missing or invalid."
            ];
        }elseif($status == '403'){
            return [
                "type" => "/problem/types/403",
                "title" => "Quota Exceeded",
                "status" => 403,
                "detail" => "You have exceeded your quota."
            ];
        }elseif($status == '404'){
            return [
                "type" => "/problem/types/404",
                "title" => "Service Unavailable",
                "status" => 404,
                "detail" => "The requested resource was not found."
            ];
        }else{
            return [
                "type" => "/problem/types/503",
                "title" => "Service Unavailable",
                "status" => 503,
                "detail" => "The service is currently unavailable."
            ];
        }
    }


    public static function checkToken(Request $request){
        $token = $request->header('x-api-token');
        $exist_token = Token::query()->where('token', $token)->first();
        return $exist_token;
    }

    public static function checkQuotaOFWorkspace(Request $request){
        $token = Token::query()->where('token', $request->header('x-api-token'))->first();
        $workspace = Workspace::query()->where('id', $token->workspace)->first();
        $billing_quota = BillingQuota::query()->where('workspace', $workspace->id)->first();
        if($billing_quota){
            $bills_for_month = Bill::query()->whereMonth('created_at', now()->month)->get();
            $total = 0;
            foreach ($bills_for_month as $billing){
                $total = $total + $billing->total_cost;
            }
            if($total >= $billing_quota->limit){
                return False;
            }
        }
        return True;
    }

}
