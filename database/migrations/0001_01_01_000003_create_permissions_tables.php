<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('user_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
            $table->timestamps();
        });

        // Seed permissions (required by tests that use RefreshDatabase)
        $this->seedPermissions();

        // MySQL triggers for auto-capitalizing patient names
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::unprepared('
                CREATE TRIGGER auto_capitalize_insert
                BEFORE INSERT ON patients
                FOR EACH ROW
                BEGIN
                    IF NEW.first_name IS NOT NULL THEN
                        SET NEW.first_name = CONCAT(UPPER(SUBSTRING(NEW.first_name, 1, 1)), LOWER(SUBSTRING(NEW.first_name, 2)));
                    END IF;
                    IF NEW.last_name IS NOT NULL THEN
                        SET NEW.last_name = CONCAT(UPPER(SUBSTRING(NEW.last_name, 1, 1)), LOWER(SUBSTRING(NEW.last_name, 2)));
                    END IF;
                END;
            ');

            DB::unprepared('
                CREATE TRIGGER auto_capitalize_update
                BEFORE UPDATE ON patients
                FOR EACH ROW
                BEGIN
                    IF NEW.first_name IS NOT NULL THEN
                        SET NEW.first_name = CONCAT(UPPER(SUBSTRING(NEW.first_name, 1, 1)), LOWER(SUBSTRING(NEW.first_name, 2)));
                    END IF;
                    IF NEW.last_name IS NOT NULL THEN
                        SET NEW.last_name = CONCAT(UPPER(SUBSTRING(NEW.last_name, 1, 1)), LOWER(SUBSTRING(NEW.last_name, 2)));
                    END IF;
                END;
            ');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS auto_capitalize_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS auto_capitalize_update');
        }

        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }

    private function seedPermissions(): void
    {
        // Seed only the permissions that were historically seeded by migrations
        // (zones, handouts, maternal). The full set comes from PermissionSeeder.
        $permissions = [
            ['name' => 'zones', 'description' => 'Manage geographic zones and assign health workers'],
            ['name' => 'print_handouts', 'description' => 'Print consultation Rx and diagnosis handouts'],
            ['name' => 'dashboard_handouts', 'description' => 'View completed handouts panel on dashboards'],
            ['name' => 'maternal', 'description' => 'Access to Maternal Health module (prenatal, postnatal, family planning)'],
        ];

        $now = now();

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                array_merge($permission, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
};
