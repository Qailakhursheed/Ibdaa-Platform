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
        // Users table indexes
        // Skip - users_role_status_index and users_status_index already exist
        // All necessary indexes already present from previous migrations

        // Students table indexes - add performance indexes for filtering
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasIndex('students', 'students_status_index')) {
                $table->index('status', 'students_status_index');
            }
            if (!Schema::hasIndex('students', 'students_gender_index')) {
                $table->index('gender', 'students_gender_index');
            }
            if (!Schema::hasIndex('students', 'students_date_of_birth_index')) {
                $table->index('date_of_birth', 'students_date_of_birth_index');
            }
        });

        // Courses table indexes - add price index for filtering
        Schema::table('courses', function (Blueprint $table) {
            // trainer_id and status already indexed
            if (!Schema::hasIndex('courses', 'courses_price_index')) {
                $table->index('price', 'courses_price_index');
            }
        });

        // Enrollments table indexes - add common query filters
        // Only add if table exists (production only, not in test environment)
        if (Schema::hasTable('enrollments')) {
            Schema::table('enrollments', function (Blueprint $table) {
                // student_id, course_id, status, application_id, approved_by already indexed
                if (!Schema::hasIndex('enrollments', 'enrollments_payment_status_index')) {
                    $table->index('payment_status', 'enrollments_payment_status_index');
                }
                if (!Schema::hasIndex('enrollments', 'enrollments_enrollment_date_index')) {
                    $table->index('enrollment_date', 'enrollments_enrollment_date_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No users table indexes to drop - they already existed

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_status_index');
            $table->dropIndex('students_gender_index');
            $table->dropIndex('students_date_of_birth_index');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_price_index');
        });

        if (Schema::hasTable('enrollments')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropIndex('enrollments_payment_status_index');
                $table->dropIndex('enrollments_enrollment_date_index');
            });
        }
    }
};
