<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Mailable sent to the admin/manager when an async report export has finished.
 *
 * The generated file is attached directly to the email so the recipient can
 * download it without needing to log back into the system.
 *
 * Validates: Requirement 15.4
 */
class ReportExportReady extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  string  $type      'sales' | 'stock'
     * @param  string  $format    'excel' | 'pdf'
     * @param  string  $filename  The base filename (e.g. "laporan-penjualan-2025-01-15_120000.xlsx")
     * @param  string  $filePath  Storage-relative path (e.g. "exports/laporan-penjualan-…xlsx")
     */
    public function __construct(
        public readonly string $type,
        public readonly string $format,
        public readonly string $filename,
        public readonly string $filePath,
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $typeLabel = $this->type === 'sales' ? 'Penjualan' : 'Stok';

        return new Envelope(
            subject: "Laporan {$typeLabel} Siap Diunduh",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.report-export-ready',
            with: [
                'type'     => $this->type,
                'format'   => $this->format,
                'filename' => $this->filename,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $fullPath = Storage::disk('local')->path($this->filePath);

        if (! file_exists($fullPath)) {
            return [];
        }

        $mimeType = $this->format === 'excel'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'application/pdf';

        return [
            Attachment::fromPath($fullPath)
                ->as($this->filename)
                ->withMime($mimeType),
        ];
    }
}
