<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Hook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestController extends AccessController
{

    public $access_token;
    public $manager_access;
    public $manager_access_token;
    public $age = null;
    public $area = null;

    public function getHook(Request $request){

        $request = $request->all();

        try {

            if (!empty($request['payload'])){

                $payload = $request['payload'];
                $this->manager_access = $this->getManagerToken($payload['vacancy_id'], $payload['employer_id']);

                //если по вакансии хука доступ в БД нашли, то работаем с ним, если нет, то выходим, хук не пишем
                if ($this->manager_access){

//                    $this->sendTelegram('Обработка хука hh_crc:' . $this->manager_access->source_name);
                    $this->manager_access_token = $this->manager_access->access_token;
                    // записываем новый хук в таблицу БД
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
                else exit();

            }
            else{
                //если пришел не хук
                $hook = Hook::create([
                    'topic_id' => json_encode($request),
                ]);
                exit();
            }

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка записи хука hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        // ищем в хх отклик по topic_id
        if (!empty($hook->resume_id)){
            //парсим резюме в таблицу для амосрм
            $applicant = $this->handleResume($payload['resume_id'], $hook);

            if ($applicant){
                //отвечаем на отклик
                $info = $this->checkNegotiation($payload['topic_id']);
            }
            else{

                $hook->status_invite_hh = 'not found';
                $hook->save();
            }
        }
        else exit(); //если нет блока else и не срабадывает if, то дольше обработка и ответ 500

        try {

            if (!empty($info)){
                //урл состоит https://api.hh.ru/negotiations/hired/3098070800?message=Для трудоустройства в Яндекс Еда проходите
                // по ссылке https://clc.to/yandex.rabota или свяжитесь с нами +7 (958) 111-84-14&send_sms=true
                $response = Http::withHeaders([
                    'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Bearer '.$this->manager_access_token
                ])
                    ->put($info['action_invite']['url'].'?message='.$info['messages']['text_email'].'&send_sms=true');

                $hook->status_invite_hh = 'set';
                $hook->save();
            }
            else exit();

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка приглашения на отклик hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }
    }

    //просмотр резюме
    public function handleResume(string $resume_id, object $hook){

        try {

            $response_raw = Http::withHeaders([
                'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
//            'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer '.$this->manager_access_token
            ])
                ->get('https://api.hh.ru/resumes/'.$resume_id);

            $resume = json_decode($response_raw->body(), true);
//            dd($resume);

            if (!empty($resume['description']) and $resume['description'] == 'Not Found'){
                //если резюме удалено или скрыто
                return false;
            }

            if (!empty($resume['oauth_error'])){

                $this->refreshToken($this->manager_access->manager_id);
                $this->sendTelegram('Ошибка авторизации в ХХ резюме: ');
            }

            $contact_info = $this->validateContactInfo($resume);

            $birth_date = $resume['birth_date'] ?? null;//дата рождения

            $applicant = Applicant::create([
                'name' => $contact_info['name'],
                'phone' => $contact_info['phone'],
                'email' => $contact_info['email'],
                'age' => $contact_info['age'],
                'area' => $contact_info['area'],
                'citizenship' => $contact_info['citizenship'],
                'resume_body' => $response_raw->body(),
            ]);

            $hook->status_amocrm = 'set';
            $hook->save();

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка обработки резюме hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        if ($applicant)
            return true;
        else
            return false;

    }

    //просмотр отклика
    //из тела отклика нужно достать нужный урл шаблона, чтобы передать его в метод для получение текста
    //а также нужно вытащить нужный action, чтобы потом использовать его урл
    public function checkNegotiation(string $topic_id){

        try {

            $response = Http::withHeaders([
                'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
//            'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer '.$this->manager_access_token
            ])
                ->get('https://api.hh.ru/negotiations/'.$topic_id);

            $response = json_decode($response->body(), true);
            $info = null;
            $this->age = $response['resume']['age'];//возраст
            $this->area = $response['resume']['area']['name'] ?? 'Пусто';//название города
//            var_dump($response['resume']['area']['name']);

            //получаем параметры $id_template и $id_action
            $params = $this->getAgeParams($this->area, $this->age);

            $templates = $response['templates'];

            foreach ($templates as $template){

                if($template['id'] == $params['id_template']){

                    $template_url = $template['url'];
                    break;
                }
            }

            //текст приглоса строка
            $messages = $this->getTextMessage($template_url);

            $actions = $response['actions'];

            foreach ($actions as $action){

                if($action['id'] == $params['id_action']){

                    $action_invite = $action;
                    break;
                }
            }

            $info = [ 'messages' => $messages, 'action_invite' => $action_invite ];

        } catch (\Exception $e) {
            $this->sendTelegram('Ошибка просмотра отклика hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        return $info;

    }

    //получаем текст шаблона по готовому урлу https://api.hh.ru/message_templates/invite?topic_id=3098303559
    public function getTextMessage(string $url){

        try {

            $response = Http::withHeaders([
                'User-Agent' => 'amocrm/1.0 (vladislav.fixnation@gmail.com)',
//            'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer '.$this->manager_access_token
            ])
                ->get($url);

            $response = json_decode($response->body(), true);

            $message = [];
            $text_email = $response['mail']['text'] ?? '';
            $text_sms = $response['sms']['text'] ?? '';
            $message['text_email'] = $text_email;
            $message['text_sms'] = $text_sms;

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка получения текста шаблона hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        return $message;
    }

    //проверка возраста и города, если челу меньше 16 или если ему 16, но он не проходит список городов
    //то ему уходить телеф интерфью, иначе ему уходит приглос на работу
    public function getAgeParams($area, $age){

        try {

            $city_after_16yo = [
                'Екатеринбург',
                'Нижний Новгород',
                'Ростов-на-Дону',
                'Москва',
                'Электросталь',
                'Щербинка',
                'Щелково',
                'Химки',
                'Реутов',
                'Раменское',
                'Пушкино',
                'Подольск',
                'Одинцово',
                'Мытищи',
                'Люберцы',
                'Лобня',
                'Красноярск',
                'Красногорск',
                'Королёв',
                'Зеленоград',
                'Жуковский',
                'Железнодорожный',
                'Домодедово',
                'Долгопрудный',
                'Видное',
                'Балашиха',
            ];
            $params = [];

            if (($age >= 16 and array_search($area, $city_after_16yo)) or
                ($age >= 18)){

                $params['id_action'] = 'hired';
                $params['id_template'] = 'hired';
            }
            else{

                $params['id_action'] = 'phone_interview';
                $params['id_template'] = 'invite';
            }

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка возраст-город hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        return $params;
    }

    //контактная инфа бывает разной в резюме, нужно разбирать что имеется
    public function validateContactInfo($resume){

        $clear_info = [];
        $clear_info['phone'] = null;
        $clear_info['email'] = null;

        try {

            //может не быть одной из частей имени
            $l_name = $resume['last_name'] ?? '';
            $f_name = $resume['first_name'] ?? '';
            $clear_info['name'] = $l_name.' '.$f_name;

            //в контактной инфе может быть несколько телефонов и не быть почты или они могут быть скрыты
            $contact_info = $resume['contact'];
            foreach ($contact_info as $item){

                if ($item['type']['id'] == 'work')
                    $clear_info['phone'] = $item['value']['formatted'] ?? null;

                elseif ($item['type']['id'] == 'cell')
                    $clear_info['phone'] = $item['value']['formatted'] ?? null;

                if ($item['type']['id'] == 'email')
                    $clear_info['email'] = $item['value'] ?? null;

            }

            $clear_info['age'] = $resume['age'] ?? null;
            $clear_info['area'] = $resume['area']['name'] ?? null;//город
            $clear_info['citizenship'] = $resume['citizenship'][0]['name'] ?? null;//гражданство

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка validateContactInfo hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        return $clear_info;

    }

}
