<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // 1. Actualizar datos del User (username, email)
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // 2. Si el usuario es médico, actualizar sus datos profesionales
        if ($user->medico) {
            $medico = $user->medico;
            
            $medicoData = [
                'nombre' => $request->input('nombre'),
                'apellido' => $request->input('apellido'),
                'telefono' => $request->input('telefono'),
            ];

            // Manejo de la firma digital
            if ($request->hasFile('firmaFile')) {
                // Borrar la firma anterior si existe
                if ($medico->firma_path && Storage::disk('public')->exists($medico->firma_path)) {
                    Storage::disk('public')->delete($medico->firma_path);
                }
                // Guardar la nueva
                $medicoData['firma_path'] = $request->file('firmaFile')->store('firmas', 'public');
            }

            $medico->update($medicoData);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}