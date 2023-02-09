<?php

namespace App\Http\Controllers;

use App\Models\Access;
use Mockery\Exception;

class AccessController extends Controller
{

    public function getToken(){

        $account_info = Access::where('source_name', 'hh_t')->first();

        try {

            $response = Http::withHeaders([
                'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer '.$account_info->access_token
            ])
                ->asForm()
                ->post('https://hh.ru/oauth/token', [
                    'grant_type' => 'authorization_code',
                    'client_id' => $account_info->client_id,
                    'client_secret' => $account_info->client_secret,
                    'redirect_uri' => $account_info->redirect_uri,
                    'code' => $account_info->code,
                ]);

        } catch (Exception $e){

            $error = $e->getMessage();
            $this->sendTelegram('Ошибка получение токена hh cour: '.$error);
        }

        $tokens = json_decode($response->body(), true);
        var_dump($tokens);

        if (!empty($tokens['access_token'])){

            $account_info->access_token = $tokens['access_token'];
            $account_info->refresh_token = $tokens['refresh_token'];
            $account_info->save();
        }
    }

    public function refreshToken(){

        $account_info = Access::where('source_name', 'hh_t')->first();

        $response = Http::withHeaders([
            'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
            'Content-Type' => 'application/x-www-form-urlencoded',
//            'Authorization' => 'Bearer '.$account_info->access_token
        ])
            ->asForm()
            ->post('https://hh.ru/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $account_info->refresh_token,
            ]);

        $tokens = json_decode($response->body(), true);
        var_dump($tokens);

    }

    public function getKeys($source_name){

        $account_info = Access::where('source_name', $source_name)->first();

        return $account_info;

    }

    public function connectAmoCrm()
    {
        $ufee = \Ufee\Amo\Oauthapi::setInstance([
            'domain' => env('AMO_DOMAIN'),
            'client_id' => env('AMO_CLIENT_ID'),
            'client_secret' => env('AMO_CLIENT_SECRET'),
            'redirect_uri' => env('AMO_REDIRECT_URI'),
        ]);

        $ufee = \Ufee\Amo\Oauthapi::getInstance(env('AMO_CLIENT_ID'));

//        $ufee->fetchAccessToken(env('AMO_CODE'));

        try {
            $ufee->account;
        } catch (\Exception $exception) {
            var_dump($exception);
//            $error = $e->getMessage();
//            $this->sendTelegram('Ошибка авторизации tilda_courier: ' . $error);
        }

        return $ufee;
    }
}
