<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?Task $task,
        public string $pesan,
        public string $jenis = 'penugasan'
    ) {}

    public function envelope(): Envelope
    {
        $prefix = match ($this->jenis) {
            'h3' => 'Pengingat H-3',
            'h1' => 'Pengingat H-1',
            'hari_h' => 'Deadline Hari Ini',
            'terlambat' => 'Tugas Terlambat',
            'review' => 'Tugas Masuk Review',
            'penugasan' => 'Tugas Baru',
            'perubahan' => 'Tugas Diperbarui',
            'bergabung' => 'Anggota Baru',
            default => 'Notifikasi',
        };

        return new Envelope(
            subject: '[TaskMate] '.$prefix.($this->task ? ': '.$this->task->judul : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder',
            with: [
                'nama' => $this->user->nama,
                'task' => $this->task,
                'pesan' => $this->pesan,
                'jenis' => $this->jenis,
            ],
        );
    }
}
