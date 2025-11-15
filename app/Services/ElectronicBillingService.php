<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\DocumentSequence;
use App\Models\Product;
use App\Models\Service;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SoapClient;
use SoapFault;

class ElectronicBillingService
{
    /**
     * Genera el XML de la factura según estándar SRI
     */
    public function generateInvoiceXml(Invoice $invoice): string
    {
        $company = $invoice->company;
        $branch = $invoice->branch;
        $customer = $invoice->customer;
        $items = $invoice->items;
        
        // Obtener secuencial del invoice_number (formato: 001-001-001)
        $parts = explode('-', $invoice->invoice_number);
        $sequential = str_pad($parts[2] ?? '001', 9, '0', STR_PAD_LEFT);
        
        // Obtener DocumentSequence para establishment y emission point
        $sequence = DocumentSequence::where('company_id', $company->id)
            ->where('document_type', 'FACTURA')
            ->where('status', 'A')
            ->first();
        
        $establishmentCode = $sequence->establishment_code ?? '001';
        $emissionPointCode = $sequence->emission_point_code ?? '001';
        
        // Determinar ambiente según company.sri_environment
        $environment = ($company->sri_environment === 'production') ? '2' : '1';
        
        // Fecha de emisión
        $issueDate = $invoice->issue_date instanceof Carbon 
            ? $invoice->issue_date 
            : Carbon::parse($invoice->issue_date);
        
        // Crear XML
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Root: factura
        $root = $xml->createElement('factura');
        $root->setAttribute('id', 'comprobante');
        $root->setAttribute('version', '1.1.0');
        $xml->appendChild($root);
        
        // infoTributaria
        $infoTributaria = $xml->createElement('infoTributaria');
        $infoTributaria->appendChild($xml->createElement('ambiente', $environment));
        $infoTributaria->appendChild($xml->createElement('tipoEmision', '1'));
        $infoTributaria->appendChild($xml->createElement('razonSocial', $this->escapeXml($company->legal_name ?? $company->name)));
        $infoTributaria->appendChild($xml->createElement('nombreComercial', $this->escapeXml($company->name)));
        $infoTributaria->appendChild($xml->createElement('ruc', $company->number_tax ?? '9999999999999'));
        $infoTributaria->appendChild($xml->createElement('claveAcceso', '')); // Se llenará después
        $infoTributaria->appendChild($xml->createElement('codDoc', '01')); // 01 = Factura
        $infoTributaria->appendChild($xml->createElement('estab', $establishmentCode));
        $infoTributaria->appendChild($xml->createElement('ptoEmi', $emissionPointCode));
        $infoTributaria->appendChild($xml->createElement('secuencial', $sequential));
        $infoTributaria->appendChild($xml->createElement('dirMatriz', $this->escapeXml($company->address ?? '')));
        $root->appendChild($infoTributaria);
        
        // infoFactura
        $infoFactura = $xml->createElement('infoFactura');
        $infoFactura->appendChild($xml->createElement('fechaEmision', $issueDate->format('d/m/Y')));
        $infoFactura->appendChild($xml->createElement('dirEstablecimiento', $this->escapeXml($branch->address ?? $company->address ?? '')));
        $infoFactura->appendChild($xml->createElement('obligadoContabilidad', 'NO')); // Por defecto NO
        $infoFactura->appendChild($xml->createElement('tipoIdentificacionComprador', $this->mapDocumentType($customer->document_type ?? 'CEDULA')));
        $infoFactura->appendChild($xml->createElement('razonSocialComprador', $this->escapeXml($customer->display_name ?? 'CONSUMIDOR FINAL')));
        $infoFactura->appendChild($xml->createElement('identificacionComprador', $customer->document_number ?? '9999999999'));
        $infoFactura->appendChild($xml->createElement('direccionComprador', $this->escapeXml($customer->address ?? '')));
        $infoFactura->appendChild($xml->createElement('totalSinImpuestos', number_format($invoice->subtotal, 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('totalDescuento', '0.00'));
        $infoFactura->appendChild($xml->createElement('totalImpuesto', number_format($invoice->tax_amount, 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('importeTotal', number_format($invoice->total_amount, 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('moneda', 'USD'));
        $root->appendChild($infoFactura);
        
        // totalConImpuestos
        $totalConImpuestos = $xml->createElement('totalConImpuestos');
        $totalImpuesto = $xml->createElement('totalImpuesto');
        $totalImpuesto->appendChild($xml->createElement('codigo', '2')); // IVA
        $totalImpuesto->appendChild($xml->createElement('codigoPorcentaje', '3')); // 15%
        $totalImpuesto->appendChild($xml->createElement('baseImponible', number_format($invoice->subtotal, 2, '.', '')));
        $totalImpuesto->appendChild($xml->createElement('valor', number_format($invoice->tax_amount, 2, '.', '')));
        $totalConImpuestos->appendChild($totalImpuesto);
        $infoFactura->appendChild($totalConImpuestos);
        
        // detalles
        $detalles = $xml->createElement('detalles');
        foreach ($items as $item) {
            $detalle = $xml->createElement('detalle');
            $detalle->appendChild($xml->createElement('codigoPrincipal', substr($item->item_id, 0, 25))); // Máximo 25 caracteres
            $detalle->appendChild($xml->createElement('descripcion', $this->escapeXml($this->getItemName($item))));
            $detalle->appendChild($xml->createElement('cantidad', number_format($item->quantity, 2, '.', '')));
            $detalle->appendChild($xml->createElement('precioUnitario', number_format($item->unit_price, 2, '.', '')));
            $detalle->appendChild($xml->createElement('descuento', '0.00'));
            $detalle->appendChild($xml->createElement('precioTotalSinImpuesto', number_format($item->subtotal, 2, '.', '')));
            
            // impuestos
            $impuestos = $xml->createElement('impuestos');
            $impuesto = $xml->createElement('impuesto');
            $impuesto->appendChild($xml->createElement('codigo', '2')); // IVA
            $impuesto->appendChild($xml->createElement('codigoPorcentaje', '3')); // 15%
            $impuesto->appendChild($xml->createElement('tarifa', '15.00'));
            $impuesto->appendChild($xml->createElement('baseImponible', number_format($item->subtotal, 2, '.', '')));
            $impuesto->appendChild($xml->createElement('valor', number_format($item->subtotal * 0.15, 2, '.', '')));
            $impuestos->appendChild($impuesto);
            $detalle->appendChild($impuestos);
            
            $detalles->appendChild($detalle);
        }
        $root->appendChild($detalles);
        
        // Generar clave de acceso
        $accessKey = $this->generateAccessKey($company, $establishmentCode, $emissionPointCode, $sequential, $issueDate, $environment);
        
        // Actualizar clave de acceso en XML
        $xpath = new DOMXPath($xml);
        $claveAccesoNode = $xpath->query('//claveAcceso')->item(0);
        if ($claveAccesoNode) {
            $claveAccesoNode->nodeValue = $accessKey;
        }
        
        return $xml->saveXML();
    }
    
    /**
     * Genera la clave de acceso del documento
     */
    private function generateAccessKey(
        Company $company,
        string $establishmentCode,
        string $emissionPointCode,
        string $sequential,
        Carbon $issueDate,
        string $environment
    ): string {
        // Formato: YYYYMMDD + TipoComprobante + Ruc + Ambiente + Serie + NumeroSecuencial + CodigoNumerico + TipoEmision
        $date = $issueDate->format('Ymd');
        $documentType = '01'; // Factura
        $ruc = str_pad($company->number_tax ?? '9999999999999', 13, '0', STR_PAD_LEFT);
        $series = $establishmentCode . $emissionPointCode;
        $sequentialPadded = str_pad($sequential, 9, '0', STR_PAD_LEFT);
        $numericCode = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        $emissionType = '1'; // Normal
        
        $key = $date . $documentType . $ruc . $environment . $series . $sequentialPadded . $numericCode . $emissionType;
        
        // Calcular dígito verificador
        $multipliers = [2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1];
        $sum = 0;
        for ($i = 0; $i < strlen($key); $i++) {
            $digit = (int) $key[$i];
            $multiplier = $multipliers[$i];
            $product = $digit * $multiplier;
            if ($product >= 10) {
                $product = (int) floor($product / 10) + ($product % 10);
            }
            $sum += $product;
        }
        $remainder = $sum % 11;
        $checkDigit = ($remainder < 2) ? $remainder : 11 - $remainder;
        
        return $key . $checkDigit;
    }
    
    /**
     * Firma el XML con el certificado .p12
     */
    public function signXmlWithP12(string $xmlContent, Company $company): string
    {
        // Obtener ruta del certificado
        $certificatePath = storage_path("app/companies/{$company->id}/certificate.p12");
        
        if (!file_exists($certificatePath)) {
            throw new Exception("Certificado .p12 no encontrado para la empresa {$company->id}");
        }
        
        // Obtener contraseña
        $password = $company->digital_signature_password 
            ? decrypt($company->digital_signature_password) 
            : '';
        
        if (empty($password)) {
            throw new Exception("Contraseña del certificado no configurada");
        }
        
        // Cargar certificado
        $certificateData = file_get_contents($certificatePath);
        $certificates = [];
        if (!openssl_pkcs12_read($certificateData, $certificates, $password)) {
            throw new Exception("Error al leer el certificado .p12: " . openssl_error_string());
        }
        
        // Cargar XML
        $xml = new DOMDocument();
        $xml->loadXML($xmlContent);
        
        // Crear firma digital
        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $objDSig->addReferenceList(
            [$xml->documentElement],
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['id_name' => 'comprobante']
        );
        
        // Agregar clave privada
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $objKey->loadKey($certificates['pkey'], false);
        $objDSig->sign($objKey);
        
        // Agregar certificado
        $objDSig->add509Cert($certificates['cert'], true);
        
        // Insertar firma en XML
        $objDSig->appendSignature($xml->documentElement);
        
        return $xml->saveXML();
    }
    
    /**
     * Envía el XML firmado al SRI (Recepción)
     */
    public function sendToSRIReception(Invoice $invoice): array
    {
        $company = $invoice->company;
        $environment = $company->sri_environment ?? 'development';
        
        // Determinar URL según ambiente
        $receptionUrl = config("electronic_billing.sri.reception.{$environment}");
        
        if (!$receptionUrl) {
            throw new Exception("URL de recepción del SRI no configurada para ambiente: {$environment}");
        }
        
        try {
            // Crear cliente SOAP
            $soapClient = new SoapClient($receptionUrl, [
                'soap_version' => SOAP_1_1,
                'trace' => true,
                'exceptions' => true,
                'stream_context' => stream_context_create([
                    'http' => [
                        'timeout' => 30,
                    ],
                ]),
            ]);
            
            // Preparar XML para envío (base64)
            $xmlBase64 = base64_encode($invoice->xml_signed);
            
            // Llamar al método de recepción
            $response = $soapClient->validarComprobante([
                'xml' => $xmlBase64,
            ]);
            
            // Procesar respuesta
            $result = [
                'success' => false,
                'received' => false,
                'errors' => [],
                'warnings' => [],
            ];
            
            if (isset($response->RespuestaRecepcionComprobante)) {
                $respuesta = $response->RespuestaRecepcionComprobante;
                $result['received'] = ($respuesta->estado === 'RECIBIDA');
                
                if (isset($respuesta->comprobantes->comprobante)) {
                    $comprobante = $respuesta->comprobantes->comprobante;
                    if (isset($comprobante->mensajes)) {
                        if (isset($comprobante->mensajes->mensaje)) {
                            $mensajes = is_array($comprobante->mensajes->mensaje) 
                                ? $comprobante->mensajes->mensaje 
                                : [$comprobante->mensajes->mensaje];
                            
                            foreach ($mensajes as $mensaje) {
                                if ($mensaje->tipo === 'ERROR') {
                                    $result['errors'][] = [
                                        'identificador' => $mensaje->identificador ?? '',
                                        'mensaje' => $mensaje->mensaje ?? '',
                                        'tipo' => $mensaje->tipo ?? '',
                                    ];
                                } elseif ($mensaje->tipo === 'ADVERTENCIA') {
                                    $result['warnings'][] = [
                                        'identificador' => $mensaje->identificador ?? '',
                                        'mensaje' => $mensaje->mensaje ?? '',
                                        'tipo' => $mensaje->tipo ?? '',
                                    ];
                                }
                            }
                        }
                    }
                }
            }
            
            $result['success'] = $result['received'] && empty($result['errors']);
            
            return $result;
            
        } catch (SoapFault $e) {
            Log::error("Error SOAP al enviar al SRI: " . $e->getMessage());
            throw new Exception("Error SOAP al enviar al SRI: " . $e->getMessage());
        } catch (Exception $e) {
            Log::error("Error al enviar al SRI: " . $e->getMessage());
            throw new Exception("Error al enviar al SRI: " . $e->getMessage());
        }
    }
    
    /**
     * Autoriza el documento en el SRI
     */
    public function authorizeWithSRI(Invoice $invoice): array
    {
        $company = $invoice->company;
        $environment = $company->sri_environment ?? 'development';
        
        // Determinar URL según ambiente
        $authorizationUrl = config("electronic_billing.sri.authorization.{$environment}");
        
        if (!$authorizationUrl) {
            throw new Exception("URL de autorización del SRI no configurada para ambiente: {$environment}");
        }
        
        if (!$invoice->access_key) {
            throw new Exception("La factura no tiene clave de acceso");
        }
        
        try {
            // Crear cliente SOAP
            $soapClient = new SoapClient($authorizationUrl, [
                'soap_version' => SOAP_1_1,
                'trace' => true,
                'exceptions' => true,
                'stream_context' => stream_context_create([
                    'http' => [
                        'timeout' => 30,
                    ],
                ]),
            ]);
            
            // Llamar al método de autorización
            $response = $soapClient->autorizacionComprobante([
                'claveAccesoComprobante' => $invoice->access_key,
            ]);
            
            // Procesar respuesta
            $result = [
                'success' => false,
                'authorized' => false,
                'authorization_number' => null,
                'authorization_date' => null,
                'xml_authorized' => null,
                'errors' => [],
            ];
            
            if (isset($response->RespuestaAutorizacionComprobante)) {
                $respuesta = $response->RespuestaAutorizacionComprobante;
                $result['authorized'] = ($respuesta->estado === 'AUTORIZADO');
                
                if ($result['authorized'] && isset($respuesta->numeroAutorizacion)) {
                    $result['authorization_number'] = $respuesta->numeroAutorizacion;
                    $result['authorization_date'] = isset($respuesta->fechaAutorizacion) 
                        ? Carbon::parse($respuesta->fechaAutorizacion) 
                        : now();
                    $result['xml_authorized'] = isset($respuesta->comprobante) 
                        ? base64_decode($respuesta->comprobante) 
                        : null;
                } else {
                    // Hay errores
                    if (isset($respuesta->autorizaciones->autorizacion->mensajes->mensaje)) {
                        $mensajes = is_array($respuesta->autorizaciones->autorizacion->mensajes->mensaje)
                            ? $respuesta->autorizaciones->autorizacion->mensajes->mensaje
                            : [$respuesta->autorizaciones->autorizacion->mensajes->mensaje];
                        
                        foreach ($mensajes as $mensaje) {
                            $result['errors'][] = [
                                'identificador' => $mensaje->identificador ?? '',
                                'mensaje' => $mensaje->mensaje ?? '',
                                'tipo' => $mensaje->tipo ?? '',
                            ];
                        }
                    }
                }
            }
            
            $result['success'] = $result['authorized'];
            
            return $result;
            
        } catch (SoapFault $e) {
            Log::error("Error SOAP al autorizar en el SRI: " . $e->getMessage());
            throw new Exception("Error SOAP al autorizar en el SRI: " . $e->getMessage());
        } catch (Exception $e) {
            Log::error("Error al autorizar en el SRI: " . $e->getMessage());
            throw new Exception("Error al autorizar en el SRI: " . $e->getMessage());
        }
    }
    
    /**
     * Genera el PDF de la factura
     */
    public function generateInvoicePdf(Invoice $invoice): string
    {
        $company = $invoice->company;
        $branch = $invoice->branch;
        $customer = $invoice->customer;
        $items = $invoice->items;
        
        // Cargar productos y servicios
        $itemIds = $items->pluck('item_id')->unique()->toArray();
        $products = Product::whereIn('id', $itemIds)->get()->keyBy('id');
        $services = Service::whereIn('id', $itemIds)->get()->keyBy('id');
        
        // Generar código de barras de la clave de acceso
        $barcodeImage = null;
        if ($invoice->access_key) {
            try {
                $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                $barcodeImage = base64_encode($barcodeGenerator->getBarcode(
                    $invoice->access_key,
                    $barcodeGenerator::TYPE_CODE_128,
                    2,
                    50
                ));
            } catch (\Exception $e) {
                Log::warning("Error al generar código de barras: " . $e->getMessage());
            }
        }
        
        // Cargar pagos de la factura
        $invoice->load('payments');
        
        // Renderizar vista Blade a HTML
        $html = view('Sales.Invoices.PDF', [
            'invoice' => $invoice,
            'company' => $company,
            'branch' => $branch,
            'customer' => $customer,
            'items' => $items,
            'products' => $products,
            'services' => $services,
            'barcodeImage' => $barcodeImage,
        ])->render();
        
        // Generar PDF con Dompdf
        $dompdf = new \Dompdf\Dompdf([
            'isPhpEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        
        // Guardar PDF
        $pdfPath = "invoices/{$invoice->id}/invoice.pdf";
        Storage::makeDirectory("invoices/{$invoice->id}");
        Storage::put($pdfPath, $dompdf->output());
        
        return $pdfPath;
    }
    
    /**
     * Proceso completo: generar XML, firmar, enviar y autorizar
     */
    public function processInvoice(Invoice $invoice, bool $autoAuthorize = false): array
    {
        try {
            // 1. Generar XML
            $xmlContent = $this->generateInvoiceXml($invoice);
            
            // 2. Firmar XML
            $xmlSigned = $this->signXmlWithP12($xmlContent, $invoice->company);
            
            // 3. Extraer clave de acceso del XML firmado
            $xmlDoc = new DOMDocument();
            $xmlDoc->loadXML($xmlSigned);
            $xpath = new DOMXPath($xmlDoc);
            $accessKeyNode = $xpath->query('//claveAcceso')->item(0);
            $accessKey = $accessKeyNode ? $accessKeyNode->nodeValue : null;
            
            // 4. Actualizar factura
            $invoice->update([
                'xml_signed' => $xmlSigned,
                'access_key' => $accessKey,
                'sri_status' => 'draft',
                'sri_environment' => $invoice->company->sri_environment ?? 'development',
            ]);
            
            // 5. Enviar a recepción del SRI
            $receptionResult = $this->sendToSRIReception($invoice);
            
            if (!$receptionResult['success']) {
                $invoice->update([
                    'sri_status' => 'rejected',
                    'sri_errors' => $receptionResult['errors'],
                ]);
                
                return [
                    'success' => false,
                    'step' => 'reception',
                    'errors' => $receptionResult['errors'],
                    'warnings' => $receptionResult['warnings'],
                ];
            }
            
            $invoice->update([
                'sri_status' => 'received',
            ]);
            
            // 6. Autorizar si se solicita
            if ($autoAuthorize) {
                $authorizationResult = $this->authorizeWithSRI($invoice);
                
                if ($authorizationResult['success']) {
                    $invoice->update([
                        'sri_status' => 'authorized',
                        'authorization_number' => $authorizationResult['authorization_number'],
                        'authorized_at' => $authorizationResult['authorization_date'],
                        'xml_authorized' => $authorizationResult['xml_authorized'],
                    ]);
                    
                    // Generar PDF
                    $pdfPath = $this->generateInvoicePdf($invoice);
                    
                    return [
                        'success' => true,
                        'access_key' => $accessKey,
                        'authorization_number' => $authorizationResult['authorization_number'],
                        'pdf_path' => $pdfPath,
                    ];
                } else {
                    return [
                        'success' => false,
                        'step' => 'authorization',
                        'errors' => $authorizationResult['errors'],
                    ];
                }
            }
            
            return [
                'success' => true,
                'access_key' => $accessKey,
                'received' => true,
            ];
            
        } catch (Exception $e) {
            Log::error("Error en processInvoice: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    // Métodos auxiliares
    
    private function escapeXml(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1, 'UTF-8');
    }
    
    private function mapDocumentType(string $documentType): string
    {
        return match($documentType) {
            'RUC' => '04',
            'CEDULA' => '05',
            'PASAPORTE' => '06',
            default => '05',
        };
    }
    
    private function getItemName(InvoiceItem $item): string
    {
        if ($item->item_type === 'product') {
            $product = Product::find($item->item_id);
            return $product?->name ?? 'Producto';
        } elseif ($item->item_type === 'service') {
            $service = Service::find($item->item_id);
            return $service?->name ?? 'Servicio';
        }
        return 'Item';
    }
}

