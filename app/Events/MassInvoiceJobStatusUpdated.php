<?php

namespace App\Events;

use App\Models\JobBatch; // <-- Gunakan JobBatch
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
//use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MassInvoiceJobStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     *
     * @param JobBatch $jobBatch Instance dari job batch yang sedang berjalan.
     * @param string $status Status saat ini ('processing', 'finished', 'failed').
     * @param int $progress Progress saat ini (0-100).
     * @param string $message Pesan yang akan ditampilkan.
     */
    public function __construct(
        public JobBatch $jobBatch,
        public string $status,
        public int $progress,
        public string $message
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // PERBAIKAN: Ambil user ID dari relasi di JobBatch
        return [
            new PrivateChannel('App.Models.User.' . $this->jobBatch->user_id),
        ];
    }

    /**
     * Nama event untuk frontend.
     */
    public function broadcastAs(): string
    {
        return 'mass-invoice.status';
    }

    /**
     * Data yang akan dikirim bersama event.
     */
    public function broadcastWith(): array
    {
        return [
            'jobBatchId' => $this->jobBatch->id,
            'status' => $this->status,
            'progress' => $this->progress,
            'message' => $this->message,
        ];
    }
}
