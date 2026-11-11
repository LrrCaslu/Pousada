<?php
namespace App\Models;
class Payment extends \Illuminate\Database\Eloquent\Model {
 protected $table='payments';
 protected $fillable=["reservation_id", "method", "provider", "status", "amount", "reference", "paid_at"];
 protected function casts(): array {return ['paid_at'=>'datetime','amount'=>'decimal:2'];}
 
 public function reservation(){return $this->belongsTo(Reservation::class);}

}
