<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('entity_id'); // This will reference the project ID
            $table->text('value')->nullable(); 
            $table->timestamps();
    
            // Foreign keys
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
            // entity_id references 'id' on 'projects' 
            $table->foreign('entity_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
