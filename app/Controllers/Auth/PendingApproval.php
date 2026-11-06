<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\BaseController;

class PendingApproval extends BaseController
{
    public function index(): string
    {
        return view('auth/pending_approval', [
            'title' => 'Pending Approval',
            'drms'  => drms_config(),
        ]);
    }
}
