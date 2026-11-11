<?php
namespace App\Services;
use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
class AvailabilityService {
 public function available(string $from,string $to,int $persons,?int $excludeReservation=null):Builder {
  return Room::query()->with('type')->where('active',true)->where('housekeeping_status','!=','manutencao')->whereHas('type',fn($q)=>$q->where('active',true)->where('capacity','>=',$persons))
    ->whereNotExists(function($q) use ($from,$to,$excludeReservation){
       $q->selectRaw('1')->from('reservation_rooms as rr')->join('reservations as res','res.id','=','rr.reservation_id')
       ->whereColumn('rr.room_id','rooms.id')->where('res.check_in','<',$to)->where('res.check_out','>',$from)
       ->where(function($status){$status->whereIn('res.status',['confirmada','checkin'])->orWhere(fn($pending)=>$pending->whereIn('res.status',['pendente','aguardando_pagamento'])->where('res.expires_at','>',now()));});
       if($excludeReservation) $q->where('res.id','!=',$excludeReservation);
    })->whereNotExists(function($q)use($from,$to){
       $q->selectRaw('1')->from('room_blocks as b')->whereColumn('b.room_id','rooms.id')->where('b.start_date','<',$to)->where('b.end_date','>',$from);
    });
 }
 public function isAvailableLocked(Room $room,string $from,string $to,?int $excludeReservation=null):bool {
    if(!$room->active||!$room->type->active||$room->housekeeping_status==='manutencao')return false;
    return $this->available($from,$to,1,$excludeReservation)->where('rooms.id',$room->id)->exists();
 }
}
