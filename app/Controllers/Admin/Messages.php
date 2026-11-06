<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ChatService;
use CodeIgniter\HTTP\ResponseInterface;

class Messages extends BaseController
{
    public function index(): string
    {
        $uid     = (int) auth()->id();
        $chat    = new ChatService();
        $peerId  = (int) ($this->request->getGet('user') ?? 0);
        $thread  = $peerId > 0 ? $chat->thread($uid, $peerId) : [];
        $inbox   = $chat->inbox($uid);

        return view('admin/messages/index', [
            'title'     => 'Messages',
            'drms'      => drms_config(),
            'inbox'     => $inbox,
            'thread'    => $thread,
            'peerId'    => $peerId,
            'contacts'  => $chat->operationsUsers(),
        ]);
    }

    public function send(): ResponseInterface
    {
        $body   = trim((string) $this->request->getPost('body'));
        $peerId = ($this->request->getPost('recipient_user_id') ?? '') !== ''
            ? (int) $this->request->getPost('recipient_user_id')
            : null;

        try {
            $msg = (new ChatService())->send((int) auth()->id(), $peerId, $body);

            return $this->response->setJSON(['status' => 'ok', 'message' => $msg]);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function broadcast(): ResponseInterface
    {
        $body = trim((string) $this->request->getPost('body'));
        try {
            $msg = (new ChatService())->send((int) auth()->id(), null, '[Announcement] ' . $body);

            return $this->response->setJSON(['status' => 'ok', 'message' => $msg]);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
