<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hooks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('topic_id')->nullable();
            $table->string('resume_id')->nullable();
            $table->string('vacancy_id')->nullable();
            $table->string('employer_id')->nullable();
            $table->string('subscription_id')->nullable();
            $table->string('action_type')->nullable();
            $table->string('user_id')->nullable();
            $table->string('status_amocrm')->default('unset');
            $table->string('status_invite_hh')->default('unset');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hooks');
    }
}
