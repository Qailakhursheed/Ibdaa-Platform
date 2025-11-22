<?php

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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // User who performed the action
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->enum('user_role', ['manager', 'technical', 'trainer', 'student'])->nullable();
            
            // Action details
            $table->string('action'); // create, update, delete, view, login, logout, etc.
            $table->string('model_type')->nullable(); // Student, Course, User, Enrollment, etc.
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('description'); // Human-readable description
            
            // HTTP Request details
            $table->string('http_method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->string('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            // Data changes (for update/delete operations)
            $table->json('old_values')->nullable(); // Before change
            $table->json('new_values')->nullable(); // After change
            
            // Additional context
            $table->text('metadata')->nullable(); // Any extra JSON data
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            $table->timestamps();
            
            // Indexes for fast searching
            $table->index('user_id');
            $table->index('action');
            $table->index('model_type');
            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
