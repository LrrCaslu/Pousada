<?php
namespace App\Models;
class Expense extends \Illuminate\Database\Eloquent\Model {
 protected $table='expenses';
 protected $fillable=["description", "category", "amount", "date", "created_by"];
 protected function casts(): array {return ['date'=>'date','amount'=>'decimal:2'];}
 
}
