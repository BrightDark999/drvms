<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserNotificationModel;

class UserNotificationService
{
    public function __construct(
        private UserNotificationModel $notifications = new UserNotificationModel(),
    ) {
    }

    public function notify(int $userId, string $title, string $body, ?string $url = null): int
    {
        $this->notifications->insert([
            'user_id' => $userId,
            'title'   => $title,
            'body'    => $body,
            'url'     => $url,
            'is_read' => 0,
        ]);

        return (int) $this->notifications->getInsertID();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function unread(int $userId, int $limit = 20): array
    {
        return $this->notifications
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->where('user_id', $userId)->where('is_read', 0)->countAllResults();
    }

    public function markRead(int $id, int $userId): void
    {
        $row = $this->notifications->find($id);
        if ($row && (int) $row['user_id'] === $userId) {
            $this->notifications->update($id, ['is_read' => 1]);
        }
    }

    public function markAllRead(int $userId): void
    {
        $this->notifications->where('user_id', $userId)->where('is_read', 0)->set(['is_read' => 1])->update();
    }
}
