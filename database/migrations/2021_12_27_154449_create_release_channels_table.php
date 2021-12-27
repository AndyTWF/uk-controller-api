<?php

use App\Models\Version\PluginReleaseChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReleaseChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plugin_release_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('The channel name');
            $table->timestamps();
        });

        PluginReleaseChannel::create(
            [
                'name' => 'stable',
            ]
        );

        PluginReleaseChannel::create(
            [
                'name' => 'beta',
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plugin_release_channels');
    }
}
