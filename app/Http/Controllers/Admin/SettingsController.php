<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;
class SettingsController extends Controller {
 private const KEYS=['pousada_nome','descricao','endereco','telefone','whatsapp','email','instagram','checkin_hora','checkout_hora','politica_cancelamento'];
 public function index(){return view('admin.settings.index',['settings'=>Setting::pluck('value','key')]);}
 public function update(Request $r){$rules=[];foreach(self::KEYS as $key)$rules[$key]='nullable|string|max:3000';$d=$r->validate($rules);foreach($d as $k=>$v)Setting::updateOrCreate(['key'=>$k],['value'=>$v]);AuditService::record('configuracoes.atualizadas');return back()->with('success','Configurações salvas.');}
}
