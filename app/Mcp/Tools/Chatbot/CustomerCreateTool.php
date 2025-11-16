<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Customer;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CustomerCreateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Crea un nuevo cliente en el sistema. Puede crear clientes individuales o empresas.
        Para clientes individuales requiere: first_name, last_name, document_type, document_number.
        Para empresas requiere: business_name, document_type, document_number.
        Opcionalmente puede incluir: email, phone_number, address.
        Útil para consultas como: "Registra el cliente JOEZER NEWMAN con cédula 1759474057 y correo paul.newman.dev@gmail.com"
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        // Acceder a los argumentos usando get() o all()
        $arguments = $request->all();

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        // Determinar tipo de cliente
        $customerType = $arguments['customer_type'] ?? 'INDIVIDUAL';
        $customerType = strtoupper($customerType);

        if (!in_array($customerType, ['INDIVIDUAL', 'BUSINESS'])) {
            return Response::error('El tipo de cliente debe ser INDIVIDUAL o BUSINESS');
        }

        // Validar campos requeridos según el tipo
        if ($customerType === 'INDIVIDUAL') {
            if (empty($arguments['first_name']) || empty($arguments['last_name'])) {
                return Response::error('Para clientes individuales se requiere first_name y last_name');
            }
        } else {
            if (empty($arguments['business_name'])) {
                return Response::error('Para empresas se requiere business_name');
            }
        }

        // Validar documento
        if (empty($arguments['document_type']) || empty($arguments['document_number'])) {
            return Response::error('Se requiere document_type (CEDULA, RUC, PASAPORTE) y document_number');
        }

        $documentType = strtoupper(trim($arguments['document_type']));
        if (!in_array($documentType, ['CEDULA', 'RUC', 'PASAPORTE'])) {
            return Response::error('document_type debe ser CEDULA, RUC o PASAPORTE');
        }

        $documentNumber = Str::upper(trim($arguments['document_number']));

        // Verificar si el cliente ya existe
        $existingCustomer = Customer::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->where('document_number', $documentNumber)
            ->first();

        if ($existingCustomer) {
            return Response::error("Ya existe un cliente con el documento {$documentType} {$documentNumber}");
        }

        // Verificar email si se proporciona
        if (!empty($arguments['email'])) {
            $email = Str::lower(trim($arguments['email']));
            $existingEmail = Customer::where('company_id', $companyId)
                ->where('email', $email)
                ->first();

            if ($existingEmail) {
                return Response::error("Ya existe un cliente con el email {$email}");
            }
        }

        // Preparar datos del cliente
        $customerData = [
            'company_id' => $companyId,
            'customer_type' => $customerType,
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'status' => 'A',
        ];

        if ($customerType === 'INDIVIDUAL') {
            $customerData['first_name'] = trim($arguments['first_name']);
            $customerData['last_name'] = trim($arguments['last_name']);
        } else {
            $customerData['business_name'] = trim($arguments['business_name']);
        }

        if (!empty($arguments['email'])) {
            $customerData['email'] = Str::lower(trim($arguments['email']));
        }

        if (!empty($arguments['phone_number'])) {
            $customerData['phone_number'] = trim($arguments['phone_number']);
        }

        if (!empty($arguments['address'])) {
            $customerData['address'] = trim($arguments['address']);
        }

        if (!empty($arguments['sex'])) {
            $customerData['sex'] = strtoupper(trim($arguments['sex']));
        }

        if (!empty($arguments['birth_date'])) {
            $customerData['birth_date'] = $arguments['birth_date'];
        }

        // Crear el cliente
        try {
            $customer = Customer::create($customerData);
            $customer->load('category');

            $output = "## Cliente Creado Exitosamente\n\n";
            $output .= "**ID:** {$customer->id}\n";
            $output .= "**Tipo:** {$customer->customer_type}\n";
            
            if ($customer->customer_type === 'INDIVIDUAL') {
                $output .= "**Nombre:** {$customer->first_name} {$customer->last_name}\n";
            } else {
                $output .= "**Razón Social:** {$customer->business_name}\n";
            }
            
            $output .= "**Documento:** {$customer->document_type} - {$customer->document_number}\n";
            
            if ($customer->email) {
                $output .= "**Email:** {$customer->email}\n";
            }
            
            if ($customer->phone_number) {
                $output .= "**Teléfono:** {$customer->phone_number}\n";
            }
            
            if ($customer->address) {
                $output .= "**Dirección:** {$customer->address}\n";
            }
            
            $output .= "**Estado:** Activo\n";

            return Response::text($output);
        } catch (\Exception $e) {
            return Response::error('Error al crear el cliente: ' . $e->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_type' => $schema->string()
                ->enum(['INDIVIDUAL', 'BUSINESS'])
                ->default('INDIVIDUAL')
                ->description('Tipo de cliente: INDIVIDUAL o BUSINESS'),
            'first_name' => $schema->string()
                ->nullable()
                ->description('Nombre del cliente (requerido para INDIVIDUAL)'),
            'last_name' => $schema->string()
                ->nullable()
                ->description('Apellido del cliente (requerido para INDIVIDUAL)'),
            'business_name' => $schema->string()
                ->nullable()
                ->description('Razón social (requerido para BUSINESS)'),
            'document_type' => $schema->string()
                ->enum(['CEDULA', 'RUC', 'PASAPORTE'])
                ->description('Tipo de documento: CEDULA, RUC o PASAPORTE'),
            'document_number' => $schema->string()
                ->description('Número de documento'),
            'email' => $schema->string()
                ->nullable()
                ->description('Email del cliente'),
            'phone_number' => $schema->string()
                ->nullable()
                ->description('Número de teléfono del cliente'),
            'address' => $schema->string()
                ->nullable()
                ->description('Dirección del cliente'),
            'sex' => $schema->string()
                ->nullable()
                ->enum(['M', 'F'])
                ->description('Sexo del cliente (M o F, solo para INDIVIDUAL)'),
            'birth_date' => $schema->string()
                ->nullable()
                ->description('Fecha de nacimiento (formato: YYYY-MM-DD, solo para INDIVIDUAL)'),
        ];
    }
}

