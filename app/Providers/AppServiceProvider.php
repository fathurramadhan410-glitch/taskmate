<?php

namespace App\Providers;

use App\Models\Notifikasi;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');

        // Data yang selalu tersedia di layout utama (sidebar + lonceng notifikasi)
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            $navWorkspaces = collect();
            $unreadCount = 0;
            $recentNotifs = collect();

            if ($user) {
                $navWorkspaces = Workspace::whereIn('id_workspace', $user->memberships()->select('id_workspace'))
                    ->orderBy('nama_workspace')
                    ->limit(8)
                    ->get();

                $unreadCount = $user->unreadCount();

                $recentNotifs = Notifikasi::where('id_user', $user->id_user)
                    ->orderByDesc('id_notif')
                    ->limit(4)
                    ->get();
            }

            $view->with(compact('navWorkspaces', 'unreadCount', 'recentNotifs'));
        });
    }
}
