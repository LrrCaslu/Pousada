<?php
namespace App\Models;
class Setting extends \Illuminate\Database\Eloquent\Model {
 protected $table='settings';
 protected $fillable=["key", "value"];
 protected function casts(): array {return [];}
 
 protected $primaryKey='key'; public $incrementing=false; protected $keyType='string';
 public static function read(string $key,?string $default=null):?string {return static::query()->where('key',$key)->value('value')??$default;}

}
