@component('mail::message')
# Olá,

Você foi convidado a criar sua conta de cliente na WuWallet. Clique no botão abaixo para completar seu cadastro!

@component('mail::button', ['url' => $inviteUrl])
Finalizar cadastro
@endcomponent

Este convite foi enviado para **{{ $email }}** e é valido por 48h.

Se você não esperava este convite, pode ignorá-lo.

Atenciosamente,<br>
{{ config('app.name') }}
@endcomponent
