<?php

namespace App\Http\Controllers\Company;

use App\Contracts\SeoMetaManagerContract;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Controlador para gestionar el perfil corporativo de la compañía del usuario autenticado.
 */
class CompanyProfileController extends Controller
{
    /**
     * @param SeoMetaManagerContract $seoMetaManager Gestor centralizado de metadatos SEO.
     */
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    /**
     * Muestra la ficha informativa de la compañía con sus datos principales.
     *
     * @param Request $request Petición HTTP entrante.
     *
     * @return View|RedirectResponse
     */
    public function show(Request $request): View|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $company = $user->company;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (!$company) {
            $company = Company::query()->first();
            
            if (!$company) {
                return redirect()
                    ->route('dashboard')
                    ->with('error', gettext('No hay empresas registradas en el sistema.'));
            }
        }

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Mi empresa',
            'description' => 'Consulta y administra la información corporativa de tu empresa en MercuryApp.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Configuración'), 'url' => route('configuration.company')],
            ['label' => gettext('Mi empresa')],
        ];

        return view('Configuration.Company', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'company' => $company,
            'taxRegimeTypes' => Company::TAX_REGIME_TYPES,
        ]);
    }

    /**
     * Actualiza la información general de la compañía del usuario autenticado.
     *
     * @param Request $request Petición con los datos de la compañía.
     *
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $company = $user->company;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (!$company) {
            $company = Company::query()->first();
            
            if (!$company) {
                return redirect()
                    ->route('dashboard')
                    ->with('error', gettext('No hay empresas registradas en el sistema.'));
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'type_tax' => ['required', Rule::in(Company::TAX_REGIME_TYPES)],
            'number_tax' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'theme_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,bmp,gif', 'max:10240'],
            'digital_certificate' => ['nullable', 'file', 'mimes:p12', 'max:10240'],
            'digital_signature_password' => ['nullable', 'string', 'min:4'],
            'sri_environment' => ['required', 'in:development,production'],
        ], [
            'name.required' => gettext('El nombre comercial es obligatorio.'),
            'name.max' => gettext('El nombre comercial no puede exceder 150 caracteres.'),
            'legal_name.max' => gettext('La razón social no puede exceder 255 caracteres.'),
            'type_tax.required' => gettext('Selecciona un tipo de régimen.'),
            'type_tax.in' => gettext('Selecciona un tipo de régimen válido.'),
            'number_tax.max' => gettext('El número fiscal no puede exceder 50 caracteres.'),
            'email.email' => gettext('Proporciona un correo electrónico válido.'),
            'theme_color.regex' => gettext('El color corporativo debe tener formato hexadecimal, por ejemplo #001A35.'),
            'logo.file' => gettext('El logo debe ser un archivo válido.'),
            'logo.mimes' => gettext('El logo debe ser una imagen JPG, JPEG, PNG, BMP o GIF.'),
            'logo.max' => gettext('El logo no debe superar los 10MB.'),
            'digital_certificate.file' => gettext('La firma digital debe ser un archivo válido.'),
            'digital_certificate.mimes' => gettext('La firma digital debe ser un archivo con extensión .p12.'),
            'digital_certificate.max' => gettext('La firma digital no debe superar los 10MB.'),
            'digital_signature_password.min' => gettext('La contraseña debe tener al menos 4 caracteres.'),
            'sri_environment.required' => gettext('Selecciona un ambiente del SRI.'),
            'sri_environment.in' => gettext('Selecciona un ambiente válido.'),
        ]);

        $logoFile = $request->file('logo');
        $certificateFile = $request->file('digital_certificate');

        $company->fill(Arr::except($validated, ['logo', 'digital_certificate', 'digital_signature_password']));

        // Encriptar contraseña del certificado si se proporciona
        if ($request->filled('digital_signature_password')) {
            $company->digital_signature_password = encrypt($request->digital_signature_password);
        }

        // Asignar ambiente SRI
        $company->sri_environment = $request->sri_environment;

        if ($logoFile) {
            $company->logo_url = $this->storeLogo($logoFile, $company);
        }

        if ($certificateFile) {
            $company->digital_url = $this->storeDigitalCertificate($certificateFile, $company);
        }

        $company->save();

        NotificationHelper::record(
            $company->id,
            'La información de la empresa fue actualizada exitosamente.',
            $user->id,
            [
                'title' => 'Actualización de empresa',
                'category' => 'Empresa',
            ]
        );

        return redirect()
            ->route('configuration.company')
            ->with('status', gettext('Actualizamos los datos de tu empresa.'));
    }
    /**
     * Guarda el logo de la empresa en public/logos/{company_id}.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param Company $company
     *
     * @return string Ruta pública relativa al logo.
     */
    private function storeLogo(\Illuminate\Http\UploadedFile $file, Company $company): string
    {
        $directory = public_path('logos/' . $company->id);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $this->deleteExistingPublicFile($company->logo_url, 'logos');

        $extension = $file->getClientOriginalExtension() ?: 'png';
        $filename = 'logo_' . now()->format('YmdHis') . '_' . Str::random(6) . '.' . $extension;
        $file->move($directory, $filename);

        return '/logos/' . $company->id . '/' . $filename;
    }

    /**
     * Guarda la firma digital de la empresa en storage/app/companies/{company_id}/certificate.p12.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param Company $company
     *
     * @return string Ruta relativa al archivo de firma (para referencia en BD).
     */
    private function storeDigitalCertificate(\Illuminate\Http\UploadedFile $file, Company $company): string
    {
        // Eliminar certificado anterior si existe
        $oldPath = "companies/{$company->id}/certificate.p12";
        if (Storage::exists($oldPath)) {
            Storage::delete($oldPath);
        }

        // Guardar nuevo certificado en storage/app/companies/{company_id}/certificate.p12
        $path = Storage::putFileAs(
            "companies/{$company->id}",
            $file,
            'certificate.p12'
        );

        // Establecer permisos restrictivos (600) para seguridad
        $fullPath = Storage::path($path);
        if (file_exists($fullPath)) {
            chmod($fullPath, 0600);
        }

        // Retornar ruta relativa para almacenar en BD (solo referencia)
        return $path;
    }

    /**
     * Elimina un archivo viejo dentro de public/{baseDirectory}/...
     *
     * @param string|null $relativePath Ruta relativa almacenada en BD.
     * @param string $baseDirectory Directorio base permitido (logos o firmas).
     *
     * @return void
     */
    private function deleteExistingPublicFile(?string $relativePath, string $baseDirectory): void
    {
        if (empty($relativePath)) {
            return;
        }

        $relativePath = ltrim($relativePath, '/');
        if (! Str::startsWith($relativePath, $baseDirectory . '/')) {
            return;
        }

        $absolutePath = public_path($relativePath);
        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }
}
