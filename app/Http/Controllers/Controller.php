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
    
}
