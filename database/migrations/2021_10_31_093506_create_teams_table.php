<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $baseURL = env('APP_URL');
        $logoPath = $baseURL.'uploads/default_team_logo.webp';
        $bannerPath = $baseURL.'uploads/default_team_banner.webp';

        Schema::create('teams', function (Blueprint $table) use($logoPath, $bannerPath) {
            $table->id();
            $table->string('team_name',191);
            $table->string('team_unique_name',191)->unique();
            $table->string('team_short_name',191)->nullable();
            $table->string('team_banner',191)->default($logoPath)->nullable();
            $table->string('team_logo',191)->default($bannerPath)->nullable();
            $table->string('city', 191);
            $table
                ->foreignId('owner_id')
                ->constrained('users', 'id')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table
                ->foreignId('captain_id')
                ->nullable()
                ->constrained('users', 'id')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
            $table
                ->foreignId('wicket_keeper_id')
                ->nullable()
                ->constrained('users', 'id')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
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
        Schema::dropIfExists('teams');
    }
}
