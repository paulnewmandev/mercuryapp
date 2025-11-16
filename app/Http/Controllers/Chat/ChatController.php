<?php

namespace App\Http\Controllers\Chat;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        private readonly SeoMetaManagerContract $seoMetaManager
    ) {
        // El middleware 'auth' se aplica en las rutas
    }

    /**
     * Muestra la página principal del chat.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        $conversations = ChatConversation::where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->with('latestMessage')
            ->latest('updated_at')
            ->limit(50)
            ->get();

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · AI Chat',
            'description' => 'Chat con IA asistente para MercuryApp',
        ])->toArray();

        return view('Chat.Index', [
            'meta' => $meta,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Muestra una conversación específica.
     */
    public function show(Request $request, ChatConversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        $messages = $conversation->messages()->get();

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Chat - ' . ($conversation->title ?? 'Nueva conversación'),
        ])->toArray();

        return view('Chat.Index', [
            'meta' => $meta,
            'conversation' => $conversation,
            'messages' => $messages,
            'conversations' => ChatConversation::where('user_id', Auth::id())
                ->where('company_id', Auth::user()->company_id)
                ->with('latestMessage')
                ->latest('updated_at')
                ->limit(50)
                ->get(),
        ]);
    }

    /**
     * Crea una nueva conversación.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'model' => ['required', 'string', 'in:deepseek-chat,gemini-1.5-pro'],
        ]);

        $conversation = ChatConversation::create([
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id,
            'title' => $validated['title'] ?? null,
            'model' => $validated['model'],
        ]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Elimina una conversación.
     */
    public function destroy(ChatConversation $conversation): JsonResponse
    {
        Gate::authorize('delete', $conversation);

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversación eliminada correctamente',
        ]);
    }

    /**
     * Envía un mensaje y obtiene respuesta del LLM con integración MCP.
     */
    public function sendMessage(Request $request, ChatConversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $userMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        // Obtener historial de mensajes
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        try {
            // Preparar mensajes para el LLM con instrucciones del sistema
            $systemMessage = [
                'role' => 'system',
                'content' => 'Eres un asistente útil que responde preguntas sobre un sistema de gestión empresarial. SIEMPRE debes responder usando HTML formateado para mejorar la presentación. Usa etiquetas HTML como <div>, <p>, <strong>, <em>, <ul>, <li>, <table>, <tr>, <td>, <h2>, <h3>, etc. para estructurar tus respuestas. Nunca respondas con texto plano sin formato. Cuando muestres datos de clientes, productos, órdenes, facturas, etc., usa HTML bien estructurado con clases CSS apropiadas. Las respuestas deben ser visualmente atractivas y fáciles de leer.

REGLAS CRÍTICAS SOBRE HERRAMIENTAS:
1. NUNCA inventes nombres de herramientas. Solo usa EXACTAMENTE los nombres que están en la lista de herramientas disponibles.
2. Para obtener DETALLES COMPLETOS de una orden de reparación cuando el usuario pida "detalles de la orden X", "información de la orden X", "datos de la orden X", "muéstrame la orden X": DEBES usar EXACTAMENTE la herramienta llamada "get_workshop_order_by_number" (no "get_workshop_orders_by_number_prefix" ni ningún otro nombre). El parámetro debe ser "order_number" con el número completo como string (ej: "001-001-001").
3. Para consultar SOLO el estado: usa "get_workshop_order_status".
4. Para buscar clientes: usa "search_customer_by_document".
5. Para estadísticas del sistema: usa "get_system_statistics".
6. Para ESTADÍSTICAS DE UN CLIENTE ESPECÍFICO (compras, órdenes, facturas): Cuando el usuario pida "estadísticas del cliente X", "compras y órdenes del cliente X", "dame sus estadísticas compras órdenes", DEBES usar MÚLTIPLES herramientas en secuencia:
   - Primero: "search_customer_by_document" para obtener los datos del cliente
   - Segundo: "get_customer_invoices" para obtener las facturas/compras (puedes usar only_pending: false para todas)
   - Tercero: "get_customer_orders" para obtener las órdenes de reparación
   - Luego combina toda la información en una respuesta HTML bien formateada con resúmenes y totales.

Si no estás seguro del nombre de una herramienta, revisa la lista de herramientas disponibles antes de usarla. NUNCA inventes nombres.',
            ];

            $llmMessages = array_merge(
                [$systemMessage],
                array_map(function ($msg) {
                    return [
                        'role' => $msg['role'],
                        'content' => $msg['content'],
                    ];
                }, $messages)
            );

            // Intento de manejo directo de intención: consulta de producto por SKU/nombre/código de barras
            $directProduct = $this->tryDirectProductQuery($validated['message']);
            if ($directProduct !== null && trim($directProduct) !== '') {
                $assistantContent = $directProduct;
            } else {
                // Llamar al LLM
            try {
                $assistantContent = $this->callLLM($conversation->model, $llmMessages);
            } catch (\Exception $e) {
                \Log::error('Error al llamar al LLM: ' . $e->getMessage());
                
                // Crear mensaje de error amigable
                $assistantContent = '<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">';
                $assistantContent .= '<p class="text-red-800 dark:text-red-200 font-semibold mb-2">Error al procesar la solicitud</p>';
                
                if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'timed out') !== false) {
                    $assistantContent .= '<p class="text-red-700 dark:text-red-300">La solicitud tardó demasiado tiempo. Por favor, intenta nuevamente o simplifica tu consulta.</p>';
                } else {
                    $assistantContent .= '<p class="text-red-700 dark:text-red-300">' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                
                $assistantContent .= '</div>';
            }
            }

            // Fallback: nunca guardar un contenido vacío
            if (trim((string) $assistantContent) === '') {
                \Log::warning('Chat: contenido vacío recibido del LLM. Se aplicará fallback.', [
                    'conversation_id' => $conversation->id,
                    'user_id' => Auth::id(),
                ]);
                $assistantContent = '<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">';
                $assistantContent .= '<p class="text-yellow-900 dark:text-yellow-200 font-semibold mb-2">No se pudo generar una respuesta</p>';
                $assistantContent .= '<p class="text-yellow-800 dark:text-yellow-300 text-sm">Intenta reformular tu solicitud o proporciona más detalles. Si estabas consultando un producto, puedes usar el SKU o el nombre. Ejemplo: <em>"dame los datos del producto 00001146"</em>.</p>';
                $assistantContent .= '</div>';
            }

            $assistantMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $assistantContent,
            ]);

            // Actualizar título de conversación si es la primera interacción
            if (!$conversation->title && count($messages) === 1) {
                $conversation->update([
                    'title' => Str::limit($validated['message'], 50),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $assistantMessage,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en chat: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detecta consultas directas de producto y retorna HTML desde la tool sin pasar por el LLM.
     */
    private function tryDirectProductQuery(string $text): ?string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $text));
        // Buscar patrones que indiquen consulta de producto
        if (preg_match('/producto\s+(.+)$/iu', $normalized, $m)) {
            $raw = trim($m[1]);
            // Quitar comillas u otros delimitadores
            $token = trim($raw, " \t\n\r\0\x0B\"'`");

            $args = [];
            // Heurísticas:
            // - Solo dígitos de 8+ => probable código de barras
            if (preg_match('/^\d{8,}$/', $token)) {
                $args['barcode'] = $token;
            }
            // - Contiene guiones/puntos o mezcla alfanumérica => probable SKU
            elseif (preg_match('/^[A-Za-z0-9][A-Za-z0-9\-.]+$/', $token)) {
                $args['sku'] = $token;
            } else {
                // Nombre de producto (puede contener espacios)
                $args['product_names'] = [$token];
            }

            try {
                $result = $this->executeProductQuery($args);
                $content = $result['content'] ?? '';
                if (is_string($content) && trim($content) !== '') {
                    return $content;
                }
            } catch (\Throwable $e) {
                \Log::warning('tryDirectProductQuery fallo', ['error' => $e->getMessage(), 'args' => $args]);
            }
        }

        return null;
    }

    /**
     * Llama al LLM (DeepSeek o Gemini).
     */
    private function callLLM(string $model, array $messages): string
    {
        if ($model === 'deepseek-chat') {
            return $this->callDeepSeek($messages);
        } elseif ($model === 'gemini-1.5-pro') {
            return $this->callGemini($messages);
        }

        throw new \Exception("Modelo no soportado: {$model}");
    }

    /**
     * Llama a la API de DeepSeek con soporte para tools MCP.
     */
    private function callDeepSeek(array $messages): string
    {
        $apiKey = env('DEEPSEEK_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('DEEPSEEK_API_KEY no configurada');
        }

        // Obtener tools MCP disponibles
        $tools = $this->getMCPTools();

        $payload = [
            'model' => 'deepseek-chat',
            'messages' => $messages,
            'temperature' => 0.7,
            'stream' => false,
        ];

        // Agregar tools si están disponibles
        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        // Determinar timeout basado en si hay tools (primera llamada) o no (segunda llamada)
        $hasTools = !empty($payload['tools'] ?? []);
        $timeout = $hasTools ? 90 : 30; // Más tiempo para la primera llamada con tools
        
        $response = Http::timeout($timeout)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.deepseek.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            $errorBody = $response->body();
            $errorMessage = 'Error al llamar a DeepSeek';
            
            try {
                $errorData = $response->json();
                if (isset($errorData['error']['message'])) {
                    $errorMessage .= ': ' . $errorData['error']['message'];
                } else {
                    $errorMessage .= ': ' . $errorBody;
                }
            } catch (\Exception $e) {
                $errorMessage .= ': ' . $errorBody;
            }
            
            throw new \Exception($errorMessage);
        }

        $data = $response->json();
        $message = $data['choices'][0]['message'] ?? null;

        if (!$message) {
            return '';
        }

        // Si hay tool calls, ejecutarlos
        if (isset($message['tool_calls']) && !empty($message['tool_calls'])) {
            return $this->handleToolCalls($message['tool_calls'], $messages);
        }

        return $message['content'] ?? '';
    }

    /**
     * Llama a la API de Gemini.
     */
    private function callGemini(array $messages): string
    {
        $apiKey = env('GOOGLE_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('GOOGLE_API_KEY no configurada');
        }

        // Convertir formato de mensajes para Gemini
        $geminiMessages = array_map(function ($msg) {
            return [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }, $messages);

        $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key={$apiKey}", [
            'contents' => $geminiMessages,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Error al llamar a Gemini: ' . $response->body());
        }

        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    /**
     * Obtiene las tools MCP disponibles para el LLM.
     */
    private function getMCPTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_customer_by_document',
                    'description' => 'Busca un cliente por documento, email, nombre completo (nombre y apellido), nombre, apellido, razón social o teléfono. Retorna todos los datos del cliente en HTML formateado. Sinónimos: buscar cliente, encontrar cliente, consultar cliente, verificar cliente, cliente existe, datos del cliente, dame los datos del cliente, información del cliente, busca el cliente PAUL NEWMAN, dame los datos de Juan Pérez. USAR ESTA TOOL cuando el usuario pida datos de un cliente específico. Puede buscar por número de documento (ej: "1759474057") o por nombre completo (ej: "PAUL NEWMAN", "Juan Pérez"). Para clientes individuales (CEDULA) muestra nombre y apellido. Para empresas (RUC) muestra razón social.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'document_number' => [
                                'type' => 'string',
                                'description' => 'Número de documento del cliente (cédula, RUC, pasaporte). Ejemplos: "1759474057", "0991234567001"',
                            ],
                            'email' => [
                                'type' => 'string',
                                'description' => 'Email del cliente',
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Nombre del cliente. Puede ser nombre completo (ej: "PAUL NEWMAN", "Juan Pérez"), solo nombre (ej: "Juan"), solo apellido (ej: "Pérez"), o razón social (ej: "Empresa XYZ"). Busca en first_name, last_name, business_name y también en nombre completo combinado.',
                            ],
                            'phone_number' => [
                                'type' => 'string',
                                'description' => 'Número de teléfono del cliente',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_customer',
                    'description' => 'Crea o registra un nuevo cliente. Sinónimos: registrar cliente, agregar cliente, nuevo cliente, crear cliente, dar de alta cliente. Para individuales requiere nombre, apellido, tipo y número de documento. Para empresas requiere razón social, tipo y número de documento.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'customer_type' => [
                                'type' => 'string',
                                'enum' => ['INDIVIDUAL', 'BUSINESS'],
                                'description' => 'Tipo de cliente: INDIVIDUAL o BUSINESS',
                                'default' => 'INDIVIDUAL',
                            ],
                            'first_name' => [
                                'type' => 'string',
                                'description' => 'Nombre del cliente (requerido para INDIVIDUAL)',
                            ],
                            'last_name' => [
                                'type' => 'string',
                                'description' => 'Apellido del cliente (requerido para INDIVIDUAL)',
                            ],
                            'business_name' => [
                                'type' => 'string',
                                'description' => 'Razón social (requerido para BUSINESS)',
                            ],
                            'document_type' => [
                                'type' => 'string',
                                'enum' => ['CEDULA', 'RUC', 'PASAPORTE'],
                                'description' => 'Tipo de documento: CEDULA, RUC o PASAPORTE',
                            ],
                            'document_number' => [
                                'type' => 'string',
                                'description' => 'Número de documento',
                            ],
                            'email' => [
                                'type' => 'string',
                                'description' => 'Email del cliente',
                            ],
                            'phone_number' => [
                                'type' => 'string',
                                'description' => 'Número de teléfono del cliente',
                            ],
                            'address' => [
                                'type' => 'string',
                                'description' => 'Dirección del cliente',
                            ],
                            'sex' => [
                                'type' => 'string',
                                'enum' => ['M', 'F'],
                                'description' => 'Sexo del cliente (M o F, solo para INDIVIDUAL)',
                            ],
                            'birth_date' => [
                                'type' => 'string',
                                'description' => 'Fecha de nacimiento (formato: YYYY-MM-DD, solo para INDIVIDUAL)',
                            ],
                        ],
                        'required' => ['document_type', 'document_number'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_customer',
                    'description' => 'Actualiza datos de un cliente existente. Sinónimos: actualizar cliente, modificar cliente, cambiar datos del cliente, editar cliente, actualizar teléfono del cliente, cambiar email del cliente, modificar dirección del cliente. Ejemplos: "actualiza el teléfono del cliente 1759474057 a 0999767814", "cambia el email del cliente con cédula 1759474057".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'document_number' => [
                                'type' => 'string',
                                'description' => 'Número de documento del cliente a actualizar',
                            ],
                            'email' => [
                                'type' => 'string',
                                'description' => 'Email del cliente (puede ser para identificar o para actualizar)',
                            ],
                            'customer_id' => [
                                'type' => 'string',
                                'description' => 'ID del cliente a actualizar',
                            ],
                            'phone_number' => [
                                'type' => 'string',
                                'description' => 'Nuevo número de teléfono',
                            ],
                            'address' => [
                                'type' => 'string',
                                'description' => 'Nueva dirección',
                            ],
                            'first_name' => [
                                'type' => 'string',
                                'description' => 'Nuevo nombre (solo para clientes individuales)',
                            ],
                            'last_name' => [
                                'type' => 'string',
                                'description' => 'Nuevo apellido (solo para clientes individuales)',
                            ],
                            'business_name' => [
                                'type' => 'string',
                                'description' => 'Nueva razón social (solo para empresas)',
                            ],
                            'status' => [
                                'type' => 'string',
                                'enum' => ['A', 'I'],
                                'description' => 'Nuevo estado: A (Activo) o I (Inactivo)',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_product',
                    'description' => 'Actualiza datos de un producto existente. Sinónimos: actualizar producto, modificar producto, cambiar datos del producto, editar producto, actualizar stock, cambiar precio del producto, modificar nombre del producto. Ejemplos: "actualiza el stock del producto iPhone 13 a 50", "cambia el precio del producto con SKU ABC123".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => [
                                'type' => 'string',
                                'description' => 'ID del producto a actualizar',
                            ],
                            'sku' => [
                                'type' => 'string',
                                'description' => 'SKU del producto (puede ser para identificar o para actualizar)',
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Nombre del producto (puede ser para identificar o para actualizar)',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Nueva descripción del producto',
                            ],
                            'stock' => [
                                'type' => 'integer',
                                'description' => 'Nuevo stock del producto',
                            ],
                            'status' => [
                                'type' => 'string',
                                'enum' => ['A', 'I'],
                                'description' => 'Nuevo estado: A (Activo) o I (Inactivo)',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_workshop_order',
                    'description' => 'Actualiza datos de una orden de reparación existente. Sinónimos: actualizar orden, modificar orden, cambiar datos de la orden, editar orden, cambiar estado de la orden, actualizar diagnóstico, modificar fecha prometida. Ejemplos: "actualiza el estado de la orden 001-001-0000001 a Completada", "cambia la fecha prometida de la orden 001-001-0000001".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'order_number' => [
                                'type' => 'string',
                                'description' => 'Número de orden a actualizar (ej: 001-001-0000001)',
                            ],
                            'order_id' => [
                                'type' => 'string',
                                'description' => 'ID de la orden a actualizar',
                            ],
                            'note' => [
                                'type' => 'string',
                                'description' => 'Nueva nota para la orden',
                            ],
                            'diagnosis' => [
                                'type' => 'boolean',
                                'description' => 'Si tiene diagnóstico (true/false)',
                            ],
                            'warranty' => [
                                'type' => 'boolean',
                                'description' => 'Si tiene garantía (true/false)',
                            ],
                            'priority' => [
                                'type' => 'string',
                                'description' => 'Nueva prioridad de la orden',
                            ],
                            'promised_at' => [
                                'type' => 'string',
                                'description' => 'Nueva fecha prometida (formato: YYYY-MM-DD)',
                            ],
                            'state_id' => [
                                'type' => 'string',
                                'description' => 'ID del nuevo estado de la orden',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_workshop_order_by_number',
                    'description' => 'HERRAMIENTA PRINCIPAL para obtener TODOS los detalles completos de una orden de reparación/trabajo, O para listar órdenes por fecha. DEBE usarse cuando el usuario pida: "detalles de la orden", "información de la orden", "datos de la orden", "ver orden", "muéstrame la orden", "dame detalles de la orden X", "información completa de la orden X", "muéstrame todos los datos de la orden X", "dame detalles de la orden 001-001-002", "dame las órdenes de taller del día de hoy", "dame las órdenes de taller del mes de enero", "dame las órdenes de taller del 1 al 15 de enero". Si se proporciona order_number, retorna detalles completos. Si se proporciona date_from (y opcionalmente date_to), retorna una lista de órdenes. Retorna información COMPLETA en HTML: estado, cliente completo, equipo, responsable, productos con precios y cantidades, servicios con precios y cantidades, abonos con fechas, totales financieros (costo total, total pagado, balance pendiente), notas, fechas, prioridad, categoría, etc. El parámetro order_number debe ser el número COMPLETO de la orden como string (ej: "001-001-001", "001-001-002"). NO usar get_workshop_order_status para detalles completos.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'order_number' => [
                                'type' => 'string',
                                'description' => 'Número COMPLETO de la orden de trabajo como string. Ejemplos válidos: "001-001-001", "001-001-002", "001-001-010". Debe ser el número completo tal como aparece en el sistema, sin modificaciones ni prefijos.',
                            ],
                            'date_from' => [
                                'type' => 'string',
                                'description' => 'Fecha inicial para búsqueda (formato: YYYY-MM-DD). Si es hoy, busca órdenes del día de hoy. Si se proporciona sin date_to, busca desde esa fecha hasta hoy.',
                            ],
                            'date_to' => [
                                'type' => 'string',
                                'description' => 'Fecha final para búsqueda (formato: YYYY-MM-DD). Si se proporciona con date_from, busca en ese rango.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_products_by_names',
                    'description' => 'Busca productos en el inventario por sus nombres, SKU o código de barras. Si solo hay un producto, retorna TODOS los detalles completos en HTML. Si hay varios, retorna una lista. Sinónimos: buscar producto, detalles del producto, información del producto, datos del producto, ver producto, muéstrame el producto, dame los detalles del producto X, dame los detalles del producto con SKU X. Ejemplos: "dame los detalles del producto 001-001-001", "muéstrame todos los detalles del producto iPhone 13", "dame los detalles del producto con código de barras 123456789".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_names' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Lista de nombres de productos a buscar (ej: ["iPhone 13", "cable", "cargador"]). Si solo hay un resultado, se muestran TODOS los detalles completos.',
                            ],
                            'sku' => [
                                'type' => 'string',
                                'description' => 'SKU del producto para obtener TODOS los detalles completos (ej: "001-001-001")',
                            ],
                            'barcode' => [
                                'type' => 'string',
                                'description' => 'Código de barras del producto para obtener TODOS los detalles completos',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_invoice_details',
                    'description' => 'HERRAMIENTA PRINCIPAL para obtener TODOS los detalles completos de una factura o nota de venta, O para listar facturas/notas de venta por fecha. DEBE usarse cuando el usuario pida: "detalles de la factura", "información de la factura", "datos de la factura", "ver factura", "muéstrame la factura", "dame detalles de la factura X", "información completa de la factura X", "muéstrame todos los datos de la factura X", "dame los detalles de la factura 001-001-000000032", "dame los detalles de la nota de venta X", "dame las facturas del día de hoy", "dame las notas de venta del día de hoy", "dame las facturas del mes de enero", "dame las facturas del 1 al 15 de enero". Si se proporciona invoice_number, retorna detalles completos. Si se proporciona date_from (y opcionalmente date_to y document_type), retorna una lista de facturas/notas de venta. El parámetro document_type puede ser "FACTURA" o "NOTA_DE_VENTA".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'invoice_number' => [
                                'type' => 'string',
                                'description' => 'Número COMPLETO de la factura o nota de venta como string. Ejemplos válidos: "001-001-000000032", "001-001-000000001". Debe ser el número completo tal como aparece en el sistema.',
                            ],
                            'invoice_id' => [
                                'type' => 'string',
                                'description' => 'ID de la factura (alternativa a invoice_number)',
                            ],
                            'date_from' => [
                                'type' => 'string',
                                'description' => 'Fecha inicial para búsqueda (formato: YYYY-MM-DD). Si es hoy, busca facturas/notas de venta del día de hoy. Si se proporciona sin date_to, busca desde esa fecha hasta hoy.',
                            ],
                            'date_to' => [
                                'type' => 'string',
                                'description' => 'Fecha final para búsqueda (formato: YYYY-MM-DD). Si se proporciona con date_from, busca en ese rango.',
                            ],
                            'document_type' => [
                                'type' => 'string',
                                'enum' => ['FACTURA', 'NOTA_DE_VENTA'],
                                'description' => 'Tipo de documento: "FACTURA" para facturas, "NOTA_DE_VENTA" para notas de venta. Si no se proporciona, busca ambos tipos.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_system_statistics',
                    'description' => 'Obtiene estadísticas del sistema. Sinónimos: estadísticas, totales, resumen, cuántos clientes, cuántos productos, cuántas órdenes, cuánto pendiente por cobrar, cuánto pendiente por pagar, ingresos, egresos, balance. Puede filtrar por fecha o mes. Para consultar solo ingresos o egresos: "dime el total de ingresos de hoy", "dime el total de egresos de hoy", "dime el total de ingresos del mes de enero", "dime el total de egresos del 1 al 15 de enero". Usa query_type: "income" para ingresos, "expense" para egresos.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'stat_type' => [
                                'type' => 'string',
                                'enum' => ['all', 'totals', 'financial', 'monthly'],
                                'description' => 'Tipo de estadísticas: all (todas), totals (solo totales generales), financial (solo financieras), monthly (por mes)',
                                'default' => 'all',
                            ],
                            'date_from' => [
                                'type' => 'string',
                                'description' => 'Fecha de inicio para filtrar (formato: YYYY-MM-DD, ej: 2025-01-01)',
                            ],
                            'date_to' => [
                                'type' => 'string',
                                'description' => 'Fecha de fin para filtrar (formato: YYYY-MM-DD, ej: 2025-01-31)',
                            ],
                            'month' => [
                                'type' => 'integer',
                                'description' => 'Mes para estadísticas mensuales (1-12)',
                                'minimum' => 1,
                                'maximum' => 12,
                            ],
                            'year' => [
                                'type' => 'integer',
                                'description' => 'Año para estadísticas mensuales (ej: 2025)',
                                'minimum' => 2000,
                                'maximum' => 2100,
                            ],
                            'query_type' => [
                                'type' => 'string',
                                'enum' => ['income', 'expense', 'both'],
                                'description' => 'Tipo de consulta específica: "income" para solo ingresos, "expense" para solo egresos, "both" para ambos. Útil para consultas como "dime el total de ingresos de hoy" o "dime el total de egresos del mes".',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_customer_debt',
                    'description' => 'Consulta cuánto debe un cliente. Sinónimos: cuánto debe, deuda del cliente, saldo pendiente, debe dinero, cuánto adeuda, pendiente por cobrar del cliente. Incluye facturas pendientes y cuentas por cobrar.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'document_number' => [
                                'type' => 'string',
                                'description' => 'Número de documento del cliente',
                            ],
                            'email' => [
                                'type' => 'string',
                                'description' => 'Email del cliente',
                            ],
                            'customer_id' => [
                                'type' => 'string',
                                'description' => 'ID del cliente',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_customer_invoices',
                    'description' => 'Consulta las facturas/compras de un cliente. Sinónimos: facturas del cliente, compras del cliente, facturas pendientes, qué facturas tiene, facturas sin pagar, cuentas por cobrar facturas, estadísticas de compras, historial de compras. USAR cuando el usuario pida información sobre facturas o compras de un cliente.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'document_number' => [
                                'type' => 'string',
                                'description' => 'Número de documento del cliente',
                            ],
                            'email' => [
                                'type' => 'string',
                                'description' => 'Email del cliente',
                            ],
                            'customer_id' => [
                                'type' => 'string',
                                'description' => 'ID del cliente',
                            ],
                            'only_pending' => [
                                'type' => 'boolean',
                                'description' => 'Si es true, solo muestra facturas pendientes de pago',
                                'default' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_customer_orders',
                    'description' => 'Consulta las órdenes de reparación/trabajo de un cliente. Sinónimos: órdenes del cliente, órdenes de trabajo, reparaciones del cliente, qué órdenes tiene, estado de las órdenes, órdenes en proceso, estadísticas de órdenes, historial de órdenes. USAR cuando el usuario pida información sobre órdenes de reparación de un cliente.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'document_number' => [
                                'type' => 'string',
                                'description' => 'Número de documento del cliente',
                            ],
                            'email' => [
                                'type' => 'string',
                                'description' => 'Email del cliente',
                            ],
                            'customer_id' => [
                                'type' => 'string',
                                'description' => 'ID del cliente',
                            ],
                            'state_name' => [
                                'type' => 'string',
                                'description' => 'Filtrar por nombre del estado (ej: "En proceso", "Completada")',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_database_schema',
                    'description' => 'Consulta el esquema de la base de datos leyendo las migraciones. Sinónimos: esquema de base de datos, estructura de tablas, migraciones, modelo de datos, schema, qué tablas hay, estructura de la BD.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'table_name' => [
                                'type' => 'string',
                                'description' => 'Nombre de la tabla específica a consultar (opcional)',
                            ],
                            'include_relations' => [
                                'type' => 'boolean',
                                'description' => 'Si es true, incluye información sobre relaciones (foreign keys)',
                                'default' => false,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'generate_table',
                    'description' => 'Genera una tabla HTML con datos del sistema. Sinónimos: crear tabla, generar tabla, tabla HTML, mostrar en tabla, listar en tabla, tabla con clientes, tabla con productos. Ejemplos: "crea una tabla con los últimos 10 clientes", "genera una tabla con productos Apple iPhone".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'data_type' => [
                                'type' => 'string',
                                'enum' => ['customers', 'products', 'orders', 'invoices'],
                                'description' => 'Tipo de datos: customers (clientes), products (productos), orders (órdenes), invoices (facturas)',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Número máximo de registros (1-100)',
                                'minimum' => 1,
                                'maximum' => 100,
                                'default' => 10,
                            ],
                            'filters' => [
                                'type' => 'object',
                                'description' => 'Filtros opcionales: para products puede incluir brand (marca) y name (nombre), para orders puede incluir state (estado), para invoices puede incluir only_pending (boolean)',
                            ],
                        ],
                        'required' => ['data_type'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'generate_chart',
                    'description' => 'Genera gráficos HTML usando Chart.js. Sinónimos: crear gráfico, generar gráfico, gráfico de barras, gráfico de líneas, gráfico circular, visualización, chart. Ejemplos: "crea un gráfico del producto más vendido por mes", "genera un gráfico de ventas del año".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'chart_type' => [
                                'type' => 'string',
                                'enum' => ['bar', 'line', 'pie', 'doughnut'],
                                'description' => 'Tipo de gráfico: bar (barras), line (líneas), pie (pastel), doughnut (rosquilla)',
                                'default' => 'bar',
                            ],
                            'data_type' => [
                                'type' => 'string',
                                'enum' => ['sales_by_month', 'top_products', 'revenue_by_month'],
                                'description' => 'Tipo de datos: sales_by_month (ventas por mes), top_products (productos más vendidos), revenue_by_month (ingresos por mes)',
                                'default' => 'sales_by_month',
                            ],
                            'period' => [
                                'type' => 'string',
                                'enum' => ['year', 'quarter', 'month', 'all'],
                                'description' => 'Período: year (año actual), quarter (trimestre), month (mes actual), all (todo)',
                                'default' => 'year',
                            ],
                        ],
                        'required' => ['data_type'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_income',
                    'description' => 'Crea un nuevo ingreso en el sistema. Sinónimos: agregar ingreso, registrar ingreso, nuevo ingreso, crear ingreso, ingresar dinero. Ejemplos: "agrega un ingreso de 500 USD del 15 de enero por venta de productos", "registra un ingreso de 1000 por servicios".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'income_type_id' => [
                                'type' => 'string',
                                'description' => 'ID del tipo de ingreso',
                            ],
                            'income_type_name' => [
                                'type' => 'string',
                                'description' => 'Nombre del tipo de ingreso (si no se proporciona ID, busca por nombre)',
                            ],
                            'movement_date' => [
                                'type' => 'string',
                                'description' => 'Fecha del ingreso (formato: YYYY-MM-DD)',
                            ],
                            'concept' => [
                                'type' => 'string',
                                'description' => 'Concepto del ingreso',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Descripción detallada',
                            ],
                            'amount' => [
                                'type' => 'number',
                                'description' => 'Monto del ingreso (ej: 500.50)',
                            ],
                            'currency_code' => [
                                'type' => 'string',
                                'default' => 'USD',
                                'description' => 'Código de moneda',
                            ],
                            'reference' => [
                                'type' => 'string',
                                'description' => 'Referencia o número de documento',
                            ],
                        ],
                        'required' => ['movement_date', 'concept', 'amount'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_expense',
                    'description' => 'Crea un nuevo egreso en el sistema. Sinónimos: agregar egreso, registrar egreso, nuevo egreso, crear egreso, gasto, registrar gasto. Ejemplos: "agrega un egreso de 200 USD del 15 de enero por compra de materiales", "registra un gasto de 150 por servicios públicos".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'expense_type_id' => [
                                'type' => 'string',
                                'description' => 'ID del tipo de egreso',
                            ],
                            'expense_type_name' => [
                                'type' => 'string',
                                'description' => 'Nombre del tipo de egreso (si no se proporciona ID, busca por nombre)',
                            ],
                            'movement_date' => [
                                'type' => 'string',
                                'description' => 'Fecha del egreso (formato: YYYY-MM-DD)',
                            ],
                            'concept' => [
                                'type' => 'string',
                                'description' => 'Concepto del egreso',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Descripción detallada',
                            ],
                            'amount' => [
                                'type' => 'number',
                                'description' => 'Monto del egreso (ej: 200.50)',
                            ],
                            'currency_code' => [
                                'type' => 'string',
                                'default' => 'USD',
                                'description' => 'Código de moneda',
                            ],
                            'reference' => [
                                'type' => 'string',
                                'description' => 'Referencia o número de documento',
                            ],
                        ],
                        'required' => ['movement_date', 'concept', 'amount'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_receivable_entry',
                    'description' => 'Crea una nueva cuenta por cobrar en el sistema. Sinónimos: agregar cuenta por cobrar, registrar cuenta por cobrar, nueva cuenta por cobrar, crear cuenta por cobrar, agregar por cobrar. Ejemplos: "agrega una cuenta por cobrar de 300 USD del 20 de enero por servicios pendientes", "registra una cuenta por cobrar de 500".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'receivable_category_id' => [
                                'type' => 'string',
                                'description' => 'ID de la categoría',
                            ],
                            'category_name' => [
                                'type' => 'string',
                                'description' => 'Nombre de la categoría (si no se proporciona ID, busca por nombre)',
                            ],
                            'movement_date' => [
                                'type' => 'string',
                                'description' => 'Fecha (formato: YYYY-MM-DD)',
                            ],
                            'concept' => [
                                'type' => 'string',
                                'description' => 'Concepto de la cuenta por cobrar',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Descripción detallada',
                            ],
                            'amount' => [
                                'type' => 'number',
                                'description' => 'Monto a cobrar (ej: 300.50)',
                            ],
                            'currency_code' => [
                                'type' => 'string',
                                'default' => 'USD',
                                'description' => 'Código de moneda',
                            ],
                            'reference' => [
                                'type' => 'string',
                                'description' => 'Referencia o número de documento',
                            ],
                            'is_collected' => [
                                'type' => 'boolean',
                                'default' => false,
                                'description' => 'Si ya fue cobrada',
                            ],
                        ],
                        'required' => ['movement_date', 'concept', 'amount'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_payable_entry',
                    'description' => 'Crea una nueva cuenta por pagar en el sistema. Sinónimos: agregar cuenta por pagar, registrar cuenta por pagar, nueva cuenta por pagar, crear cuenta por pagar, agregar por pagar. Ejemplos: "agrega una cuenta por pagar de 400 USD del 25 de enero por factura de proveedor", "registra una cuenta por pagar de 600".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'payable_category_id' => [
                                'type' => 'string',
                                'description' => 'ID de la categoría',
                            ],
                            'category_name' => [
                                'type' => 'string',
                                'description' => 'Nombre de la categoría (si no se proporciona ID, busca por nombre)',
                            ],
                            'movement_date' => [
                                'type' => 'string',
                                'description' => 'Fecha (formato: YYYY-MM-DD)',
                            ],
                            'concept' => [
                                'type' => 'string',
                                'description' => 'Concepto de la cuenta por pagar',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Descripción detallada',
                            ],
                            'amount' => [
                                'type' => 'number',
                                'description' => 'Monto a pagar (ej: 400.50)',
                            ],
                            'currency_code' => [
                                'type' => 'string',
                                'default' => 'USD',
                                'description' => 'Código de moneda',
                            ],
                            'reference' => [
                                'type' => 'string',
                                'description' => 'Referencia o número de documento',
                            ],
                            'is_paid' => [
                                'type' => 'boolean',
                                'default' => false,
                                'description' => 'Si ya fue pagada',
                            ],
                        ],
                        'required' => ['movement_date', 'concept', 'amount'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'query_incomes',
                    'description' => 'Consulta ingresos del sistema. Puede filtrar por fecha o rango de fechas. Sinónimos: consultar ingresos, ver ingresos, listar ingresos, ingresos del mes, ingresos por fecha, ingresos entre fechas. Ejemplos: "muéstrame los ingresos de enero", "ingresos del 1 al 31 de enero".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'date_from' => [
                                'type' => 'string',
                                'description' => 'Fecha de inicio (formato: YYYY-MM-DD)',
                            ],
                            'date_to' => [
                                'type' => 'string',
                                'description' => 'Fecha de fin (formato: YYYY-MM-DD)',
                            ],
                            'month' => [
                                'type' => 'integer',
                                'minimum' => 1,
                                'maximum' => 12,
                                'description' => 'Mes (1-12)',
                            ],
                            'year' => [
                                'type' => 'integer',
                                'minimum' => 2000,
                                'maximum' => 2100,
                                'description' => 'Año (ej: 2025)',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'default' => 50,
                                'minimum' => 1,
                                'maximum' => 200,
                                'description' => 'Número máximo de registros',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'query_expenses',
                    'description' => 'Consulta egresos del sistema. Puede filtrar por fecha o rango de fechas. Sinónimos: consultar egresos, ver egresos, listar egresos, egresos del mes, egresos por fecha, egresos entre fechas, gastos, consultar gastos. Ejemplos: "muéstrame los egresos de enero", "egresos del 1 al 31 de enero".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'date_from' => [
                                'type' => 'string',
                                'description' => 'Fecha de inicio (formato: YYYY-MM-DD)',
                            ],
                            'date_to' => [
                                'type' => 'string',
                                'description' => 'Fecha de fin (formato: YYYY-MM-DD)',
                            ],
                            'month' => [
                                'type' => 'integer',
                                'minimum' => 1,
                                'maximum' => 12,
                                'description' => 'Mes (1-12)',
                            ],
                            'year' => [
                                'type' => 'integer',
                                'minimum' => 2000,
                                'maximum' => 2100,
                                'description' => 'Año (ej: 2025)',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'default' => 50,
                                'minimum' => 1,
                                'maximum' => 200,
                                'description' => 'Número máximo de registros',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'query_receivable_entries',
                    'description' => 'Consulta cuentas por cobrar del sistema. Puede filtrar por fecha, rango de fechas, o solo pendientes. Sinónimos: consultar cuentas por cobrar, ver cuentas por cobrar, listar cuentas por cobrar, cuentas por cobrar del mes, cuentas por cobrar pendientes. Ejemplos: "muéstrame las cuentas por cobrar de enero", "cuentas por cobrar pendientes".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'date_from' => [
                                'type' => 'string',
                                'description' => 'Fecha de inicio (formato: YYYY-MM-DD)',
                            ],
                            'date_to' => [
                                'type' => 'string',
                                'description' => 'Fecha de fin (formato: YYYY-MM-DD)',
                            ],
                            'month' => [
                                'type' => 'integer',
                                'minimum' => 1,
                                'maximum' => 12,
                                'description' => 'Mes (1-12)',
                            ],
                            'year' => [
                                'type' => 'integer',
                                'minimum' => 2000,
                                'maximum' => 2100,
                                'description' => 'Año (ej: 2025)',
                            ],
                            'only_pending' => [
                                'type' => 'boolean',
                                'default' => false,
                                'description' => 'Si es true, solo muestra cuentas pendientes de cobro',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'default' => 50,
                                'minimum' => 1,
                                'maximum' => 200,
                                'description' => 'Número máximo de registros',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'query_payable_entries',
                    'description' => 'Consulta cuentas por pagar del sistema. Puede filtrar por fecha, rango de fechas, o solo pendientes. Sinónimos: consultar cuentas por pagar, ver cuentas por pagar, listar cuentas por pagar, cuentas por pagar del mes, cuentas por pagar pendientes. Ejemplos: "muéstrame las cuentas por pagar de enero", "cuentas por pagar pendientes".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'date_from' => [
                                'type' => 'string',
                                'description' => 'Fecha de inicio (formato: YYYY-MM-DD)',
                            ],
                            'date_to' => [
                                'type' => 'string',
                                'description' => 'Fecha de fin (formato: YYYY-MM-DD)',
                            ],
                            'month' => [
                                'type' => 'integer',
                                'minimum' => 1,
                                'maximum' => 12,
                                'description' => 'Mes (1-12)',
                            ],
                            'year' => [
                                'type' => 'integer',
                                'minimum' => 2000,
                                'maximum' => 2100,
                                'description' => 'Año (ej: 2025)',
                            ],
                            'only_pending' => [
                                'type' => 'boolean',
                                'default' => false,
                                'description' => 'Si es true, solo muestra cuentas pendientes de pago',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'default' => 50,
                                'minimum' => 1,
                                'maximum' => 200,
                                'description' => 'Número máximo de registros',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_product_label',
                    'description' => 'Obtiene el enlace PDF de la etiqueta de código de barras de un producto. Sinónimos: etiqueta del producto, etiqueta PDF producto, código de barras producto, label producto, imprimir etiqueta producto. Ejemplos: "dame la etiqueta del producto 001-001-001", "muéstrame la etiqueta PDF del producto con SKU ABC123".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'sku' => [
                                'type' => 'string',
                                'description' => 'SKU del producto (ej: 001-001-001)',
                            ],
                        ],
                        'required' => ['sku'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_equipment_label',
                    'description' => 'Obtiene el enlace PDF de la etiqueta de código de barras de un equipo. Sinónimos: etiqueta del equipo, etiqueta PDF equipo, código de barras equipo, label equipo, imprimir etiqueta equipo. Ejemplos: "dame la etiqueta del equipo ABC123", "muéstrame la etiqueta PDF del equipo con identificador XYZ789".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'identifier' => [
                                'type' => 'string',
                                'description' => 'Identificador del equipo',
                            ],
                        ],
                        'required' => ['identifier'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_workshop_order_label',
                    'description' => 'Obtiene el enlace PDF de la etiqueta de una orden de trabajo. Sinónimos: etiqueta de la orden, etiqueta PDF orden, código de barras orden, label orden, imprimir etiqueta orden. Ejemplos: "dame la etiqueta de la orden 001-001-0000001", "muéstrame la etiqueta PDF de la orden 001-001-0000001".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'order_number' => [
                                'type' => 'string',
                                'description' => 'Número de orden (ej: 001-001-0000001)',
                            ],
                        ],
                        'required' => ['order_number'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_workshop_order_status',
                    'description' => 'SOLO para consultar el ESTADO ACTUAL de una orden. Retorna información MÍNIMA: estado, cliente, equipo. NO retorna productos, servicios, abonos, totales, notas, etc. USAR SOLO cuando el usuario pregunte específicamente "¿qué estado tiene la orden X?" o "¿en qué estado está la orden X?". Si el usuario pide "detalles", "información", "datos", "muéstrame" o "dame" sobre una orden, DEBE usar get_workshop_order_by_number en su lugar.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'order_number' => [
                                'type' => 'string',
                                'description' => 'Número de orden (ej: 001-001-001, 001-001-002)',
                            ],
                        ],
                        'required' => ['order_number'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Maneja las tool calls del LLM ejecutando las tools MCP correspondientes.
     */
    private function handleToolCalls(array $toolCalls, array $messages): string
    {
        $toolResults = [];
        
        foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'] ?? null;
            $arguments = json_decode($toolCall['function']['arguments'] ?? '{}', true);
            $toolCallId = $toolCall['id'] ?? null;

            if (!$functionName || !$toolCallId) {
                continue;
            }

            try {
                $result = match($functionName) {
                    'search_customer_by_document' => $this->executeCustomerQuery($arguments),
                    'create_customer' => $this->executeCustomerCreate($arguments),
                    'update_customer' => $this->executeCustomerUpdate($arguments),
                    'get_customer_debt' => $this->executeCustomerDebtQuery($arguments),
                    'get_customer_invoices' => $this->executeCustomerInvoicesQuery($arguments),
                    'get_customer_orders' => $this->executeCustomerOrdersQuery($arguments),
                    'get_workshop_order_by_number' => $this->executeOrderQuery($arguments),
                    'update_workshop_order' => $this->executeOrderUpdate($arguments),
                    'search_products_by_names' => $this->executeProductQuery($arguments),
                    'update_product' => $this->executeProductUpdate($arguments),
                    'get_system_statistics' => $this->executeStatisticsQuery($arguments),
                    'create_income' => $this->executeIncomeCreate($arguments),
                    'query_incomes' => $this->executeIncomeQuery($arguments),
                    'create_expense' => $this->executeExpenseCreate($arguments),
                    'query_expenses' => $this->executeExpenseQuery($arguments),
                    'create_receivable_entry' => $this->executeReceivableEntryCreate($arguments),
                    'query_receivable_entries' => $this->executeReceivableEntryQuery($arguments),
                    'create_payable_entry' => $this->executePayableEntryCreate($arguments),
                    'query_payable_entries' => $this->executePayableEntryQuery($arguments),
                    'get_product_label' => $this->executeProductLabel($arguments),
                    'get_equipment_label' => $this->executeEquipmentLabel($arguments),
                    'get_workshop_order_label' => $this->executeWorkshopOrderLabel($arguments),
                    'get_workshop_order_status' => $this->executeWorkshopOrderStatus($arguments),
                    'get_invoice_details' => $this->executeInvoiceDetails($arguments),
                    'get_database_schema' => $this->executeDatabaseSchemaQuery($arguments),
                    'generate_table' => $this->executeTableGenerator($arguments),
                    'generate_chart' => $this->executeChartGenerator($arguments),
                    default => $this->handleUnknownTool($functionName),
                };

                // Si hay un error con sugerencia, incluirla en el resultado
                if (isset($result['error']) && isset($result['suggestion'])) {
                    $result['message'] = $result['error'] . ' Por favor, intenta de nuevo usando la herramienta sugerida: ' . $result['suggestion'];
                }
                
                $toolResults[] = [
                    'tool_call_id' => $toolCallId,
                    'role' => 'tool',
                    'name' => $functionName,
                    'content' => json_encode($result),
                ];
            } catch (\Exception $e) {
                $toolResults[] = [
                    'tool_call_id' => $toolCallId,
                    'role' => 'tool',
                    'name' => $functionName,
                    'content' => json_encode(['error' => $e->getMessage()]),
                ];
            }
        }

        // Optimización: Si solo hay una tool call y el resultado ya está en HTML formateado,
        // devolver directamente sin hacer segunda llamada al LLM
        if (count($toolCalls) === 1 && count($toolResults) === 1) {
            $result = $toolResults[0];
            $resultData = json_decode($result['content'], true);
            
            // Si el resultado tiene contenido HTML formateado, devolverlo directamente
            if (isset($resultData['success']) && $resultData['success'] && isset($resultData['content'])) {
                $content = $resultData['content'];
                // Si el contenido ya está en HTML bien formateado, devolverlo directamente
                if (is_string($content) && (
                    strpos($content, '<div') !== false || 
                    strpos($content, '<table') !== false || 
                    strpos($content, '<h') !== false ||
                    strpos($content, '<p') !== false
                )) {
                    return $content;
                }
            }
        }
        
        // Verificar si hay errores de herramientas desconocidas y sugerir corrección
        $hasUnknownTool = false;
        $suggestedTool = null;
        foreach ($toolResults as $result) {
            $resultData = json_decode($result['content'], true);
            if (isset($resultData['error']) && isset($resultData['suggestion'])) {
                $hasUnknownTool = true;
                $suggestedTool = $resultData['suggestion'];
                // Reemplazar el error con un mensaje más claro
                $result['content'] = json_encode([
                    'error' => $resultData['error'],
                    'suggestion' => $resultData['suggestion'],
                    'message' => $resultData['error'] . ' Por favor, usa la herramienta correcta: ' . $resultData['suggestion'] . ' con los mismos parámetros pero usando el nombre de herramienta correcto.',
                ]);
            }
        }

        // Agregar resultados de tools al contexto y obtener respuesta final
        $messages[] = [
            'role' => 'assistant',
            'content' => null,
            'tool_calls' => $toolCalls,
        ];

        foreach ($toolResults as $result) {
            $messages[] = [
                'role' => $result['role'],
                'tool_call_id' => $result['tool_call_id'],
                'name' => $result['name'],
                'content' => $result['content'],
            ];
        }

        // Si hay una herramienta desconocida con sugerencia, agregar instrucción adicional
        if ($hasUnknownTool && $suggestedTool) {
            $messages[] = [
                'role' => 'system',
                'content' => "IMPORTANTE: Se intentó usar una herramienta que no existe. Debes usar la herramienta '{$suggestedTool}' con los mismos parámetros. NO inventes nombres de herramientas. Solo usa las herramientas disponibles en la lista.",
            ];
        }

        // Llamar al LLM nuevamente con el contexto de los tools (timeout más corto para formateo)
        $apiKey = env('DEEPSEEK_API_KEY');
        $response = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.deepseek.com/v1/chat/completions', [
            'model' => 'deepseek-chat',
            'messages' => $messages,
            'temperature' => 0.7,
            'stream' => false,
            'tools' => $this->getMCPTools(), // Asegurar que las herramientas estén disponibles en la segunda llamada
            'tool_choice' => 'auto',
        ]);

        if (!$response->successful()) {
            $errorBody = $response->body();
            $errorMessage = 'Error al llamar a DeepSeek con tool results';
            
            try {
                $errorData = $response->json();
                if (isset($errorData['error']['message'])) {
                    $errorMessage .= ': ' . $errorData['error']['message'];
                } else {
                    $errorMessage .= ': ' . $errorBody;
                }
            } catch (\Exception $e) {
                $errorMessage .= ': ' . $errorBody;
            }
            
            throw new \Exception($errorMessage);
        }

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Ejecuta la query de cliente usando las tools MCP.
     */
    private function executeCustomerQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\CustomerQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la creación de cliente usando las tools MCP.
     */
    private function executeCustomerCreate(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\CustomerCreateTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la query de orden usando las tools MCP.
     */
    private function executeOrderQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\WorkshopOrderQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la query de productos usando las tools MCP.
     */
    private function executeProductQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\ProductQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la query de detalles de factura usando las tools MCP.
     */
    private function executeInvoiceDetails(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\InvoiceQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la query de estadísticas usando las tools MCP.
     */
    private function executeStatisticsQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\StatisticsQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la query de deuda del cliente usando las tools MCP.
     */
    private function executeCustomerDebtQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\CustomerDebtQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la query de facturas del cliente usando las tools MCP.
     */
    private function executeCustomerInvoicesQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\CustomerInvoicesQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la query de órdenes del cliente usando las tools MCP.
     */
    private function executeCustomerOrdersQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\CustomerOrdersQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la query del esquema de base de datos usando las tools MCP.
     */
    private function executeDatabaseSchemaQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\DatabaseSchemaQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la generación de tabla usando las tools MCP.
     */
    private function executeTableGenerator(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\TableGeneratorTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la generación de gráfico usando las tools MCP.
     */
    private function executeChartGenerator(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\ChartGeneratorTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la actualización de cliente usando las tools MCP.
     */
    private function executeCustomerUpdate(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\CustomerUpdateTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la actualización de producto usando las tools MCP.
     */
    private function executeProductUpdate(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\ProductUpdateTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la actualización de orden usando las tools MCP.
     */
    private function executeOrderUpdate(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\WorkshopOrderUpdateTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la creación de ingreso usando las tools MCP.
     */
    private function executeIncomeCreate(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\IncomeCreateTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la consulta de ingresos usando las tools MCP.
     */
    private function executeIncomeQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\IncomeQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la creación de egreso usando las tools MCP.
     */
    private function executeExpenseCreate(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\ExpenseCreateTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la consulta de egresos usando las tools MCP.
     */
    private function executeExpenseQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\ExpenseQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la creación de cuenta por cobrar usando las tools MCP.
     */
    private function executeReceivableEntryCreate(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\ReceivableEntryCreateTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la consulta de cuentas por cobrar usando las tools MCP.
     */
    private function executeReceivableEntryQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\ReceivableEntryQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la creación de cuenta por pagar usando las tools MCP.
     */
    private function executePayableEntryCreate(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\PayableEntryCreateTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la consulta de cuentas por pagar usando las tools MCP.
     */
    private function executePayableEntryQuery(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\PayableEntryQueryTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la obtención de etiqueta de producto usando las tools MCP.
     */
    private function executeProductLabel(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\ProductLabelTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la obtención de etiqueta de equipo usando las tools MCP.
     */
    private function executeEquipmentLabel(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\EquipmentLabelTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la obtención de etiqueta de orden usando las tools MCP.
     */
    private function executeWorkshopOrderLabel(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\WorkshopOrderLabelTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Ejecuta la consulta de estado de orden usando las tools MCP.
     */
    private function executeWorkshopOrderStatus(array $arguments): array
    {
        $tool = new \App\Mcp\Tools\Chatbot\WorkshopOrderStatusTool();
        $mcpRequest = new \Laravel\Mcp\Request($arguments, 'chat-session');
        $response = $tool->handle($mcpRequest);
        
        $content = $response->content();
        $text = method_exists($content, 'toArray') ? ($content->toArray()['text'] ?? '') : (string) $content;
        
        return [
            'success' => true,
            'content' => $text,
        ];
    }

    /**
     * Maneja herramientas desconocidas y sugiere la herramienta correcta.
     */
    private function handleUnknownTool(string $functionName): array
    {
        // Detectar si es una herramienta relacionada con órdenes de taller
        if (stripos($functionName, 'workshop') !== false || stripos($functionName, 'order') !== false) {
            if (stripos($functionName, 'detail') !== false || stripos($functionName, 'info') !== false || stripos($functionName, 'data') !== false) {
                return [
                    'error' => "La herramienta '{$functionName}' no existe. Para obtener detalles completos de una orden, usa la herramienta 'get_workshop_order_by_number' con el parámetro 'order_number' que debe ser el número completo de la orden (ej: '001-001-001', '001-001-002').",
                    'suggestion' => 'get_workshop_order_by_number',
                ];
            }
            if (stripos($functionName, 'status') !== false || stripos($functionName, 'estado') !== false) {
                return [
                    'error' => "La herramienta '{$functionName}' no existe. Para consultar el estado de una orden, usa la herramienta 'get_workshop_order_status' con el parámetro 'order_number'.",
                    'suggestion' => 'get_workshop_order_status',
                ];
            }
            return [
                'error' => "La herramienta '{$functionName}' no existe. Herramientas disponibles para órdenes: 'get_workshop_order_by_number' (detalles completos), 'get_workshop_order_status' (solo estado), 'get_workshop_order_label' (etiqueta PDF), 'update_workshop_order' (actualizar orden).",
            ];
        }

        return [
            'error' => "La herramienta '{$functionName}' no existe. Por favor, usa solo las herramientas disponibles en la lista.",
        ];
    }
}
