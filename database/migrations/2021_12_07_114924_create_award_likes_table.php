<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAwardLikesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('award_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('users');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('tournament_id')->nullable()->constrained('tournaments');
            $table->foreignId('fixture_id')->nullable()->constrained('fixtures');
            $table->string('type', 10)->comment('SHARE, LIKE')->nullable();
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
        Schema::dropIfExists('award_likes');
    }
}
