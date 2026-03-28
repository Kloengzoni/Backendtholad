<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN type ENUM(
            'appartement',
            'villa',
            'studio',
            'maison',
            'chambre',
            'bureau',
            'salle_reunion',
            'salle_fete',
            'terrain',
            'entrepot',
            'commerce',
            'autres'
        ) DEFAULT 'appartement'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN type ENUM(
            'appartement','villa','studio','maison','chambre','bureau','terrain'
        ) DEFAULT 'appartement'");
    }
};