<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombre;
    public $token;
    public $resetUrl;

    public function __construct($usuario, $token, $nombre, $email)
    {
        $this->nombre = $nombre;
        $this->token = $token;
        $this->resetUrl = rtrim(env('FRONTEND_URL', 'https://jhp-frontend-production.up.railway.app'), '/') 
            . '/reset-password.html?token=' . urlencode($token) . '&email=' . urlencode($email);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperación de Contraseña - JHP Motos POS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'nombre' => $this->nombre,
                'resetUrl' => $this->resetUrl,
                'token' => $this->token,
            ],
        );
    }
}