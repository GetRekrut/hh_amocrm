<?php

namespace App\Http\Controllers;

use App\Models\Access;
use Illuminate\Http\Request;

class HookController extends AccessController
{
    //подписка на хуки
    public function setWebHook(){

        $account_info = Access::where('source_name', 'hh_t')->first();

        $response = Http::withHeaders([
            'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$account_info->access_token
        ])
            ->post('https://api.hh.ru/webhook/subscriptions', [
                'actions' => [['type' => 'NEW_NEGOTIATION_VACANCY']], //новый отклик на вакансию
                'url' => 'https://webhook.site/06053974-b6af-4afd-81b6-ef7f23236a4a'
            ]);

        var_dump($response->body());
    }

    //поверить хуки
    public function checkWebHook(){

        $account_info = Access::where('source_name', 'hh_t')->first();

        $response = Http::withHeaders([
            'HH-User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$account_info->access_token
        ])
            ->get('https://api.hh.ru/webhook/subscriptions', [
                'locale' => 'RU', //новый отклик на вакансию
                'host' => 'hh.ru'
            ]);

        var_dump($response->body());
    }

    //удалить хуки
    public function deleteWebHook(){

        $account_info = Access::where('source_name', 'hh_t')->first();
        $id = '152795'; //id подписки на уведомление

        $response = Http::withHeaders([
            'HH-User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$account_info->access_token
        ])
            ->delete('https://api.hh.ru/webhook/subscriptions/'.$id, [
                'locale' => 'RU', //новый отклик на вакансию
                'host' => 'hh.ru'
            ]);

        var_dump($response->body());
    }
}
