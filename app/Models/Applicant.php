<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{

    protected $fillable = [
        'name',
        'phone',
        'email',
        'age',
        'area',
        'citizenship',
        'resume_body',
        'status_amocrm',
    ];
}
