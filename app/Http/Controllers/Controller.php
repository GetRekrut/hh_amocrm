<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Http;
use App\Models\Access;

class Controller extends BaseController
{

    private $chat_id_tg = '-602888899';
    private $token_bot_tg = '1733271437:AAFDiwmKsT2qp_U0JYa8rMifFZql7ncbwvI';

    public function sendTelegram ($message)
    {

        $response = Http::withHeaders([
            'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
            'Content-Type' => 'application/json',
        ])
            ->post('https://api.telegram.org/bot' . $this->token_bot_tg . '/sendMessage', [
                'text' => $message,
                'chat_id' => $this->chat_id_tg,
            ]);
    }

    public function Telegram ($message)
    {
        $send_data = [
            'text' => $message,
            'chat_id' => $this->chat_id_tg,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->token_bot_tg . '/sendMessage',
            CURLOPT_POSTFIELDS => json_encode($send_data),
            CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"))
        ]);
        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result);

        if ($result->ok) return true;
        else return false;
    }
}
