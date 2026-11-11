<?php
namespace App\Models;
class Reservation extends \Illuminate\Database\Eloquent\Model {
 protected $table='reservations';
 protected $fillable=["code", "guest_id", "check_in", "check_out", "adults", "children", "status", "expires_at", "payment_method", "base_total", "discount_total", "total", "currency", "price_breakdown", "coupon_code", "notes"];
 protected function casts(): array {return ['check_in'=>'date','check_out'=>'date','expires_at'=>'datetime','price_breakdown'=>'array','total'=>'decimal:2','base_total'=>'decimal:2','discount_total'=>'decimal:2'];}
 
 public function guest(){return $this->belongsTo(Guest::class);}
 public function rooms(){return $this->belongsToMany(Room::class,'reservation_rooms')->withPivot('total');}
 public function payments(){return $this->hasMany(Payment::class);}

}
