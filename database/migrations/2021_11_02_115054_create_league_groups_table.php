<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeagueGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('league_groups', function (Blueprint $table) {
            $table->id();
            $table->string('league_group_name', 191);
            $table->foreignId('tournament_id');
            $table->enum('round_type', ['IPL', 'LEAGUE MATCH', 'SUPER LEAGUE']);
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
        Schema::dropIfExists('league_groups');
    }
}
