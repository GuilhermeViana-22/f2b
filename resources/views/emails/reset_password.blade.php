@extends('emails.default')

@section('conteudo')
    <div class="header">
        Password Change Request
    </div>
    <div class="content">
        <p>Dear {{ ucfirst($name) }},</p>
        <p>We received a request to change your password. Please use the following code to verify your email address and proceed with the password change:</p>

        <p class="code">{{ $code }}</p>

        <p>If you did not request this change, please contact the administrators as soon as possible.</p>
    </div>
@endsection
