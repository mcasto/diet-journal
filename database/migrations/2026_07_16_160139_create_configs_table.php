<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->onDelete('cascade');
            $table->string('sex')
                ->default('m');
            $table->decimal('height', 8, 2)
                ->default(172.72);
            $table->decimal('weight', 8, 2)
                ->default(81.65);
            $table->date('birthdate')
                ->default('1996-07-16');
            $table->integer('exercise')
                ->default(0);
            $table->string('target')
                ->default('loss');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configs');
    }
};
