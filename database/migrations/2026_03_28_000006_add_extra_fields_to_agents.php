<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration — Champs supplémentaires pour les agents
 * Adresse, contrat, document d'identité, relation contact urgence
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // Adresse
            $table->string('address')->nullable()->after('emergency_contact_phone');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('country', 100)->default('Congo Brazzaville')->after('city');

            // Contrat
            $table->string('contract_type', 30)->default('CDI')->after('hire_date');

            // Contact urgence — relation
            $table->string('emergency_contact_relation', 50)->nullable()->after('emergency_contact_phone');

            // Document identité
            $table->string('id_document_type', 50)->nullable()->after('avatar');
            $table->string('id_document_number', 100)->nullable()->after('id_document_type');

            // Permissions supplémentaires
            $table->boolean('can_manage_users')->default(false)->after('can_view_reports');
            $table->boolean('can_manage_agents')->default(false)->after('can_manage_users');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'address', 'city', 'country', 'contract_type',
                'emergency_contact_relation', 'id_document_type', 'id_document_number',
                'can_manage_users', 'can_manage_agents',
            ]);
        });
    }
};
