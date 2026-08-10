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
    Schema::table('mensalidades', function (Blueprint $table) {
        $table->longText('pix_qr_base64')->nullable();
    });
}

public function down()
{
    Schema::table('mensalidades', function (Blueprint $table) {
        $table->dropColumn('pix_qr_base64');
    });
}

};
