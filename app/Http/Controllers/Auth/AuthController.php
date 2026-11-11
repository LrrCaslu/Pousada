<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\{User,Guest,Role};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Hash,DB,Password};
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
class AuthController extends Controller {
 public function show(){return view('auth.login');}
 public function login(Request $r){
    $d=$r->validate(['email'=>'required|email','password'=>'required|string']);
    if(!Auth::attempt(['email'=>$d['email'],'password'=>$d['password'],'active'=>true],$r->boolean('remember')))return back()->withErrors(['email'=>'Credenciais inválidas.'])->onlyInput('email');
    $r->session()->regenerate();
    return redirect()->intended(auth()->user()->hasRole('hospede')?route('guest.dashboard'):route('admin.dashboard'));
 }
 public function registerForm(){return view('auth.register');}
 public function register(Request $r){
   $d=$r->validate(['name'=>'required|string|max:120','email'=>'required|email|max:180|unique:users','password'=>['required','confirmed',PasswordRule::min(10)->letters()->numbers()],'phone'=>'nullable|string|max:30']);
   $user=DB::transaction(function()use($d){
     $role=Role::where('slug','hospede')->firstOrFail();
     $u=User::create(['role_id'=>$role->id,'name'=>$d['name'],'email'=>$d['email'],'password'=>Hash::make($d['password']),'active'=>true]);
     Guest::create(['user_id'=>$u->id,'name'=>$d['name'],'email'=>$d['email'],'phone'=>$d['phone']??null]);
     return $u;
   });
   Auth::login($user);$r->session()->regenerate();return redirect()->route('guest.dashboard');
 }
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('home');}
 public function forgot(){return view('auth.forgot');}
 public function sendReset(Request $r){$r->validate(['email'=>'required|email']);Password::sendResetLink($r->only('email'));return back()->with('success','Se o e-mail existir, enviaremos instruções. Em modo local consulte storage/logs/laravel.log.');}
 public function resetForm(string $token,Request $r){return view('auth.reset',['token'=>$token,'email'=>$r->query('email')]);}
 public function reset(Request $r){
  $d=$r->validate(['token'=>'required','email'=>'required|email','password'=>['required','confirmed',PasswordRule::min(10)->letters()->numbers()]]);
  $status=Password::reset($d,function(User $u,string $password){$u->forceFill(['password'=>Hash::make($password),'remember_token'=>Str::random(60)])->save();event(new PasswordReset($u));});
  return $status===Password::PASSWORD_RESET?redirect()->route('login')->with('success','Senha alterada.'):back()->withErrors(['email'=>__($status)]);
 }
}
