<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Customer;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CustomerQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Busca un cliente por su número de documento, email, nombre o teléfono.
        Retorna todos los datos del cliente si existe, o indica que no existe.
        Útil para consultas como: "¿Existe el cliente con documento 1759474057?" o "Busca el cliente juan@example.com"
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        // Acceder a los argumentos usando all()
        $arguments = $request->all();
        $documentNumber = $arguments['document_number'] ?? null;
        $email = $arguments['email'] ?? null;
        $name = $arguments['name'] ?? null;
        $phone = $arguments['phone_number'] ?? null;

        if (!$documentNumber && !$email && !$name && !$phone) {
            return Response::error('Debe proporcionar al menos uno de: document_number, email, name, phone_number');
        }

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        $query = Customer::where('company_id', $companyId);

        if ($documentNumber) {
            $query->where('document_number', $documentNumber);
        }
        if ($email) {
            $query->where('email', $email);
        }
        if ($name) {
            // Si el nombre contiene espacios, buscar por nombre y apellido juntos
            $nameParts = explode(' ', trim($name));
            $query->where(function($q) use ($name, $nameParts) {
                if (count($nameParts) > 1) {
                    // Buscar por nombre y apellido juntos
                    $q->where(function($subQ) use ($nameParts) {
                        $subQ->where('first_name', 'like', "%{$nameParts[0]}%")
                             ->where('last_name', 'like', "%{$nameParts[1]}%");
                    })
                    ->orWhere(function($subQ) use ($nameParts) {
                        $subQ->where('first_name', 'like', "%{$nameParts[1]}%")
                             ->where('last_name', 'like', "%{$nameParts[0]}%");
                    });
                }
                // También buscar en cada campo individualmente
                $q->orWhere('first_name', 'like', "%{$name}%")
                  ->orWhere('last_name', 'like', "%{$name}%")
                  ->orWhere('business_name', 'like', "%{$name}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$name}%"]);
            });
        }
        if ($phone) {
            $query->where('phone_number', $phone);
        }

        $customers = $query->with(['category'])->limit(10)->get();

        if ($customers->isEmpty()) {
            return Response::text('<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se encontraron clientes con los criterios proporcionados.</p></div>');
        }

        $output = '<div class="space-y-4">';
        $output .= '<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Cliente(s) Encontrado(s)</h2>';
        
        foreach ($customers as $customer) {
            $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Tipo:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->customer_type ?? 'N/A') . '</span></div>';
            
            // Mostrar nombre y apellido para clientes individuales (CEDULA)
            // Mostrar razón social para empresas (RUC)
            $isCedula = strtoupper($customer->document_type ?? '') === 'CEDULA';
            $isRuc = strtoupper($customer->document_type ?? '') === 'RUC';
            $isIndividual = strtolower($customer->customer_type ?? '') === 'individual';
            $isBusiness = strtolower($customer->customer_type ?? '') === 'business';
            
            if ($isCedula || $isIndividual) {
                // Cliente normal (individual/cedula): mostrar nombre y apellido
                $fullName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                if ($fullName) {
                    $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Nombre:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($fullName) . '</span></div>';
                }
            } elseif ($isRuc || $isBusiness) {
                // Empresa (RUC/business): mostrar razón social
                if ($customer->business_name) {
                    $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Razón Social:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->business_name) . '</span></div>';
                }
            } else {
                // Fallback: mostrar lo que esté disponible
                $fullName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                if ($fullName) {
                    $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Nombre:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($fullName) . '</span></div>';
                }
                if ($customer->business_name) {
                    $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Razón Social:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->business_name) . '</span></div>';
                }
            }
            
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Documento:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars(($customer->document_type ?? 'N/A') . ' - ' . ($customer->document_number ?? 'N/A')) . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Email:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->email ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Teléfono:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->phone_number ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Dirección:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->address ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Estado:</span><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($customer->status === 'A' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400') . '">' . ($customer->status === 'A' ? 'Activo' : 'Inactivo') . '</span></div>';
            
            if ($customer->category) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Categoría:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->category->name) . '</span></div>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'document_number' => $schema->string()
                ->nullable()
                ->description('Número de documento del cliente'),
            'email' => $schema->string()
                ->nullable()
                ->description('Email del cliente'),
            'name' => $schema->string()
                ->nullable()
                ->description('Nombre del cliente. Puede ser nombre completo (ej: "Juan Pérez"), solo nombre (ej: "Juan"), solo apellido (ej: "Pérez"), o razón social (ej: "Empresa XYZ"). Busca en first_name, last_name, business_name y también en nombre completo combinado.'),
            'phone_number' => $schema->string()
                ->nullable()
                ->description('Número de teléfono del cliente'),
        ];
    }
}

