<?php

namespace Modules\Translations\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\Translations\App\Model\Langs;

;

class TranslationsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Model::unguard();

        $languages = [
            ['name' => "Ўзбекча", 'code' => 'oz', 'status' => 1],
            ['name' => "O'zbekcha", 'code' => 'uz', 'status' => 1],
            ['name' => 'Русский', 'code' => 'ru', 'status' => 1],
            ['name' => 'English', 'code' => 'en', 'status' => 1],
        ];

        foreach ($languages as $language) {
            Langs::create([
                'name' => $language['name'],
                'code' => $language['code'],
                'status' => $language['status'],
            ]);
        }
    }
}
