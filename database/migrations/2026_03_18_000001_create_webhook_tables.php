<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('endpoint', 500);
            $table->string('description', 191)->nullable();
            $table->json('events');
            $table->string('type')->default('regular');
            $table->json('payload')->nullable();
            $table->json('headers')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('webhooks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('webhook_configuration_id');
            $table->string('event', 100);
            $table->string('endpoint', 500);
            $table->text('payload_sent')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->boolean('successful')->default(false);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('webhook_configuration_id')
                ->references('id')
                ->on('webhook_configurations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('webhook_configurations');
    }
};
