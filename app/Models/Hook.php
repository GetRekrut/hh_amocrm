<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hook extends Model
{

    protected $fillable = [
        'topic_id',
        'resume_id',
        'vacancy_id',
        'employer_id',
        'subscription_id',
        'action_type',
        'user_id',
        'status_amocrm',
        'status_invite_hh',
    ];
}
