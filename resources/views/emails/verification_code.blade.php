@extends('emails.default')

@section('conteudo')
    <div class="header">
        Verification Code
    </div>
    <div class="content">
        <p>Dear {{ ucfirst($name) }},</p>
        <p>Thank you for signing up. Please use the code below to verify your email address:</p>

        <p class="code">{{ $code }}</p>

        <p>If you did not request this email, please ignore it.</p>
        <p>Best regards,<br>The Team</p>
    </div>
@endsection
