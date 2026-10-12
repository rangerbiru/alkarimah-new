<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $menuIds = DB::table('menu')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(route, '$.name')) = ?", ['finance.transaction.pending'])
            ->pluck('id');

        if ($menuIds->isEmpty()) {
            return;
        }

        $userIds = DB::table('users')->where('role', 'kasir-unit')->pluck('id');

        DB::table('user_rights')
            ->whereIn('id_user', $userIds)
            ->whereIn('id_menu', $menuIds)
            ->delete();
    }

    /**
     * Reverse the migrations.
     *
     * Deleted rights are intentionally not restored: Kasir Unit must not have the Pending menu.
     */
    public function down(): void
    {
        //
    }
};
