<?php

namespace App\Http\Controllers\User;

use App\Contracts\SeoMetaManagerContract;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Controlador para gestionar el perfil personal del usuario autenticado.
 */
class UserProfileController extends Controller
{
    /**
     * @param SeoMetaManagerContract $seoMetaManager Gestor centralizado de metadatos SEO.
     */
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    /**
     * Muestra la página de perfil del usuario autenticado.
     *
     * @param Request $request Petición HTTP entrante.
     *
     * @return View
     */
    public function show(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Mi perfil',
            'description' => 'Consulta y actualiza tu información personal y contraseña.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Mi perfil')],
        ];

        $avatarUrl = $user->avatar_url ?: '/theme-images/user/owner.jpg';

        // Mapear valores de la base de datos a valores del formulario
        $genderMapping = [
            'MALE' => 'M',
            'FEMALE' => 'F',
            'OTHER' => 'O',
        ];

        // Crear una copia del usuario con el gender mapeado para la vista
        $userForView = clone $user;
        if ($user->gender && isset($genderMapping[$user->gender])) {
            $userForView->gender = $genderMapping[$user->gender];
        }

        return view('User.Profile', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'user' => $userForView,
            'avatarUrl' => $avatarUrl,
        ]);
    }

    /**
     * Actualiza la información personal del usuario autenticado.
     *
     * @param Request $request Petición con los datos del usuario.
     *
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'document_number' => ['nullable', 'string', 'max:50'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', Rule::in(['M', 'F', 'O'])],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,bmp,gif', 'max:5120'],
        ], [
            'first_name.required' => gettext('El nombre es obligatorio.'),
            'first_name.max' => gettext('El nombre no puede exceder 100 caracteres.'),
            'last_name.required' => gettext('El apellido es obligatorio.'),
            'last_name.max' => gettext('El apellido no puede exceder 100 caracteres.'),
            'email.required' => gettext('El correo electrónico es obligatorio.'),
            'email.email' => gettext('Proporciona un correo electrónico válido.'),
            'email.unique' => gettext('Este correo electrónico ya está en uso.'),
            'document_number.max' => gettext('El número de documento no puede exceder 50 caracteres.'),
            'phone_number.max' => gettext('El número de teléfono no puede exceder 50 caracteres.'),
            'date_of_birth.date' => gettext('La fecha de nacimiento debe ser una fecha válida.'),
            'date_of_birth.before' => gettext('La fecha de nacimiento debe ser anterior a hoy.'),
            'gender.in' => gettext('Selecciona un género válido.'),
            'avatar.file' => gettext('El avatar debe ser un archivo válido.'),
            'avatar.mimes' => gettext('El avatar debe ser una imagen JPG, JPEG, PNG, BMP o GIF.'),
            'avatar.max' => gettext('El avatar no debe superar los 5MB.'),
        ]);

        $avatarFile = $request->file('avatar');

        // Mapear valores del formulario a valores de la base de datos
        $genderMapping = [
            'M' => 'MALE',
            'F' => 'FEMALE',
            'O' => 'OTHER',
        ];

        if (isset($validated['gender']) && isset($genderMapping[$validated['gender']])) {
            $validated['gender'] = $genderMapping[$validated['gender']];
        }

        $user->fill(Arr::except($validated, ['avatar']));

        if ($avatarFile) {
            $user->avatar_url = $this->storeAvatar($avatarFile, $user);
        }

        $user->save();

        NotificationHelper::record(
            $user->company_id,
            'Tu información personal fue actualizada exitosamente.',
            $user->id,
            [
                'title' => 'Actualización de perfil',
                'category' => 'Usuario',
            ]
        );

        return redirect()
            ->route('profile.show')
            ->with('status', gettext('Actualizamos tus datos personales.'));
    }

    /**
     * Actualiza la contraseña del usuario autenticado.
     *
     * @param Request $request Petición con la nueva contraseña.
     *
     * @return RedirectResponse
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_password.required' => gettext('La nueva contraseña es obligatoria.'),
            'new_password.min' => gettext('La nueva contraseña debe tener al menos 8 caracteres.'),
            'new_password.confirmed' => gettext('Las contraseñas no coinciden.'),
        ]);

        // Actualizar contraseña
        $user->password_hash = Hash::make($validated['new_password']);
        $user->save();

        NotificationHelper::record(
            $user->company_id,
            'Tu contraseña fue actualizada exitosamente.',
            $user->id,
            [
                'title' => 'Actualización de contraseña',
                'category' => 'Usuario',
            ]
        );

        return redirect()
            ->route('profile.show')
            ->with('status', gettext('Tu contraseña fue actualizada correctamente.'));
    }

    /**
     * Guarda el avatar del usuario en public/avatars/{user_id}.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param \App\Models\User $user
     *
     * @return string Ruta pública relativa al avatar.
     */
    private function storeAvatar(\Illuminate\Http\UploadedFile $file, \App\Models\User $user): string
    {
        $directory = public_path('avatars/' . $user->id);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Eliminar avatar anterior si existe
        if ($user->avatar_url && Str::startsWith($user->avatar_url, '/avatars/')) {
            $oldPath = public_path(ltrim($user->avatar_url, '/'));
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        $extension = $file->getClientOriginalExtension() ?: 'png';
        $filename = 'avatar_' . now()->format('YmdHis') . '_' . Str::random(6) . '.' . $extension;
        $file->move($directory, $filename);

        return '/avatars/' . $user->id . '/' . $filename;
    }
}

