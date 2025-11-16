<x-layouts.dashboard-layout :meta="$meta ?? []">
    <div class="flex h-screen flex-col bg-gray-50 dark:bg-slate-900 -mx-6 -my-8">
        {{-- Header --}}
        <header class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 py-4 shadow-sm dark:border-gray-800 dark:bg-slate-900">
            <div class="flex items-center gap-4">
                <button
                    onclick="window.location.href='{{ route('dashboard') }}'"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    {{ gettext('Volver') }}
                </button>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ gettext('AI Chat') }}</h1>
            </div>
            <div class="flex items-center gap-4">
                <select id="model-selector" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm dark:border-gray-700 dark:bg-slate-800 dark:text-white">
                    <option value="deepseek-chat">DeepSeek Chat</option>
                    <option value="gemini-1.5-pro">Google Gemini 1.5 Pro</option>
                </select>
                <button
                    id="new-conversation-btn"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-strong"
                >
                    <i class="fa-solid fa-plus"></i>
                    {{ gettext('Nueva Conversación') }}
                </button>
            </div>
        </header>

        <div class="flex flex-1 min-h-0 overflow-hidden">
            {{-- Sidebar con conversaciones --}}
            <aside class="w-80 shrink-0 border-r border-gray-200 bg-white dark:border-gray-800 dark:bg-slate-900 overflow-y-auto">
                <div class="p-4">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ gettext('Conversaciones') }}
                    </h2>
                    <div id="conversations-list" class="space-y-2">
                        @forelse($conversations as $conv)
                            <div class="group relative rounded-lg border border-gray-200 p-3 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-slate-800 {{ isset($conversation) && $conversation->id === $conv->id ? 'bg-primary/10 border-primary' : '' }}">
                                <a
                                    href="{{ route('chat.show', $conv->id) }}"
                                    class="block"
                                >
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $conv->title ?? gettext('Nueva conversación') }}
                                    </p>
                                    @if($conv->latestMessage)
                                        <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ Str::limit($conv->latestMessage->content, 50) }}
                                        </p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                        {{ $conv->updated_at->diffForHumans() }}
                                    </p>
                                </a>
                                <button
                                    onclick="deleteConversation('{{ $conv->id }}', event)"
                                    class="absolute right-2 top-2 hidden rounded-lg p-1.5 text-gray-400 transition hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 group-hover:block"
                                    title="{{ gettext('Eliminar conversación') }}"
                                >
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ gettext('No hay conversaciones') }}</p>
                        @endforelse
                    </div>
                </div>
            </aside>

            {{-- Área principal de chat --}}
            <main class="flex flex-1 min-h-0 flex-col">
                <div id="chat-container" class="flex-1 min-h-0 overflow-y-auto p-6">
                    <div id="messages-container" class="mx-auto max-w-4xl space-y-4">
                        @if(isset($conversation) && isset($messages))
                            @foreach($messages as $msg)
                                <div class="flex gap-4 {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-[80%] rounded-2xl px-4 py-3 {{ $msg->role === 'user' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white' }}">
                                        <div class="whitespace-pre-wrap prose prose-sm dark:prose-invert max-w-none">
                                            {!! $msg->content !!}
                                        </div>
                                        <p class="mt-2 text-xs opacity-70">{{ $msg->created_at->format('H:i') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-gray-500 dark:text-gray-400">
                                {{ gettext('Inicia una nueva conversación o selecciona una existente') }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Input de mensaje --}}
                <div class="shrink-0 border-t border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-slate-900">
                    <form id="chat-form" class="mx-auto max-w-4xl">
                        <div class="flex gap-4">
                            <input
                                type="text"
                                id="message-input"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-800 dark:text-white"
                                placeholder="{{ gettext('Escribe tu mensaje...') }}"
                                autocomplete="off"
                                @if(!isset($conversation)) disabled @endif
                            >
                            <button
                                type="submit"
                                id="send-btn"
                                class="rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-primary-strong disabled:opacity-50 disabled:cursor-not-allowed"
                                @if(!isset($conversation)) disabled @endif
                            >
                                <i class="fa-solid fa-paper-plane mr-2"></i>
                                {{ gettext('Enviar') }}
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .typing-indicator {
            display: inline-flex;
            gap: 4px;
            align-items: center;
        }
        
        .typing-indicator span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            opacity: 0.4;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-indicator span:nth-child(1) {
            animation-delay: -0.32s;
        }
        
        .typing-indicator span:nth-child(2) {
            animation-delay: -0.16s;
        }
        
        @keyframes typing {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.4;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
    <script>
        // Función global para eliminar conversación
        async function deleteConversation(conversationId, event) {
            event.preventDefault();
            event.stopPropagation();
            
            // Usar SweetAlert2 para confirmación
            const result = await Swal.fire({
                title: '{{ gettext("¿Eliminar conversación?") }}',
                text: '{{ gettext("Esta acción no se puede deshacer") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '{{ gettext("Sí, eliminar") }}',
                cancelButtonText: '{{ gettext("Cancelar") }}',
                reverseButtons: true,
                customClass: {
                    popup: 'dark:bg-slate-800 dark:text-white',
                    title: 'dark:text-white',
                    htmlContainer: 'dark:text-gray-300',
                    confirmButton: 'dark:bg-red-600 dark:hover:bg-red-700',
                    cancelButton: 'dark:bg-gray-600 dark:hover:bg-gray-700',
                },
            });

            if (!result.isConfirmed) {
                return;
            }

            try {
                const response = await fetch(`/chat/conversations/${conversationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                
                if (data.success) {
                    // Mostrar mensaje de éxito
                    await Swal.fire({
                        title: '{{ gettext("¡Eliminado!") }}',
                        text: '{{ gettext("La conversación ha sido eliminada correctamente") }}',
                        icon: 'success',
                        confirmButtonColor: '#10b981',
                        timer: 1500,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'dark:bg-slate-800 dark:text-white',
                            title: 'dark:text-white',
                            htmlContainer: 'dark:text-gray-300',
                        },
                    });

                    // Si estamos viendo esta conversación, redirigir al índice
                    const currentConversationId = @json(isset($conversation) ? $conversation->id : null);
                    if (currentConversationId === conversationId) {
                        window.location.href = '{{ route("chat.index") }}';
                    } else {
                        // Recargar la página para actualizar la lista
                        window.location.reload();
                    }
                } else {
                    await Swal.fire({
                        title: '{{ gettext("Error") }}',
                        text: data.message || '{{ gettext("Error al eliminar la conversación") }}',
                        icon: 'error',
                        confirmButtonColor: '#dc2626',
                        customClass: {
                            popup: 'dark:bg-slate-800 dark:text-white',
                            title: 'dark:text-white',
                            htmlContainer: 'dark:text-gray-300',
                        },
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    title: '{{ gettext("Error") }}',
                    text: '{{ gettext("Error al eliminar la conversación") }}',
                    icon: 'error',
                    confirmButtonColor: '#dc2626',
                    customClass: {
                        popup: 'dark:bg-slate-800 dark:text-white',
                        title: 'dark:text-white',
                        htmlContainer: 'dark:text-gray-300',
                    },
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const messagesContainer = document.getElementById('messages-container');
            const sendBtn = document.getElementById('send-btn');
            const modelSelector = document.getElementById('model-selector');
            
            let currentConversationId = @json(isset($conversation) ? $conversation->id : null);

            // Crear nueva conversación
            document.getElementById('new-conversation-btn')?.addEventListener('click', async () => {
                const model = modelSelector.value;
                const response = await fetch('{{ route('chat.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ model }),
                });

                const data = await response.json();
                if (data.success) {
                    window.location.href = `/chat/conversations/${data.conversation.id}`;
                }
            });

            // Enviar mensaje
            chatForm?.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const message = messageInput.value.trim();
                if (!message || !currentConversationId) return;

                // Agregar mensaje del usuario
                addMessage({ role: 'user', content: message, created_at: new Date() });
                messageInput.value = '';
                sendBtn.disabled = true;

                // Crear elemento para respuesta del asistente con animación de escritura
                const assistantMessageElement = addMessage({ 
                    role: 'assistant', 
                    content: '', 
                    created_at: new Date(),
                    streaming: true,
                });
                
                // Mostrar animación de escritura
                showTypingAnimation(assistantMessageElement);

                try {
                    // Enviar mensaje y recibir respuesta
                    const response = await fetch(`/chat/conversations/${currentConversationId}/messages`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ message }),
                    });

                    if (!response.ok) {
                        throw new Error('Error en la respuesta');
                    }

                    const data = await response.json();
                    
                    // Ocultar animación de escritura
                    hideTypingAnimation(assistantMessageElement);
                    
                    // Actualizar mensaje con contenido completo
                    if (data.message) {
                        assistantMessageElement.dataset.messageId = data.message.id;
                        updateMessageContent(assistantMessageElement, data.message.content);
                        assistantMessageElement.dataset.streaming = 'false';
                    }

                } catch (error) {
                    console.error('Error:', error);
                    hideTypingAnimation(assistantMessageElement);
                    updateMessageContent(assistantMessageElement, 'Error al obtener respuesta: ' + error.message);
                } finally {
                    sendBtn.disabled = false;
                }
            });

            // Función para mostrar animación de escritura
            function showTypingAnimation(element) {
                const textP = element.querySelector('p');
                if (textP) {
                    textP.innerHTML = '<span class="typing-indicator"><span></span><span></span><span></span></span>';
                    textP.classList.add('typing-animation');
                }
            }

            // Función para ocultar animación de escritura
            function hideTypingAnimation(element) {
                const textP = element.querySelector('p');
                if (textP) {
                    textP.classList.remove('typing-animation');
                }
            }

            function addMessage(message) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `flex gap-4 ${message.role === 'user' ? 'justify-end' : 'justify-start'}`;
                messageDiv.dataset.streaming = message.streaming ? 'true' : 'false';
                
                const contentDiv = document.createElement('div');
                contentDiv.className = `max-w-[80%] rounded-2xl px-4 py-3 ${
                    message.role === 'user' 
                        ? 'bg-primary text-white' 
                        : 'bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white'
                }`;
                
                const textDiv = document.createElement('div');
                textDiv.className = 'whitespace-pre-wrap prose prose-sm dark:prose-invert max-w-none';
                
                // Si el contenido contiene HTML, renderizarlo
                if (message.content && /<[a-z][\s\S]*>/i.test(message.content)) {
                    textDiv.innerHTML = message.content || '';
                    // Ejecutar scripts después de un breve delay
                    setTimeout(() => {
                        const scripts = textDiv.querySelectorAll('script');
                        scripts.forEach(oldScript => {
                            const newScript = document.createElement('script');
                            Array.from(oldScript.attributes).forEach(attr => {
                                newScript.setAttribute(attr.name, attr.value);
                            });
                            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                            oldScript.parentNode.replaceChild(newScript, oldScript);
                        });
                    }, 100);
                } else {
                    textDiv.textContent = message.content || '';
                }
                
                contentDiv.appendChild(textDiv);

                const timeP = document.createElement('p');
                timeP.className = 'mt-2 text-xs opacity-70';
                timeP.textContent = new Date(message.created_at).toLocaleTimeString();
                contentDiv.appendChild(timeP);

                messageDiv.appendChild(contentDiv);
                messagesContainer.appendChild(messageDiv);
                
                // Scroll al final
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                
                return messageDiv;
            }

            function updateMessageContent(element, content) {
                const contentDiv = element.querySelector('.prose, div, p');
                if (contentDiv) {
                    // Si el contenido contiene HTML, renderizarlo
                    if (content && /<[a-z][\s\S]*>/i.test(content)) {
                        contentDiv.innerHTML = content;
                        // Ejecutar scripts si hay
                        setTimeout(() => {
                            const scripts = contentDiv.querySelectorAll('script');
                            scripts.forEach(oldScript => {
                                const newScript = document.createElement('script');
                                Array.from(oldScript.attributes).forEach(attr => {
                                    newScript.setAttribute(attr.name, attr.value);
                                });
                                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                                oldScript.parentNode.replaceChild(newScript, oldScript);
                            });
                        }, 100);
                    } else {
                        contentDiv.textContent = content;
                    }
                }
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            // Si hay una conversación cargada, establecer su ID y modelo
            @if(isset($conversation))
                currentConversationId = '{{ $conversation->id }}';
                if (modelSelector) {
                    modelSelector.value = '{{ $conversation->model }}';
                }
                if (messageInput) messageInput.disabled = false;
                if (sendBtn) sendBtn.disabled = false;
            @endif
        });
    </script>
    @endpush
</x-layouts.dashboard-layout>

