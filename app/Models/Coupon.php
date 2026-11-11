<?php
namespace App\Models;
class Coupon extends \Illuminate\Database\Eloquent\Model {
 protected $table='coupons';
 protected $fillable=["code", "type", "value", "starts_at", "ends_at", "active"];
 protected function casts(): array {return ['active'=>'boolean'];}
 
}
