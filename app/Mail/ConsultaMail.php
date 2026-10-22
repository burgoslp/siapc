<?php

namespace App\Mail;

use App\Models\Consulta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Empresa;
use Illuminate\Support\Facades\Storage;

class ConsultaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $consulta;
    public $empresa;

    public function __construct(Consulta $consulta)
    {
        $this->consulta = $consulta;
        $this->empresa = Empresa::pluck('valor', 'clave')->toArray();
    }

    public function envelope(): Envelope
    {
        $nombreClinica = $this->empresa['nombre_clinica'] ?? 'Clínica Dental';
        return new Envelope(
            subject: 'Receta Médica - ' . $nombreClinica,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.consulta',
        );
    }

    public function attachments(): array
    {
        // Generamos el mismo PDF que creamos antes
        $logoPath = null;
        if (!empty($this->empresa['logo']) && Storage::disk('public')->exists($this->empresa['logo'])) {
            $logoPath = Storage::disk('public')->path($this->empresa['logo']);
        }

        $pdf = Pdf::loadView('pdf.consulta', [
            'consulta' => $this->consulta, 
            'empresa' => $this->empresa,
            'logoPath' => $logoPath
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'Receta_Medica.pdf')
                ->withMime('application/pdf'),
        ];
    }
}