<?php

namespace Modules\Translations\App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Langs extends Model
{
    use HasFactory;
    protected $table = 'langs';
    protected $fillable = [
        "id",
        "name",
        "code",
        "status",
    ];
    public $timestamps = true;
    public static function getLangId($code)
    {
        if ($code === null) {
            $code = 'uz';
        }

        $data = self::where('code', $code)->first();
        return $data ? $data->id : null;
    }

    public static function getLangCode($id)
    {
        $data = self::where('id', $id)->first();

        return $data ? $data->code : null;
    }

}
