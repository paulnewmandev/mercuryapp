<?php

namespace App\Helpers;

use App\Models\Notification;
use Illuminate\Support\Str;

/**
 * Helper para registrar notificaciones en la base de datos.
 */
class NotificationHelper
{
    /**
     * Registra una nueva notificación.
     *
     * @param string|null $companyId Identificador de la compañía (puede ser null para usuarios super admin).
     * @param string $description Descripción de la notificación.
     * @param string|null $userId Usuario asociado, si aplica.
     * @param array<string, mixed> $context Atributos adicionales como título, categoría o metadatos.
     *
     * @return Notification|null
     */
    public static function record(?string $companyId, string $description, ?string $userId = null, array $context = []): ?Notification
    {
        // Si no hay company_id, no crear la notificación (usuarios super admin no tienen compañía)
        if ($companyId === null) {
            return null;
        }

        return Notification::create(array_merge([
            'id' => (string) Str::uuid(),
            'company_id' => $companyId,
            'user_id' => $userId,
            'title' => $context['title'] ?? null,
            'category' => $context['category'] ?? null,
            'description' => $description,
            'meta' => $context['meta'] ?? null,
            'status' => $context['status'] ?? 'A',
            'read_at' => $context['read_at'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $context));
    }
}


