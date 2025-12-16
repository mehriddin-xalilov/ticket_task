<?php

namespace Modules\Translations\App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemMessage extends Model
{
    protected $table = 'system_message';
    protected $fillable = [
      "id",
      "category",
      "message",
    ];
    public $timestamps = true;
    public function translations()
    {
        return $this->hasMany(SystemMessageTranslation::class, 'id', 'id');
    }

}
