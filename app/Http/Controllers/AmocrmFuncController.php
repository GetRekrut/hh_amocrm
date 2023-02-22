<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mockery\Exception;
use Illuminate\Support\Facades\Log;

class AmocrmFuncController extends AccessController
{

    public $amoCRM;

    public $resp_id = 7168072; //алексей
    public $status_id = 52163782; //отклики хх тех - новые отклики
    public $pipeline_id;

    public $city;
    public $age;
    public $email;
    public $citizenship;
    public $tag;

    public function getCurrentContact($applicant, $amocrm)
    {
        try {
            $contacts = $amocrm->contacts()->searchByPhone($applicant->phone);
            $currentContact = $contacts->first();

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка поиска контакта hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        return $currentContact;
    }

    public function createContact ($applicant, $amocrm)
    {
        try {

            $newContact = $amocrm->contacts()->create();
            $newContact->responsible_user_id = $this->resp_id;
            $newContact->name = $applicant->name;
            $newContact->cf('Телефон')->setValue($applicant->phone);

            if (!empty($applicant->email))
                $newContact->cf('Email')->setValue($applicant->email);

            $newContact->save();

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка создания контакта hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        return $newContact;
    }

    public function addNote ($applicant, $amocrm, $entity)
    {
        $note_text = [
            'Информация о соискателе',
            '----------------------',
            ' Имя : ' . $applicant->name,
            ' Телефон : ' . $applicant->phone,
            ' Возраст : ' . $applicant->age,
            ' Город : ' . $applicant->area,
            ' Гражданство : ' . $applicant->citizenship,
            '----------------------',
        ];
        $note_text = implode("\n", $note_text);

        $note = $entity->createNote( $type = 4 );
        $note->text = $note_text;
        $note->element_type = 1;
        $note->element_id = $entity->id;
        $note->save();
    }

    public function addTask (
        $contact,
        $lead,
        $expireTime,
        $text = 'Поступил отклик!'
    )
    {
        $task = $lead->createTask( $type = 1 );
        $task->text = $text;
        $task->element_type = 2;
        $task->responsible_user_id = $contact->responsible_user_id;
        $task->complete_till_at = $expireTime;
        $task->element_id = $lead->id;
        $task->save();
    }

    public function createLeadInContact ($applicant, $amocrm, $contact)
    {
        try {

            $newLead = $contact->createLead();
            $newLead->responsible_user_id = $contact->responsible_user_id;
            $newLead->pipeline_id = 5997970;
            $newLead->status_id = $this->status_id;
            $newLead->attachTags(['hh']);
            $newLead->name = 'Тест Отклик HH';
            $newLead->cf()->byId(445211)->setValue($applicant->area); //город
            $newLead->cf()->byId(1151137)->setValue($applicant->age); //возраст
            $newLead->cf()->byId(445215)->setValue($applicant->citizenship); //гражданство
            $newLead->cf()->byId(1162695)->setValue('headhunter'); //источнк
            $newLead->save();

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка создания сделки hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        return $newLead;
    }

    public function updateLead ($applicant, $lead)
    {
        try {

//            $lead->attachTags([$this->tag, $info['tag_2']]);
//            $lead->name = 'Проходит обучение';
//            $lead->status_id = $info['status_id'];

            $city = $lead->cf()->byId(445211)->getValue(); //город
            if (!empty($city))
                $lead->cf()->byId(445211)->setValue($applicant->area); //город

            $age = $lead->cf()->byId(1151137)->getValue(); //возраст
            if (!empty($age))
                $lead->cf()->byId(1151137)->setValue($applicant->age); //возраст

            $citizenship = $lead->cf()->byId(445215)->getValue(); //гражданство
            if (!empty($citizenship))
                $lead->cf()->byId(445215)->setValue($applicant->citizenship); //гражданство

            $lead->save();

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->sendTelegram('Ошибка обновления сделки hh_crc: '.$e->getLine().' - '. $e->getMessage());
        }

        return $lead;
    }

    public function getListOfLeads ( $contact )
    {
        $leads = $contact->leads;
        return $leads;
    }

    public function changeStatusOfLead ( $lead, $status )
    {
        $lead->status_id = $status;
        $lead->save();
    }

}
