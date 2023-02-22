<?php

namespace App\Http\Controllers;

use App\Models\Access;
use App\Models\Applicant;
use Illuminate\Http\Request;

class AmocrmController extends AmocrmFuncController
{

    public function addApplicant(){

        $applicants = Applicant::where('status_amocrm', 'unset')->take(10)->get();
        $amocrm = $this->connectAmoCrm();

        if (!empty($applicants)){

            foreach ($applicants as $applicant){

                if (empty($applicant->phone)){

                    $applicant['status_amocrm'] = 'set';
                    $applicant->save();
                    continue;
                }

                $current_contact = $this->getCurrentContact($applicant, $amocrm);

                if (!$current_contact){

                    $new_contact = $this->createContact($applicant, $amocrm);
//                $this->addNote($applicant, $amocrm, $new_contact);

                    $new_lead = $this->createLeadInContact($applicant, $amocrm, $new_contact);
//                $this->addTask($new_contact, $new_lead, time() + 7200);

                }
                else{

//                $this->addNote($applicant, $amocrm, $current_contact);

                    $leads = $current_contact->leads;

                    // если нашли лиды на контакте
                    if ($leads){

                        $active_lead = false;

                        foreach ($leads->collection()->all() as $lead){

                            //если лид не закрыт
                            if ($lead->status_id != 142 and $lead->status_id != 143){

                                $active_lead = $this->updateLead($applicant, $lead);
//                            $this->addTask($current_contact, $active_lead, time() + 7200);
                                break;
                            }
                        }

                        //если активного лида не нашли
                        if (!$active_lead){

                            $new_lead = $this->createLeadInContact($applicant, $amocrm, $current_contact);
//                        $this->addTask($current_contact, $new_lead, time() + 7200);

                        }
                    }
                    else {
                        //лидов на контакте нет
                        $new_lead = $this->createLeadInContact($applicant, $amocrm, $current_contact);
//                    $this->addTask($current_contact, $new_lead, time() + 7200);

                    }
                }

                // нет ограничения на кол-во создания!!!
                sleep(1);
                $applicant['status_amocrm'] = 'set';
                $applicant->save();
            }
        }
    }
}
