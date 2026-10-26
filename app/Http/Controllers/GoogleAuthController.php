<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        // Sin stateless(): Socialite guarda y valida el parámetro "state" (protección CSRF del login).
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return $this->fail('No se pudo completar el inicio de sesión con Google. Inténtalo de nuevo.');
        }

        $email = strtolower((string) $googleUser->getEmail());

        // Solo permite correos @ufpso.edu.co
        if (!str_ends_with($email, '@ufpso.edu.co')) {
            return $this->fail('Solo se permite el acceso con correo institucional @ufpso.edu.co');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return $this->fail('No existe una cuenta registrada con este correo institucional.');
        }

        // Mismas condiciones que el login con contraseña: registro verificado por OTP y con rol asignado.
        if (!$user->email_verified_at) {
            return $this->fail('Tu cuenta aún no está verificada. Completa el registro con el código enviado a tu correo.');
        }

        if ($user->roles()->doesntExist()) {
            return $this->fail('Tu cuenta no tiene un rol asignado. Contacta al administrador.');
        }

        Auth::login($user);
        session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function fail(string $message)
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
