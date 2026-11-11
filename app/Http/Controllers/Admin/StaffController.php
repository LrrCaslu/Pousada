<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{User,Role};
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
class StaffController extends Controller {
 public function index(){return view('admin.staff.index',['staff'=>User::with('role')->whereHas('role',fn($q)=>$q->where('slug','!=','hospede'))->orderBy('name')->get(),'roles'=>Role::whereIn('slug',['admin','recepcao','financeiro','governanca'])->get()]);}
 public function store(Request $r){
  $d=$r->validate(['name'=>'required|string|max:120','email'=>'required|email|max:180|unique:users','role_id'=>'required|exists:roles,id','password'=>['required',PasswordRule::min(10)->letters()->numbers()]]);
  $role=Role::findOrFail($d['role_id']);abort_if($role->slug==='hospede',422);
  $u=User::create(['name'=>$d['name'],'email'=>$d['email'],'role_id'=>$role->id,'password'=>Hash::make($d['password']),'active'=>true]);
  AuditService::record('funcionario.criado','user',$u->id);return back()->with('success','Funcionário cadastrado.');
 }
 public function update(Request $r,User $user){
  $d=$r->validate(['name'=>'required|string|max:120','email'=>'required|email|max:180|unique:users,email,'.$user->id,'role_id'=>'required|exists:roles,id','active'=>'required|boolean','password'=>['nullable',PasswordRule::min(10)->letters()->numbers()]]);
  $role=Role::findOrFail($d['role_id']);abort_if($role->slug==='hospede',422);
  if($user->id===$r->user()->id&&(!$d['active']||$role->slug!=='admin'))return back()->withErrors(['active'=>'Você não pode remover seu próprio acesso administrativo.']);
  $fields=['name'=>$d['name'],'email'=>$d['email'],'role_id'=>$role->id,'active'=>$d['active']];if(!empty($d['password']))$fields['password']=Hash::make($d['password']);
  $user->update($fields);AuditService::record('funcionario.atualizado','user',$user->id);return back()->with('success','Funcionário atualizado.');
 }
}
