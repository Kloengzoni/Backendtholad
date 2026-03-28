<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration — Ajout de champs complémentaires à la table properties
 * Champs : deposit, contact_phone, contact_email, view_type, rules, price_period extension
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Caution / dépôt de garantie
            $table->decimal('deposit', 12, 2)->default(0)->after('price');

            // Contact direct de la propriété
            $table->string('contact_phone', 30)->nullable()->after('longitude');
            $table->string('contact_email', 150)->nullable()->after('contact_phone');

            // Vue et règlement
            $table->string('view_type', 50)->nullable()->after('district');
            $table->text('rules')->nullable()->after('views_count');

            // Champs spécifiques bureaux / salles
            $table->integer('workstations')->nullable()->after('capacity');

            // Terrain
            $table->string('terrain_type', 50)->nullable()->after('workstations');
            $table->string('land_title', 100)->nullable()->after('terrain_type');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'deposit', 'contact_phone', 'contact_email',
                'view_type', 'rules', 'workstations', 'terrain_type', 'land_title',
            ]);
        });
    }
};
