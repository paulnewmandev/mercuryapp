<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controlador encargado de gestionar las notificaciones del usuario autenticado.
 */
class NotificationController extends Controller
{
    /**
     * Muestra el listado paginado de notificaciones del usuario.
     *
     * @param Request $request
     *
     * @return View
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = Notification::query()
            ->where('company_id', $user->company_id)
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->latest('created_at')
            ->paginate(15);

        return view('Notifications.Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Marca una notificación como leída.
     *
     * @param Request $request
     * @param Notification $notification
     *
     * @return JsonResponse|RedirectResponse
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse|RedirectResponse
    {
        $this->authorizeNotification($notification);

        if (! $notification->read_at) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Notificación marcada como leída.'),
            ]);
        }

        return back()->with('status', __('Notificación marcada como leída.'));
    }

    /**
     * Elimina la notificación indicada.
     *
     * @param Request $request
     * @param Notification $notification
     *
     * @return JsonResponse|RedirectResponse
     */
    public function destroy(Request $request, Notification $notification): JsonResponse|RedirectResponse
    {
        $this->authorizeNotification($notification);

        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Notificación eliminada correctamente.'),
            ], Response::HTTP_OK);
        }

        return back()->with('status', __('Notificación eliminada correctamente.'));
    }

    /**
     * Verifica que la notificación pertenezca a la compañía del usuario autenticado.
     *
     * @param Notification $notification
     *
     * @return void
     */
    protected function authorizeNotification(Notification $notification): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        abort_if(
            $notification->company_id !== $user->company_id
                || ($notification->user_id && $notification->user_id !== $user->id),
            Response::HTTP_FORBIDDEN
        );
    }
}

