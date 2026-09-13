<?php
// app/Services/Api/Ynov/NotificationService.php
namespace App\Services\Api\Ynov;

use App\Mail\TransmettreRdvMail;
use App\Models\Api\Ynov\parameter\Notification;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NotificationService
{

    /**
     * Nettoyer une chaîne pour la base de données
     * Supprime les émojis et caractères spéciaux non supportés
     */
    private function cleanString(string $string): string
    {
        // Supprimer les émojis et caractères spéciaux
        // Cette expression régulière supprime les caractères Unicode au-delà de BMP
        $string = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $string);
        
        // Alternative: supprimer tous les emojis courants
        $string = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $string); // Emoticons
        $string = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $string); // Misc Symbols
        $string = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $string); // Transport
        $string = preg_replace('/[\x{1F700}-\x{1F77F}]/u', '', $string); // Alchemical
        $string = preg_replace('/[\x{1F780}-\x{1F7FF}]/u', '', $string); // Geometric
        $string = preg_replace('/[\x{1F800}-\x{1F8FF}]/u', '', $string); // Supplemental
        $string = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $string); // Misc Symbols
        
        return trim($string);
    }
    /**
     * Créer une notification
     */
    public function create(array $data): Notification
    {
        return DB::transaction(function () use ($data) {
            $title = isset($data['title']) ? $this->cleanString($data['title']) : null;
            $body = isset($data['body']) ? $this->cleanString($data['body']) : null;
            $notification = Notification::create([
                'uuid_notification' => (string) Str::uuid(),
                'user_uuid' => $data['user_uuid'],
                'group_notif_uuid' => $data['group_notif_uuid'] ?? null,
                'title' => $title,
                'body' => $body,
                'type' => $data['type'] ?? 'system',
                'action_url' => $data['action_url'] ?? null,
                'action_label' => $data['action_label'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'channel' => $data['channel'] ?? 'database',
                'created_by' => $data['created_by'] ?? null,
            ]);

            ActivityLog::log([
                'user_uuid' => $data['user_uuid'],
                'action' => 'notification_created',
                'action_type' => 'notification',
                'module' => 'notifications',
                'description' => "Notification créée : {$title}",
                'resource_type' => 'notification',
                'resource_id' => $notification->uuid_notification,
                'level' => 'info',
            ]);

            return $notification;
        });
    }

    /**
     * Créer une notification pour plusieurs utilisateurs
     */
    public function createForUsers(array $userUuids, array $data): array
    {
        $notifications = [];
        foreach ($userUuids as $userUuid) {
            $notifications[] = $this->create(array_merge($data, ['user_uuid' => $userUuid]));
        }
        return $notifications;
    }

    /**
     * Créer une notification pour tous les utilisateurs d'un groupe
     */
    public function createForGroup(string $groupNotifUuid, array $data): array
    {
        $users = User::whereHas('groupNotifs', function ($query) use ($groupNotifUuid) {
            $query->where('group_notif_uuid', $groupNotifUuid);
        })->get();

        $notifications = [];
        foreach ($users as $user) {
            $notifications[] = $this->create(array_merge($data, ['user_uuid' => $user->uuid_user]));
        }
        return $notifications;
    }

    /**
     * Récupérer les notifications d'un utilisateur
     */
    public function getUserNotifications(string $userUuid, array $filters = [], int $perPage = 20)
    {
        $query = Notification::where('user_uuid', $userUuid);

        // Filtres
        if (isset($filters['read'])) {
            if ($filters['read']) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        if (isset($filters['important'])) {
            if ($filters['important']) {
                $query->important();
            }
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['group_notif_uuid'])) {
            $query->inGroup($filters['group_notif_uuid']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('body', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(string $notificationUuid, string $userUuid): ?Notification
    {
        $notification = Notification::where('uuid_notification', $notificationUuid)
            ->where('user_uuid', $userUuid)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return $notification;
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(string $userUuid): int
    {
        return Notification::where('user_uuid', $userUuid)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Marquer une notification comme importante
     */
    public function markAsImportant(string $notificationUuid, string $userUuid): ?Notification
    {
        $notification = Notification::where('uuid_notification', $notificationUuid)
            ->where('user_uuid', $userUuid)
            ->first();

        if ($notification) {
            $notification->markAsImportant();
        }

        return $notification;
    }

    /**
     * Retirer le statut important
     */
    public function unmarkImportant(string $notificationUuid, string $userUuid): ?Notification
    {
        $notification = Notification::where('uuid_notification', $notificationUuid)
            ->where('user_uuid', $userUuid)
            ->first();

        if ($notification) {
            $notification->unmarkImportant();
        }

        return $notification;
    }

    /**
     * Supprimer une notification (soft delete)
     */
    public function delete(string $notificationUuid, string $userUuid): bool
    {
        return Notification::where('uuid_notification', $notificationUuid)
            ->where('user_uuid', $userUuid)
            ->delete();
    }

    /**
     * Compter les notifications non lues
     */
    public function countUnread(string $userUuid): int
    {
        return Notification::where('user_uuid', $userUuid)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Compter les notifications importantes
     */
    public function countImportant(string $userUuid): int
    {
        return Notification::where('user_uuid', $userUuid)
            ->where('is_important', true)
            ->count();
    }

    /**
     * Envoyer un email avec pièce jointe
     */
    public function sendEmailWithAttachment(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $gestionnaire = User::where('uuid_user', $data['gestionnaire_uuid'])->first();
            
            if (!$gestionnaire) {
                return [
                    'success' => false,
                    'message' => 'Gestionnaire non trouvé.',
                    'code' => 'GESTIONNAIRE_NOT_FOUND',
                    'status' => 404,
                ];
            }

            // Sujet et message automatiques
            $sujet = "Transmission de RDV pour traitement";
            $message = "Bonjour,\n\nVous trouverez ci-joint le fichier Excel contenant les RDV transmis pour traitement.\n\nVeuillez procéder au traitement dans les meilleurs délais.\n\nCordialement,\nL'équipe YNOV.";

            // Stocker le fichier temporairement
            $fichier = $data['fichier'];
            $fichierNom = $fichier->getClientOriginalName();
            $fichierExtension = $fichier->getClientOriginalExtension();
            $fichierPath = $fichier->storeAs('temp', uniqid() . '.' . $fichierExtension, 'local');

            try {
                // Envoyer l'email au gestionnaire principal
                Mail::to($gestionnaire->email)
                    ->cc($data['copie_cc'] ?? [])
                    ->send(new TransmettreRdvMail(
                        $gestionnaire->email,
                        storage_path('app/' . $fichierPath),
                        $fichierNom,
                        $sujet,
                        $message
                    ));

                // Créer une notification pour le gestionnaire (database)
                $notification = $this->create([
                    'user_uuid' => $gestionnaire->uuid_user,
                    'title' => $sujet,
                    'body' => $message,
                    'type' => 'email',
                    'channel' => 'database',
                    'metadata' => [
                        'fichier_nom' => $fichierNom,
                        'fichier_taille' => $fichier->getSize(),
                        'envoye_par' => $data['envoye_par'] ?? null,
                        'copie_cc' => $data['copie_cc'] ?? [],
                    ],
                    'created_by' => $data['envoye_par'] ?? null,
                ]);

                // Créer des notifications database pour les destinataires en copie
                if (!empty($data['copie_cc'])) {
                    foreach ($data['copie_cc'] as $emailCc) {
                        $userCc = User::where('email', $emailCc)->first();
                        if ($userCc) {
                            $this->create([
                                'user_uuid' => $userCc->uuid_user,
                                'title' => "[Copie] " . $sujet,
                                'body' => $message,
                                'type' => 'email',
                                'channel' => 'database',
                                'metadata' => [
                                    'is_copy' => true,
                                    'original_recipient' => $gestionnaire->email,
                                ],
                                'created_by' => $data['envoye_par'] ?? null,
                            ]);
                        }
                    }
                }

                // Log d'activité
                ActivityLog::log([
                    'user_uuid' => $data['envoye_par'] ?? null,
                    'action' => 'rdv_transmis_par_email',
                    'action_type' => 'email',
                    'module' => 'rdvs',
                    'description' => "Email avec fichier Excel envoyé à {$gestionnaire->email}",
                    'resource_type' => 'rdv',
                    'level' => 'info',
                    'old_values' => [],
                    'new_values' => [
                        'gestionnaire_uuid' => $gestionnaire->uuid_user,
                        'gestionnaire_email' => $gestionnaire->email,
                        'sujet' => $sujet,
                        'fichier_nom' => $fichierNom,
                        'copie_cc' => $data['copie_cc'] ?? [],
                    ],
                ]);

                return [
                    'success' => true,
                    'message' => 'Email transmis avec succès.',
                    'code' => 'EMAIL_TRANSMIS',
                    'status' => 200,
                    'data' => [
                        'notification_uuid' => $notification->uuid_notification,
                        'gestionnaire_email' => $gestionnaire->email,
                        'fichier_nom' => $fichierNom,
                    ],
                ];
            } finally {
                // Nettoyer le fichier temporaire
                if (Storage::disk('local')->exists($fichierPath)) {
                    Storage::disk('local')->delete($fichierPath);
                }
            }
        });
    }
}