<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name',191)->nullable();
            $table->string('last_name',191)->nullable();

            $table->string('username',191)->unique();
            $table->string('phone',20)->unique()->nullable();
            $table->string('email',191)->unique();
            $table->string('password',191);

            $table->string('country', 191)->nullable();
            $table->string('state', 191)->nullable();
            $table->string('city', 191)->nullable();

            $table->date('date_of_birth')->nullable();
            $table->string('birth_place',191)->nullable();

            $table->string('playing_role',20)->nullable();
            $table->string('batting_style',20)->nullable();
            $table->string('bowling_style',20)->nullable();

            $table->string('profile_pic',191)->nullable();
            $table->string('cover',191)->nullable();
            $table->enum('gender', ['MALE', 'FEMALE', 'OTHER'])->nullable();
            $table->text('bio', 1000)->nullable();
            $table->text('hire_info', 500)->nullable();
            $table->text('social_accounts', 1000)->nullable()->comment('Facebook, Twitter, Instagram and Discord accounts links');

            $table->string('registration_type', 20)->comment('ORGANIZER, CLUB_OWNER, SCORER, PLAYER')->nullable();
            $table->string('forgot_code', 30)->default(0)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
