<?php

namespace App\Http\Controllers;

use App\Models\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class HookController extends AccessController
{
    public $access_token;

    public function __construct(){

        $account_info = $this->getKeys('hh_admin');
        $this->access_token = $account_info->access_token; // получаем токен доступа
    }

    //подписка на хуки
    public function setWebHook(){

        $response = Http::withHeaders([
            'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->access_token
        ])
            ->post('https://api.hh.ru/webhook/subscriptions', [
                'actions' => [['type' => 'NEW_NEGOTIATION_VACANCY']], //новый отклик на вакансию
                'url' => 'https://dev.sky-network.pro/api/courier_rec_center/amoCRM/hh/public/hh/get_hook'
            ]);

        var_dump($response->body());
    }

    //поверить хуки
    public function checkWebHook(){

        $response = Http::withHeaders([
            'HH-User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->access_token
        ])
            ->get('https://api.hh.ru/webhook/subscriptions', [
                'locale' => 'RU', //новый отклик на вакансию
                'host' => 'hh.ru'
            ]);

        var_dump($response->body());
    }

    //удалить хуки
    public function deleteWebHook(){


        $id_test = '152795'; //id подписки на уведомление в тестовом кабинете
        $id_dev = '155445'; //id подписки на уведомление в dev кабинете

        $response = Http::withHeaders([
            'HH-User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->access_token
        ])
            ->delete('https://api.hh.ru/webhook/subscriptions/'.$id_dev, [
                'locale' => 'RU', //новый отклик на вакансию
                'host' => 'hh.ru'
            ]);

        var_dump($response->body());
    }
}
