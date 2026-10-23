<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceChat;
use Illuminate\Http\Request;

/**
 * Chat realtime per workspace (permintaan tambahan pengguna).
 *
 * Implementasi "pure chat": pesan teks yang tersimpan di database dan
 * diperbarui otomatis melalui polling AJAX tiap 2 detik (aman untuk
 * XAMPP lokal tanpa WebSocket/Node).
 */
class ChatController extends Controller
{
    public function show(Request $request, Workspace $workspace)
    {
        $messages = $workspace->chats()
            ->with('user:id_user,nama,avatar')
            ->orderByDesc('id_chat')
            ->limit(80)
            ->get()
            ->reverse()
            ->values();

        $lastId = (int) ($messages->last()->id_chat ?? 0);

        return view('chat.show', compact('workspace', 'messages', 'lastId'));
    }

    /** API: ambil pesan baru sejak id tertentu (dipakai polling JS) */
    public function fetch(Request $request, Workspace $workspace)
    {
        $after = (int) $request->query('after', 0);

        $messages = $workspace->chats()
            ->with('user:id_user,nama,avatar')
            ->where('id_chat', '>', $after)
            ->orderBy('id_chat')
            ->limit(200)
            ->get();

        return response()->json([
            'messages' => $messages->map(fn ($m) => $this->bentukPesan($m, $request->user()))->values(),
        ]);
    }

    /** Kirim pesan baru */
    public function send(Request $request, Workspace $workspace)
    {
        $data = $request->validate([
            'pesan' => ['required', 'string', 'max:1000'],
        ], [
            'pesan.required' => 'Pesan tidak boleh kosong.',
            'pesan.max' => 'Pesan maksimal 1000 karakter.',
        ]);

        $chat = WorkspaceChat::create([
            'id_workspace' => $workspace->id_workspace,
            'id_user' => $request->user()->id_user,
            'pesan' => trim($data['pesan']),
        ]);

        $chat->load('user:id_user,nama,avatar');

        return response()->json(['message' => $this->bentukPesan($chat, $request->user())]);
    }

    protected function bentukPesan(WorkspaceChat $m, $user): array
    {
        return [
            'id' => $m->id_chat,
            'pesan' => $m->pesan,
            'waktu' => $m->created_at?->format('H:i') ?? '',
            'tanggal' => $m->created_at?->format('d/m/Y') ?? '',
            'own' => $user && $m->id_user === $user->id_user,
            'user' => [
                'nama' => $m->user->nama ?? 'Akun terhapus',
                'avatar' => $m->user?->avatar_url,
                'initial' => initial($m->user->nama ?? '?'),
            ],
        ];
    }
}
