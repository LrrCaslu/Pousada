<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Reservation,Room};
use App\Services\ReservationService;
use Illuminate\Http\Request;
class ReservationController extends Controller {
 public function index(Request $r){
  $query=Reservation::with('guest','rooms.type')->latest();
  if($r->filled('status'))$query->where('status',$r->input('status'));
  if($r->filled('search'))$query->where(function($q)use($r){$q->where('code','like','%'.$r->input('search').'%')->orWhereHas('guest',fn($g)=>$g->where('name','like','%'.$r->input('search').'%'));});
  return view('admin.reservations.index',['reservations'=>$query->paginate(15)->withQueryString()]);
 }
 public function create(){return view('admin.reservations.create',['rooms'=>Room::with('type')->where('active',true)->orderBy('number')->get()]);}
 public function store(Request $r,ReservationService $service){
  $d=$r->validate(['room_id'=>'required|exists:rooms,id','check_in'=>'required|date|after_or_equal:today','check_out'=>'required|date|after:check_in','adults'=>'required|integer|min:1|max:20','children'=>'nullable|integer|min:0|max:20','name'=>'required|string|max:120','email'=>'required|email|max:180','phone'=>'nullable|string|max:30','coupon_code'=>'nullable|string|max:32','payment_method'=>'required|in:pix,cartao,presencial,transferencia']);
  $res=$service->create($d);return redirect()->route('admin.reservations.show',$res)->with('success','Reserva cadastrada.');
 }
 public function show(Reservation $reservation){return view('admin.reservations.show',['reservation'=>$reservation->load('guest','rooms.type','payments')]);}
 public function status(Request $r,Reservation $reservation,ReservationService $service){
  $d=$r->validate(['status'=>'required|in:pendente,aguardando_pagamento,confirmada,checkin,checkout,cancelada,nao_compareceu','note'=>'nullable|string|max:600']);
  $service->transition($reservation,$d['status'],$d['note']??null);return back()->with('success','Status atualizado.');
 }
}
