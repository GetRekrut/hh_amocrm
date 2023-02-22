<?php

namespace App\Http\Controllers;

use App\Models\Access;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class AccessController extends Controller
{

    public $id_manager_admin = null;
    public $id_manager_vlad = null;

    public function getToken(Request $request){

        $request = $request->all();

        $account_info = Access::where('manager_id', $request['manager_id'])->first();
//        dd($account_info);

        try {

            $response = Http::withHeaders([
                'User-Agent' => 'amocrm-dev/1.0 (vladislav.fixnation@gmail.com)',
                'Content-Type' => 'application/x-www-form-urlencoded',
//                'Authorization' => 'Bearer '.$account_info->access_token
            ])
                ->asForm()
                ->post('https://hh.ru/oauth/token', [
                    'grant_type' => 'authorization_code',
                    'client_id' => $account_info->client_id,
                    'client_secret' => $account_info->client_secret,
                    'redirect_uri' => $account_info->redirect_uri,
                    'code' => $request['code'],
                ]);

            $tokens = json_decode($response->body(), true);

            if (!empty($tokens['access_token'])){

                $account_info->access_token = $tokens['access_token'];
                $account_info->refresh_token = $tokens['refresh_token'];
                $account_info->code = $request['code'];
                $account_info->save();
            }

        } catch (Exception $e){

            $error = $e->getMessage();
            $this->sendTelegram('Ошибка получение токена hh cour: '.$e->getLine().' - '. $e->getMessage());
            Log::warning('code: '.$request['code']);
        }
    }

    public function refreshToken(string $manager_id){

        $account_info = Access::where('manager_id', $manager_id)->first();

        try {

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

            if (!empty($tokens['access_token'])){

                $account_info->access_token = $tokens['access_token'];
                $account_info->refresh_token = $tokens['refresh_token'];
                $account_info->save();
            }

            Log::info($tokens);

        } catch (Exception $e){
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка обновления токена hh cour: '.$e->getLine().' - '. $e->getMessage());
        }
    }

    //получение токена учетки которая ответственная за вакансию, для авторизации и ответа с этой учетки
    public function getManagerToken($vacancy_id, $employer_id){

        try {

            // ищем доступ по employer_id, это что-то типа идентификатора кабинета
            $account_info = Access::where('employer_id', $employer_id)->first();

            if (!empty($vacancy_id)){

                $response = Http::withHeaders([
                    'HH-User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$account_info->access_token
                ])
                    ->get('https://api.hh.ru/vacancies/'.$vacancy_id);

                $response = json_decode($response->body(), true);
                
                $manager_id = $response['manager']['id'];

                $manager_info = Access::where('manager_id', $manager_id)->first();

                if (!empty($manager_info)) return $manager_info;

                else return false;
            }

        } catch (Exception $e){
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка получения токена менеджера hh cour: '.$e->getLine().' - '. $e->getMessage());
        }

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
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка авторизации hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        return $ufee;
    }
}
