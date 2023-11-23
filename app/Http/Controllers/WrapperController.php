<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\Token;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Helpers\MyAPIHelper;




class WrapperController extends Controller
{
    private $url = 'http://localhost:4043/api';
    public function createConversation(Request $request){
        $validator = Validator::make($request->all(), [
            'prompt' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ]);
        }

        if(!MyAPIHelper::checkToken($request)){
            return MyAPIHelper::getErrorResponse('401');
        }
        if(!MyAPIHelper::checkQuotaOFWorkspace($request)){
            return MyAPIHelper::getErrorResponse('403');
        }



        $prompt = $request->get('prompt');

        $generated_conversation_id = Str::random(50);
        $new_conversation = new Conversation();
        $new_conversation->conversation_id = $generated_conversation_id;
        $new_conversation->save();

        $client = new Client();
//        CREATE CONVERSATION
        try {
            $response1 = $client->post(
                ($this->url.'/conversation'),
                [
                    'form_params' => [
                        'conversationId' => $generated_conversation_id
                    ]
                ]
            );
        }catch (RequestException $requestException){
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }



//        SEND FIRST MESSAGE
        try {
            $response2 = $client->post(
                ($this->url.'/conversation/'.$generated_conversation_id),
                [
                    'form_params' => [
                        'text' => $prompt
                    ]
                ]
            );
        }catch (RequestException $requestException){
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }
        $datae = json_decode($response2->getBody()->getContents());
        return response()->json([
            'conversation_id' => $generated_conversation_id,
            'response' => $datae->response,
            'is_final' => $datae->is_final
        ]);
    }




    public function continueConversation(Request $request, Conversation $conversation){
        $validator = Validator::make($request->all(), [
            'prompt' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 400);
        }

        if(!MyAPIHelper::checkToken($request)){
            return MyAPIHelper::getErrorResponse('401');
        }

        if(!MyAPIHelper::checkQuotaOFWorkspace($request)){
            return MyAPIHelper::getErrorResponse('403');
        }


        $client = new Client();

        if(!$conversation->is_final){
            //       Get partial conversation response(Check state of last answer)
            try {
                $response1 = $client->get(
                    ($this->url.'/conversation/'.$conversation->conversation_id)
                );
            }catch (RequestException $requestException){
                if($requestException->hasResponse()){
                    return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
                }
            }

            $data1 = json_decode($response1->getBody()->getContents());

            if(str_contains($data1, '<EOF>')){//if - Generation completed
                $conversation->is_final = True;//Generation completed
                $conversation->save();
            }else{
                return MyAPIHelper::getErrorResponse('503');
            }
        }

        //        CONTINUE CONVERSATION
        try {
            $response2 = $client->post(
                ($this->url.'/conversation/'.$conversation->conversation_id),
                [
                    'form_params' => [
                        'text' => $request->get('prompt')
                    ]
                ]
            );
        }catch (RequestException $requestException){
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }
        // Generation answer started
        $conversation->is_final = False;
        $conversation->save();

        $data2 = json_decode($response2->getBody()->getContents());


        return response()->json([
            'conversation_id' => $conversation->conversation_id,
            'response' => $data2->response,
            'is_final' => $data2->is_final
        ]);
    }















    public function getPartialConversation(Request $request, Conversation $conversation)
    {
        if(!MyAPIHelper::checkToken($request)){
            return MyAPIHelper::getErrorResponse('401');
        }

        if(!MyAPIHelper::checkQuotaOFWorkspace($request)){
            return MyAPIHelper::getErrorResponse('403');
        }


        $client = new Client();

        //       Get partial conversation response(Check state of last answer)
        try {
            $response1 = $client->get(
                ($this->url.'/conversation/'.$conversation->conversation_id)
            );
        }catch (RequestException $requestException){
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }

        $dataString = json_decode($response1->getBody()->getContents());
        error_log($dataString);

        if(preg_match(MyAPIHelper::$patternOfEOF, $dataString, $matches)){//if - Generation completed(EOF contains)
            if($matches[1]){
                $milliseconds = ((int)$matches[1]);
                $seconds = $milliseconds / 1000;
                $exist_token = Token::query()->where('token', $request->header('x-api-token'))->first();

                $new_bill = new Bill();
                $new_bill->time_process = $seconds;
                $new_bill->total_cost = $seconds*0.005;
                $new_bill->token = $exist_token->id;
                $new_bill->save();


                $conversation->is_final = True;//Generation completed
                $conversation->save();

            }
        }


        return response()->json([
            'conversation_id' => $conversation->conversation_id,
            'response' => $dataString,
            'is_final' => $conversation->is_final
        ]);
    }








    public function generateImageBasedPrompt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text_prompt' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 400);
        }


        if (!MyAPIHelper::checkToken($request)) {
            return MyAPIHelper::getErrorResponse('401');
        }

        if (!MyAPIHelper::checkQuotaOFWorkspace($request)) {
            return MyAPIHelper::getErrorResponse('403');
        }
        $client = new Client();


        try {
            $response1 = $client->post(
                $this->url.'/generate',
                [
                    'form_params' => [
                        'text_prompt' => $request->get('text_prompt')
                    ]
                ]
            );
        }catch (RequestException $requestException){
            error_log($requestException->getResponse()->getStatusCode());
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }


        $data = json_decode($response1->getBody()->getContents());

        $new_job = new Job();
        $new_job->job_id = $data->job_id;
        $new_job->started_at = Carbon::parse($data->started_at);
        $new_job->save();

        return response()->json([
            'job_id' => $data->job_id
        ], 201);
    }












    public function getStatusJob(Request $request, Job $job)
    {
        if (!MyAPIHelper::checkToken($request)) {
            return MyAPIHelper::getErrorResponse('401');
        }

        if (!MyAPIHelper::checkQuotaOFWorkspace($request)) {
            return MyAPIHelper::getErrorResponse('403');
        }
        $client = new Client();


        try {
            $response1 = $client->get(
                $this->url.'/status/'.$job->job_id
            );
        }catch (RequestException $requestException){
            error_log($requestException->getResponse()->getStatusCode());
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }


        $data = json_decode($response1->getBody()->getContents());

        $job->preview_url = $data->image_url;

        //Save preview image and set to 'preview_local_url'...
        $imageContent = file_get_contents($data->image_url);
        $path = 'uploads/generation/'.Str::random(20).'_generated.jpg';
        Storage::disk('ws_public_uploads')->put($path, $imageContent);
        $job->preview_local_url = $path;

        if($data->progress == 100){
            $job->is_final = True;
        }

        $job->save();

        return response()->json([
            'status' => $data->status,
            'progress' => $data->progress,
            'image_url' => $data->image_url,
        ], 200);
    }
















    public function getResultJob(Request $request, Job $job)
    {
        if (!MyAPIHelper::checkToken($request)) {
            return MyAPIHelper::getErrorResponse('401');
        }

        if (!MyAPIHelper::checkQuotaOFWorkspace($request)) {
            return MyAPIHelper::getErrorResponse('403');
        }
        $client = new Client();


        try {
            $response1 = $client->get(
                $this->url.'/result/'.$job->job_id
            );
        }catch (RequestException $requestException){
            error_log($requestException->getResponse()->getStatusCode());
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }


        $data = json_decode($response1->getBody()->getContents());

        if($data->finished_at){
            $job->finished_at = Carbon::parse($data->finished_at);

            //Difference and generate new Billing
            $seconds = $job->finished_at->diff($job->started_at)->s;

            $exist_token = Token::query()->where('token', $request->header('x-api-token'))->first();

            $new_bill = new Bill();
            $new_bill->time_process = $seconds;
            $new_bill->total_cost = $seconds*0.005;
            $new_bill->token = $exist_token->id;
            $new_bill->save();

        }
        if($data->image_url){
            //Save main image and set to 'local_url'...
            $job->image_url = $data->image_url;
            $imageContent = file_get_contents($data->image_url);
            $path = 'uploads/generation/'.Str::random(20).'_generated.jpg';
            Storage::disk('ws_public_uploads')->put($path, $imageContent);
            $job->local_url = $path;
        }

        if($data->resource_id){
            $job->resource_id = $data->resource_id;
        }

        $job->save();

        return response()->json([
            'resource_id' => $data->resource_id,
            'image_url' => $data->image_url
        ], 200);
    }


    public function upscaleImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resource_id' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ]);
        }

        if (!MyAPIHelper::checkToken($request)) {
            return MyAPIHelper::getErrorResponse('401');
        }

        if (!MyAPIHelper::checkQuotaOFWorkspace($request)) {
            return MyAPIHelper::getErrorResponse('403');
        }
        $client = new Client();


        try {
            $response1 = $client->post(
                $this->url.'/upscale',
                [
                    'form_params' => [
                        'resource_id' => $request->get('resource_id')
                    ]
                ]
            );
        }catch (RequestException $requestException){
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }


        $data = json_decode($response1->getBody()->getContents());

        $new_job = new Job();
        $new_job->job_id = $data->job_id;
        $new_job->started_at = Carbon::parse($data->started_at);
        $new_job->save();

        return response()->json([
            'job_id' => $data->job_id
        ], 201);
    }





    public function zoomIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resource_id' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ]);
        }

        if (!MyAPIHelper::checkToken($request)) {
            return MyAPIHelper::getErrorResponse('401');
        }

        if (!MyAPIHelper::checkQuotaOFWorkspace($request)) {
            return MyAPIHelper::getErrorResponse('403');
        }
        $client = new Client();


        try {
            $response1 = $client->post(
                $this->url.'/zoom/in',
                [
                    'form_params' => [
                        'resource_id' => $request->get('resource_id')
                    ]
                ]
            );
        }catch (RequestException $requestException){
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }


        $data = json_decode($response1->getBody()->getContents());

        $new_job = new Job();
        $new_job->job_id = $data->job_id;
        $new_job->started_at = Carbon::parse($data->started_at);
        $new_job->save();

        return response()->json([
            'job_id' => $data->job_id
        ], 201);
    }




    public function zoomOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resource_id' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ]);
        }

        if (!MyAPIHelper::checkToken($request)) {
            return MyAPIHelper::getErrorResponse('401');
        }

        if (!MyAPIHelper::checkQuotaOFWorkspace($request)) {
            return MyAPIHelper::getErrorResponse('403');
        }
        $client = new Client();


        try {
            $response1 = $client->post(
                $this->url.'/zoom/out',
                [
                    'form_params' => [
                        'resource_id' => $request->get('resource_id')
                    ]
                ]
            );
        }catch (RequestException $requestException){
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }


        $data = json_decode($response1->getBody()->getContents());

        $new_job = new Job();
        $new_job->job_id = $data->job_id;
        $new_job->started_at = Carbon::parse($data->started_at);
        $new_job->save();

        return response()->json([
            'job_id' => $data->job_id
        ], 201);
    }













    public function imageRecognition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file'
        ]);
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ]);
        }

        if (!MyAPIHelper::checkToken($request)) {
            return MyAPIHelper::getErrorResponse('401');
        }

        if (!MyAPIHelper::checkQuotaOFWorkspace($request)) {
            return MyAPIHelper::getErrorResponse('403');
        }
        $client = new Client();

        $image = $request->file('image');

        $multipart = [];
        $multipart[] = [
            'name' => 'image',
            'contents' => fopen($image->getRealPath(), 'r'),
        ];

        $started_time = microtime(true);
        try {
            $response1 = $client->post(
                $this->url.'/recognize',
                [
                    'multipart' => $multipart
                ]
            );
        }catch (RequestException $requestException){
            if($requestException->hasResponse()){
                return MyAPIHelper::getErrorResponse($requestException->getResponse()->getStatusCode());
            }
        }
        $end_time = microtime(true);


        $seconds = round($end_time - $started_time, 3);
        $exist_token = Token::query()->where('token', $request->header('x-api-token'))->first();

        $new_bill = new Bill();
        $new_bill->time_process = $seconds;
        $new_bill->total_cost = $seconds*0.005;
        $new_bill->token = $exist_token->id;
        $new_bill->save();


        $data = json_decode($response1->getBody()->getContents());

        $custom_objects = [];

        $imagedata = getimagesize($image->path());
        $width = $imagedata[0];
        $height = $imagedata[1];

        foreach ($data->objects as $obj){
            $custom_objects[] = [
                'name' => $obj->label,
                'probability' => $obj->probability,
                'bounding_box' => [
                    //Думаю будет отсчет от верхнего левого угла(для фронта будет как минимум удобнее и быстрее)
                    'x' => $obj->bounding_box->left,
                    'y' => $obj->bounding_box->top,
                    'width' => ((int)$width) - $obj->bounding_box->left - $obj->bounding_box->right,
                    'height' => ((int)$height) - $obj->bounding_box->top - $obj->bounding_box->bottom,
                ]
            ];
        }

        return response()->json([
            'objects' => $custom_objects
        ]);
    }
}
