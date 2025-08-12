<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use App\Http\Controllers\AssetController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{

    public function run()
    {
        // $requestArray = new Collection();

        // $requestArray->add(new Request([
        //     'serial_num' => '40ANZJT0B2SU',
        //     'desk' => '001',
        //     'x' => '20',
        //     'y' => '15'
        // ]));

        // // Starts at asset_id = 1001
        // $requestArray->add(new Request([
        //     'userID' => 'estoller',
        //     'fullName' => 'Stoller, Evan',
        //     'desk' => '001'
        // ]));

        // foreach ($requestArray as $request) {
        //     $request->setMethod('POST');
        //     $controller = new AssetController();
        //     $controller->store($request);
        // }
    }
}
