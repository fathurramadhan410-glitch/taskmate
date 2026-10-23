<?php

use App\Http\Middleware\EnsureWorkspaceMember;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'workspace.member' => EnsureWorkspaceMember::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Pesan ramah ketika unggahan melebihi batas php.ini (upload_max_filesize / post_max_size)
        $exceptions->render(function (Illuminate\Http\Exceptions\PostTooLargeException $e, Request $request) {
            return back()->with('gagal', 'Unggahan terlalu besar untuk server. Perbesar "upload_max_filesize" dan "post_max_size" pada php.ini (lihat README).');
        });
    })->create();
