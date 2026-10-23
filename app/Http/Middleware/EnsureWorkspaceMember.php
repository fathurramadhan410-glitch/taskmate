<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan hanya anggota workspace yang dapat mengaksesnya (SRS 2.5.f).
 */
class EnsureWorkspaceMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $workspace = $request->route('workspace');

        $isMember = $user
            && $workspace instanceof Workspace
            && $workspace->members()->where('id_user', $user->id_user)->exists();

        if (! $isMember) {
            abort(403, 'Anda bukan anggota workspace ini, sehingga tidak dapat mengakses halamannya.');
        }

        return $next($request);
    }
}
