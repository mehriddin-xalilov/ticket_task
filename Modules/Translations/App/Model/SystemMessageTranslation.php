<?php

namespace Modules\Translations\App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemMessageTranslation extends Model
{

    protected $table = 'system_message_translation';
    protected $fillable = [
        'id',
        'language',
        'translation'
    ];
    public $timestamps = true;


    public function message()
    {
        return $this->hasOne(SystemMessage::class);
    }

    public static function generateJs()
    {
        error_reporting(2245);
        $langs = Langs::where('status', 1)->get();
        $messages = SystemMessage::all();
        foreach ($langs as $lang) {
            $data=[];
            foreach ($messages as $message) {
               $item=self::where([
                   'language' => $lang->code,
                   'id'=>$message['id']
               ])->first();
               if(is_object($item) and strlen($item->translation)!== 0) {
                   $data[$message['message']] = $item->translation;
                   continue;
               }
               $data[$message['message']] = $message['translation'];
            }
            $paths = config('translations.paths');
            foreach($paths as $item) {
                $dir = $item.'/locales/'.$lang->code;
                $link = $dir.'/translation.json';

                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                if (file_exists($link)) {
                    unlink($link);
                }
                $fp = fopen($link, 'w');

                if (!$fp) {
                    throw new \Exception("Fayl ochilmadi: $link");
                }
                fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE));
                fclose($fp);
            }

        }
        return true;
    }


    public static function rules()
    {
        return [
            'id'=>'required|exists:system_message_translation,id',
            'language'=>'required|exists:langs,code',
            'translation'=>'',
        ];
    }
}
