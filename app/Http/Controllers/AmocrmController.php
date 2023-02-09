<?php

namespace App\Http\Controllers;

use App\Models\Access;
use Illuminate\Http\Request;

class AmocrmController extends AccessController
{

    public function test(){

        $amocrm = $this->connectAmoCrm();
        dd($amocrm->account);

    }
}
