<?php
namespace App\Models;
class Page extends \Illuminate\Database\Eloquent\Model {
 protected $table='pages';
 protected $fillable=["slug", "title", "body", "published"];
 protected function casts(): array {return ['published'=>'boolean'];}
 
}
