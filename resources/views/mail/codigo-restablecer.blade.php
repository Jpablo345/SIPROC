<x-mail::message>
# Restablecer contraseña

Hola, {{ $nombre }}:

Usa este código en **SIPROC** para crear una nueva contraseña:

<x-mail::panel>
<div style="font-size: 30px; font-weight: 700; letter-spacing: 8px; text-align: center;">{{ $codigo }}</div>
</x-mail::panel>

El código vence en **{{ $minutos }} minutos** y solo sirve en el navegador donde lo pediste.

Si no pediste cambiar tu contraseña, ignora este correo: la actual sigue funcionando. No compartas este código con nadie.

Saludos,<br>
**SIPROC - Universidad Francisco de Paula Santander Ocaña**
</x-mail::message>
