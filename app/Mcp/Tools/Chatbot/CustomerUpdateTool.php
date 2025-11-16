<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Customer;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CustomerUpdateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Actualiza datos de un cliente existente. Puede actualizar: teléfono, email, dirección, nombre, apellido, razón social, estado, etc.
        Requiere el número de documento del cliente para identificarlo.
        Sinónimos: actualizar cliente, modificar cliente, cambiar datos del cliente, editar cliente, actualizar teléfono del cliente, cambiar email del cliente.
        Ejemplos: "actualiza el teléfono del cliente 1759474057 a 0999767814", "cambia el email del cliente con cédula 1759474057".
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $arguments = $request->all();

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        // Identificar cliente
        $documentNumber = $arguments['document_number'] ?? null;
        $email = $arguments['email'] ?? null;
        $customerId = $arguments['customer_id'] ?? null;

        if (!$documentNumber && !$email && !$customerId) {
            return Response::error('Debe proporcionar document_number, email o customer_id para identificar al cliente');
        }

        // Buscar cliente
        $query = Customer::where('company_id', $companyId);

        if ($customerId) {
            $query->where('id', $customerId);
        } elseif ($documentNumber) {
            $query->where('document_number', $documentNumber);
        } elseif ($email) {
            $query->where('email', $email);
        }

        $customer = $query->first();

        if (!$customer) {
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontró el cliente especificado.</p></div>');
        }

        // Preparar datos a actualizar
        $updateData = [];

        if (isset($arguments['phone_number'])) {
            $updateData['phone_number'] = $arguments['phone_number'];
        }

        if (isset($arguments['email'])) {
            // Validar que el email no esté en uso por otro cliente
            $emailExists = Customer::where('company_id', $companyId)
                ->where('email', $arguments['email'])
                ->where('id', '!=', $customer->id)
                ->exists();
            
            if ($emailExists) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">El email ' . htmlspecialchars($arguments['email']) . ' ya está registrado para otro cliente.</p></div>');
            }
            
            $updateData['email'] = $arguments['email'];
        }

        if (isset($arguments['address'])) {
            $updateData['address'] = $arguments['address'];
        }

        if (isset($arguments['first_name']) && $customer->customer_type === 'INDIVIDUAL') {
            $updateData['first_name'] = $arguments['first_name'];
        }

        if (isset($arguments['last_name']) && $customer->customer_type === 'INDIVIDUAL') {
            $updateData['last_name'] = $arguments['last_name'];
        }

        if (isset($arguments['business_name']) && $customer->customer_type === 'BUSINESS') {
            $updateData['business_name'] = $arguments['business_name'];
        }

        if (isset($arguments['status'])) {
            if (!in_array($arguments['status'], ['A', 'I'])) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">El estado debe ser A (Activo) o I (Inactivo).</p></div>');
            }
            $updateData['status'] = $arguments['status'];
        }

        if (empty($updateData)) {
            return Response::text('<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se proporcionaron datos para actualizar.</p></div>');
        }

        try {
            $customer->update($updateData);
            $customer->refresh();

            $output = '<div class="space-y-4">';
            $output .= '<div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">';
            $output .= '<p class="text-green-800 dark:text-green-200 font-semibold mb-2">✓ Cliente actualizado correctamente</p>';
            $output .= '</div>';
            
            $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">';
            $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Datos Actualizados</h3>';
            
            if ($customer->customer_type === 'INDIVIDUAL') {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Nombre:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->first_name . ' ' . $customer->last_name) . '</span></div>';
            } else {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Razón Social:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->business_name) . '</span></div>';
            }
            
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Documento:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->document_type . ' - ' . $customer->document_number) . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Email:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->email ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Teléfono:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->phone_number ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Dirección:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($customer->address ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Estado:</span><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($customer->status === 'A' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400') . '">' . ($customer->status === 'A' ? 'Activo' : 'Inactivo') . '</span></div>';
            $output .= '</div></div>';

            return Response::text($output);
        } catch (\Exception $e) {
            Log::error("Error al actualizar cliente: " . $e->getMessage());
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Error al actualizar el cliente: ' . htmlspecialchars($e->getMessage()) . '</p></div>');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'document_number' => $schema->string()
                ->nullable()
                ->description('Número de documento del cliente a actualizar'),
            'email' => $schema->string()
                ->nullable()
                ->description('Email del cliente a actualizar (puede ser para identificar o para actualizar)'),
            'customer_id' => $schema->string()
                ->nullable()
                ->description('ID del cliente a actualizar'),
            'phone_number' => $schema->string()
                ->nullable()
                ->description('Nuevo número de teléfono'),
            'address' => $schema->string()
                ->nullable()
                ->description('Nueva dirección'),
            'first_name' => $schema->string()
                ->nullable()
                ->description('Nuevo nombre (solo para clientes individuales)'),
            'last_name' => $schema->string()
                ->nullable()
                ->description('Nuevo apellido (solo para clientes individuales)'),
            'business_name' => $schema->string()
                ->nullable()
                ->description('Nueva razón social (solo para empresas)'),
            'status' => $schema->string()
                ->enum(['A', 'I'])
                ->nullable()
                ->description('Nuevo estado: A (Activo) o I (Inactivo)'),
        ];
    }
}

