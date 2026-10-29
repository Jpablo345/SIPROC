@component('mail::message')
# Restablecer contraseña

Hola,

Recibiste este correo porque se solicitó restablecer la contraseña de tu cuenta en **SIPROC - Facultad de Ingeniería UFPSO**.

@component('mail::button', ['url' => $actionUrl, 'color' => 'red'])
Restablecer contraseña
@endcomponent

Este enlace expirará en **60 minutos**.

Si no solicitaste restablecer tu contraseña, ignora este mensaje.

Saludos,<br>
**SIPROC - Universidad Francisco de Paula Santander Ocaña**

@component('mail::subcopy')
Si el botón no funciona, copia y pega este enlace en tu navegador: [{{ $actionUrl }}]({{ $actionUrl }})
@endcomponent
@endcomponent