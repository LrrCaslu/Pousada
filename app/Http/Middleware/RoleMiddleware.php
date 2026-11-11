<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class RoleMiddleware {
 public function handle(Request $request, Closure $next, string ...$roles): Response {
   abort_unless($request->user() && $request->user()->active && $request->user()->hasAnyRole($roles),403,'Acesso não autorizado.');
   return $next($request);
 }
}
