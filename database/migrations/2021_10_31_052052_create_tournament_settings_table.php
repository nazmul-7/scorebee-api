<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTournamentSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tournament_settings', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('tournament_id')
                ->constrained('tournaments')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->unsignedTinyInteger('total_groups')->default(0);
            $table->unsignedTinyInteger('min_teams')->default(2);
            $table->unsignedTinyInteger('max_teams')->nullable();
            $table->unsignedTinyInteger('group_winners')->default(1);
            $table->enum('third_position', ['YES', 'NO']);

            $table->string('second_round_type', 20)->comment('SUPER 10, SUPER 8, SUPER 6, SUPER 4, SUPER 2')->nullable();
            $table->string('third_round_type', 20)->comment('SUPER 8, SUPER 6, SUPER 4, SUPER 2')->nullable();
            $table->string('fourth_round_type', 20)->comment('SUPER 6, SUPER 4, SUPER 2')->nullable();

            $table->unsignedTinyInteger('first_round_face_off')->default(1);
            $table->unsignedTinyInteger('second_round_face_off')->default(1);
            $table->unsignedTinyInteger('third_round_face_off')->default(1);
            $table->unsignedTinyInteger('fourth_round_face_off')->default(1);

            $table->date('start_date');
            $table->date('end_date');
            $table->time('match_length');
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
        Schema::dropIfExists('tournament_settings');
    }
}
