<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\ElectronicBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateTestInvoiceDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:generate-test-documents {invoice_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera XML y PDF de prueba para una factura existente';

    /**
     * Execute the console command.
     */
    public function handle(ElectronicBillingService $billingService): int
    {
        $invoiceId = $this->argument('invoice_id');
        
        // Si no se proporciona ID, buscar la primera factura disponible
        if (!$invoiceId) {
            $invoice = Invoice::where('document_type', 'FACTURA')
                ->with(['company', 'branch', 'customer', 'items'])
                ->first();
            
            if (!$invoice) {
                $this->error('No se encontraron facturas en la base de datos.');
                return Command::FAILURE;
            }
            
            $this->info("Usando factura: {$invoice->invoice_number} (ID: {$invoice->id})");
        } else {
            $invoice = Invoice::where('document_type', 'FACTURA')
                ->with(['company', 'branch', 'customer', 'items'])
                ->find($invoiceId);
            
            if (!$invoice) {
                $this->error("No se encontró la factura con ID: {$invoiceId}");
                return Command::FAILURE;
            }
        }
        
        // Verificar que la compañía tenga certificado
        $certificatePath = storage_path("app/companies/{$invoice->company_id}/certificate.p12");
        if (!file_exists($certificatePath)) {
            $this->warn("⚠️  Certificado .p12 no encontrado en: {$certificatePath}");
            $this->warn("   El certificado debe estar en: storage/app/companies/{$invoice->company_id}/certificate.p12");
            $this->warn("   Generando XML sin firmar para prueba...");
            
            // Generar solo XML sin firmar
            try {
                $xmlContent = $billingService->generateInvoiceXml($invoice);
                
                // Guardar XML sin firmar
                $xmlPath = "test/invoices/{$invoice->id}/invoice_unsigned.xml";
                Storage::makeDirectory("test/invoices/{$invoice->id}");
                Storage::put($xmlPath, $xmlContent);
                
                $this->info("✓ XML generado (sin firmar): " . Storage::path($xmlPath));
                
                // Intentar generar PDF (puede fallar sin certificado)
                try {
                    $pdfPath = $billingService->generateInvoicePdf($invoice);
                    $this->info("✓ PDF generado: " . Storage::path($pdfPath));
                } catch (\Exception $e) {
                    $this->warn("⚠️  No se pudo generar PDF: " . $e->getMessage());
                }
                
                return Command::SUCCESS;
            } catch (\Exception $e) {
                $this->error("Error al generar XML: " . $e->getMessage());
                return Command::FAILURE;
            }
        }
        
        $this->info("Generando XML y PDF para factura: {$invoice->invoice_number}");
        $this->info("Compañía: {$invoice->company->name}");
        $this->info("Cliente: {$invoice->customer->display_name}");
        $this->info("Total: USD " . number_format($invoice->total_amount, 2));
        
        try {
            // Generar XML y firmar (sin enviar al SRI)
            $this->info("\n1. Generando XML...");
            $xmlContent = $billingService->generateInvoiceXml($invoice);
            
            $this->info("2. Firmando XML con certificado...");
            $xmlSigned = $billingService->signXmlWithP12($xmlContent, $invoice->company);
            
            // Extraer clave de acceso
            $xmlDoc = new \DOMDocument();
            $xmlDoc->loadXML($xmlSigned);
            $xpath = new \DOMXPath($xmlDoc);
            $accessKeyNode = $xpath->query('//claveAcceso')->item(0);
            $accessKey = $accessKeyNode ? $accessKeyNode->nodeValue : 'N/A';
            
            $this->info("   Clave de acceso: {$accessKey}");
            
            // Guardar XML firmado
            $xmlPath = "test/invoices/{$invoice->id}/invoice_signed.xml";
            Storage::makeDirectory("test/invoices/{$invoice->id}");
            Storage::put($xmlPath, $xmlSigned);
            
            $this->info("✓ XML firmado guardado: " . Storage::path($xmlPath));
            
            // Actualizar factura con XML y clave de acceso
            $invoice->update([
                'xml_signed' => $xmlSigned,
                'access_key' => $accessKey,
                'sri_status' => 'draft',
                'sri_environment' => $invoice->company->sri_environment ?? 'development',
            ]);
            
            $this->info("\n3. Generando PDF...");
            $pdfPath = $billingService->generateInvoicePdf($invoice);
            
            $this->info("✓ PDF generado: " . Storage::path($pdfPath));
            
            // Copiar PDF a carpeta de prueba para fácil acceso
            $testPdfPath = "test/invoices/{$invoice->id}/invoice.pdf";
            Storage::put($testPdfPath, Storage::get($pdfPath));
            
            $this->info("✓ PDF copiado a: " . Storage::path($testPdfPath));
            
            $this->info("\n✅ Documentos generados exitosamente!");
            $this->info("\nArchivos generados:");
            $this->info("  - XML firmado: " . Storage::path($xmlPath));
            $this->info("  - PDF: " . Storage::path($testPdfPath));
            $this->info("\nPuedes revisar los archivos en:");
            $this->info("  " . storage_path("app/test/invoices/{$invoice->id}/"));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
