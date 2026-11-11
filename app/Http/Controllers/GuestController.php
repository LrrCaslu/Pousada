<?php
namespace App\Http\Controllers;
use App\Models\{Reservation,Guest};
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
class GuestController extends Controller {
 public function dashboard(Request $r){
  $guest=$r->user()->guest;
  $reservations=$guest?$guest->reservations()->with('rooms.type','payments')->latest()->paginate(10):Reservation::whereRaw('1=0')->paginate(10);
  return view('guest.dashboard',compact('reservations'));
 }
 public function cancel(Request $r,Reservation $reservation,ReservationService $service){
  abort_unless($reservation->guest->user_id===$r->user()->id,403);
  $service->transition($reservation,'cancelada','Cancelamento solicitado pelo hóspede');
  return back()->with('success','Reserva cancelada.');
 }
 public function profile(Request $r){return view('guest.profile',['user'=>$r->user()]);}
 public function update(Request $r){
  $d=$r->validate(['name'=>'required|string|max:120','phone'=>'nullable|string|max:30','current_password'=>'nullable|required_with:password','password'=>['nullable','confirmed',PasswordRule::min(10)->letters()->numbers()]]);
  if(!empty($d['password'])){if(!Hash::check($d['current_password']??'',$r->user()->password))return back()->withErrors(['current_password'=>'Senha atual incorreta.']);$r->user()->password=Hash::make($d['password']);}
  $r->user()->name=$d['name'];$r->user()->save();$r->user()->guest?->update(['name'=>$d['name'],'phone'=>$d['phone']??null]);
  return back()->with('success','Dados atualizados.');
 }
}
