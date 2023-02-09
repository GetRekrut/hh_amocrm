<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Hook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class ResponseController extends AccessController
{

    public $access_token;

    public function getHook(Request $request){

        $account_info = $this->getKeys('hh_test');
        $this->access_token = $account_info->access_token; // получаем токен доступа

        $request = $request->all();

        if (!empty($request['payload'])){

            // записываем новый хук в таблицу БД
            $payload = $request['payload'];
            $hook = Hook::create([
                'topic_id' => $payload['topic_id'],
                'resume_id' => $payload['resume_id'],
                'vacancy_id' => $payload['vacancy_id'],
                'employer_id' => $payload['employer_id'],
                'subscription_id' => $request['subscription_id'],
                'action_type' => $request['action_type'],
                'user_id' => $request['user_id'],
            ]);

        }

        // ищем в хх отклик по topic_id
        if ($hook)
            $info = $this->checkNegotiation($payload['topic_id']);

        $response = Http::withHeaders([
            'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer '.$this->access_token
        ])
            ->put($info['action_invite']['url'].'?'.$info['message']);

        dd($response = json_decode($response->body(), true));

    }

    //просмотр резюме
    public function handleResume($resume_id, $hook){

        $response_raw = Http::withHeaders([
            'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
//            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer '.$this->access_token
        ])
            ->get('https://api.hh.ru/resumes/'.$resume_id);

        $response = json_decode($response_raw->body(), true);

        $applicant = Applicant::create([
            'name' => $response['last_name'].' '.$response['first_name'],
            'phone' => $response['contact'][0]['value']['formatted'],// тут нужно перебирать
            'email' => $response['contact'][1]['value'], // и тут тоже
            'age' => $response['age'],
            'area' => $response['area']['name'],
            'citizenship' => $response['citizenship']['0']['name'],
            'resume_body' => $response_raw->body(),
        ]);

        $hook->status_amocrm = 'set';
        $hook->save();

        dd($response);

    }

    //просмотр отклика
    //из тела отклика нужно достать нужный урл шаблона, чтобы передать его в метод для получение текста
    //а также нужно вытащить нужный action, чтобы потом использовать его урл
    public function checkNegotiation($topic_id){

        $id_template = 'invite';
        $id_action = 'Телефонное интервью';

        $response = Http::withHeaders([
            'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
//            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer '.$this->access_token
        ])
            ->get('https://api.hh.ru/negotiations/'.$topic_id);

        $response = json_decode($response->body(), true);

        $templates = $response['templates'];

        foreach ($templates as $template){

            if($template['id'] == $id_template){

                $template_url = $template['url'];
                break;
            }
        }

        //текст приглоса строка
        $message = $this->getTextMessage($template_url);

        $actions = $response['actions'];

        foreach ($actions as $action){

            if($action['name'] == $id_action){

                $action_invite = $action;
                break;
            }
        }

        $info = [ 'message' => $message, 'action_invite' => $action_invite ];

        return $info;

    }

    //получаем текст шаблона по готовому урлу https://api.hh.ru/message_templates/invite?topic_id=3098303559
    public function getTextMessage(string $url){

        $response = Http::withHeaders([
            'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
//            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer '.$this->access_token
        ])
            ->get($url);

        $response = json_decode($response->body(), true);
        $message = $response['sms']['text'];

        return $message;
    }

}
