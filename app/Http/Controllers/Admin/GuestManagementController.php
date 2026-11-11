<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Services\AuditService;
use Illuminate\Http\Request;
class GuestManagementController extends Controller {
 public function index(Request $r){return view('admin.guests.index',['guests'=>Guest::query()->when($r->filled('q'),fn($q)=>$q->where('name','like','%'.$r->q.'%')->orWhere('email','like','%'.$r->q.'%'))->withCount('reservations')->latest()->paginate(15)]);}
 public function store(Request $r){$d=$r->validate(['name'=>'required|string|max:120','email'=>'nullable|email|max:180','phone'=>'nullable|string|max:30','notes'=>'nullable|string|max:2000']);$g=Guest::create($d);AuditService::record('hospede.criado','guest',$g->id);return back()->with('success','Hóspede cadastrado.');}
 public function update(Request $r,Guest $guest){$d=$r->validate(['name'=>'required|string|max:120','email'=>'nullable|email|max:180','phone'=>'nullable|string|max:30','notes'=>'nullable|string|max:2000']);$guest->update($d);AuditService::record('hospede.atualizado','guest',$guest->id);return back()->with('success','Hóspede atualizado.');}
}
