<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChatMessageModel;
use CodeIgniter\Shield\Models\UserModel;

class ChatService
{
    public function __construct(
        private ChatMessageModel $messages = new ChatMessageModel(),
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function inbox(int $userId, int $limit = 100): array
    {
        return $this->messages->builder()
            ->groupStart()
                ->where('recipient_user_id', $userId)
                ->orWhere('recipient_user_id', null)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function thread(int $userId, int $otherUserId, int $limit = 50): array
    {
        return $this->messages->builder()
            ->groupStart()
                ->groupStart()
                    ->where('sender_user_id', $userId)
                    ->where('recipient_user_id', $otherUserId)
                ->groupEnd()
                ->orGroupStart()
                    ->where('sender_user_id', $otherUserId)
                    ->where('recipient_user_id', $userId)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function send(int $senderId, ?int $recipientId, string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            throw new \RuntimeException('Message cannot be empty.');
        }

        $this->messages->insert([
            'sender_user_id'    => $senderId,
            'recipient_user_id' => $recipientId,
            'body'              => $body,
            'is_read'           => 0,
        ]);

        $row = $this->messages->find((int) $this->messages->getInsertID());
        if ($recipientId) {
            (new UserNotificationService())->notify(
                $recipientId,
                'New message',
                mb_substr($body, 0, 120),
                base_url('admin/messages?user=' . $senderId),
            );
        }

        return $row ?? [];
    }

    public function markRead(int $messageId, int $userId): void
    {
        $row = $this->messages->find($messageId);
        if ($row && (int) ($row['recipient_user_id'] ?? 0) === $userId) {
            $this->messages->update($messageId, ['is_read' => 1]);
        }
    }

    /**
     * @return list<array{id:int, username:string, email:string}>
     */
    public function operationsUsers(): array
    {
        $db  = db_connect();
        $ids = $db->table('auth_groups_users')
            ->select('user_id')
            ->whereIn('group', ['mdrrmo', 'admin'])
            ->get()
            ->getResultArray();

        $users = [];
        $model = model(UserModel::class);
        foreach ($ids as $row) {
            $u = $model->findById((int) $row['user_id']);
            if ($u) {
                $users[] = ['id' => (int) $u->id, 'username' => (string) $u->username, 'email' => (string) $u->email];
            }
        }

        return $users;
    }
}
