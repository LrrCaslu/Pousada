<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{RoomType,SeasonalRate,Rate,Coupon};
use App\Services\AuditService;
use Illuminate\Http\Request;
class RateController extends Controller {
 public function index(){return view('admin.rates.index',['types'=>RoomType::all(),'seasons'=>SeasonalRate::latest()->take(20)->get(),'rates'=>Rate::latest()->take(20)->get(),'coupons'=>Coupon::latest()->get()]);}
 public function season(Request $r){$d=$r->validate(['room_type_id'=>'required|exists:room_types,id','name'=>'required|string|max:120','start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','price'=>'required|numeric|min:0.01','priority'=>'required|integer|min:0|max:100']);$s=SeasonalRate::create($d);AuditService::record('tarifa.temporada','seasonal_rate',$s->id);return back()->with('success','Temporada cadastrada.');}
 public function day(Request $r){$d=$r->validate(['room_type_id'=>'required|exists:room_types,id','date'=>'required|date','price'=>'required|numeric|min:0.01']);Rate::updateOrCreate(['room_type_id'=>$d['room_type_id'],'date'=>$d['date']],['price'=>$d['price']]);return back()->with('success','Tarifa diária salva.');}
 public function coupon(Request $r){$d=$r->validate(['code'=>'required|alpha_dash|max:32|unique:coupons','type'=>'required|in:fixed,percent','value'=>'required|numeric|min:0.01|max:10000','starts_at'=>'nullable|date','ends_at'=>'nullable|date|after_or_equal:starts_at']);$d['code']=strtoupper($d['code']);$c=Coupon::create($d);AuditService::record('cupom.criado','coupon',$c->id);return back()->with('success','Cupom criado.');}
}
