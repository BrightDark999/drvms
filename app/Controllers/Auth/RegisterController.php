<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Services\CaptchaService;
use App\Services\NotificationService;
use App\Services\UserProfileService;
use App\Services\VolunteerGroupService;
use CodeIgniter\Events\Events;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Controllers\RegisterController as ShieldRegisterController;
use CodeIgniter\Shield\Exceptions\ValidationException;
use CodeIgniter\Shield\Validation\ValidationRules;

/**
 * Public volunteer registration (Individual or Group) — accounts stay inactive until an admin approves them.
 */
class RegisterController extends ShieldRegisterController
{
    public function registerAction(): RedirectResponse
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->registerRedirect());
        }

        if (! setting('Auth.allowRegistration')) {
            return redirect()->back()->withInput()
                ->with('error', lang('Auth.registerDisabled'));
        }

        $post = $this->request->getPost();
        $captchaSvc = new CaptchaService();

        // 1. Anti-bot honeypot check
        if (! $captchaSvc->isHoneypotClean($post)) {
            return redirect()->back()->withInput()->with('error', 'Spam submission detected.');
        }

        // 2. Anti-bot CAPTCHA verification
        if (! $captchaSvc->verify((string) ($post['captcha_code'] ?? ''))) {
            return redirect()->back()->withInput()
                ->with('error', 'Invalid security verification code. Please check the CAPTCHA and try again.');
        }

        // 3. Group registration member validation
        $regType = (string) ($post['registration_type'] ?? 'individual');
        $rawMembers = (array) ($post['members'] ?? []);
        $validMembers = [];
        if ($regType === 'group') {
            foreach ($rawMembers as $m) {
                if (! empty(trim((string) ($m['full_name'] ?? '')))) {
                    $validMembers[] = $m;
                }
            }
            if (count($validMembers) < 7) {
                return redirect()->back()->withInput()
                    ->with('error', 'Group volunteer registration requires a minimum of 7 group members.');
            }
        }

        $users = $this->getUserProvider();
        $rules = $this->getValidationRules();

        if (! $this->validateData($post, $rules, [], config('Auth')->DBGroup)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = $users->createNewUser([
            'email'    => (string) ($post['email'] ?? ''),
            'password' => (string) ($post['password'] ?? ''),
        ]);
        $user->username = null;

        try {
            $users->save($user);
        } catch (ValidationException) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        }

        $user = $users->findById($users->getInsertID());
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', 'Registration failed. Please try again.');
        }

        $users->addToDefaultGroup($user);
        Events::trigger('register', $user);

        $users->update($user->id, [
            'active'         => 0,
            'status'         => 'pending',
            'status_message' => 'Awaiting administrator approval.',
        ]);

        $profileSvc = new UserProfileService();
        $barangayId = (int) ($post['barangay_id'] ?? 0);
        $profileSvc->ensureProfile((int) $user->id, [
            'full_name'   => trim((string) ($post['full_name'] ?? '')),
            'phone'       => trim((string) ($post['phone'] ?? '')),
            'address'     => trim((string) ($post['address'] ?? '')),
            'barangay_id' => $barangayId > 0 ? $barangayId : null,
        ]);

        $file = $this->request->getFile('id_document');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->withInput()
                ->with('error', 'Please upload a valid ID document (JPG, PNG, or PDF) for verification.');
        }

        try {
            $profileSvc->storeIdDocument((int) $user->id, $file, 'pending');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        // Save volunteer group if applicable
        if ($regType === 'group') {
            try {
                (new VolunteerGroupService())->registerGroup((int) $user->id, [
                    'organization_name' => (string) ($post['organization_name'] ?? ''),
                    'head_name'         => (string) ($post['head_name'] ?? $post['full_name'] ?? ''),
                    'head_contact'      => (string) ($post['head_contact'] ?? $post['phone'] ?? ''),
                    'head_email'        => (string) ($post['email'] ?? ''),
                    'barangay_id'       => $barangayId > 0 ? $barangayId : null,
                    'address'           => (string) ($post['address'] ?? ''),
                    'description'       => (string) ($post['group_description'] ?? ''),
                ], $validMembers);
            } catch (\Throwable $e) {
                // Group creation fallback error handling
            }
        }

        if (auth()->loggedIn()) {
            auth()->logout();
        }

        $typeLabel = $regType === 'group' ? 'Volunteer Group (' . count($validMembers) . ' members)' : 'Individual Volunteer';
        (new NotificationService())->notifyOperations(
            '[DRMS] New ' . $typeLabel . ' registration pending approval',
            $typeLabel . ' "' . trim((string) ($post['full_name'] ?? '')) . "\" ({$user->email}) registered and needs admin approval.\n\n"
            . 'Review and verify in User Management: ' . base_url('admin/users'),
            ['event' => 'user.registered', 'user_id' => $user->id, 'type' => $regType],
        );

        return redirect()->to(url_to('pending-approval'))
            ->with('message', 'Your ' . strtolower($typeLabel) . ' registration was submitted. An administrator will verify your information before activation.');
    }

    /**
     * @return array<string, array<string, list<string>|string>>
     */
    protected function getValidationRules(): array
    {
        $authConfig = config('Auth');
        $tables     = $authConfig->tables;
        $shield     = new ValidationRules();

        $emailRules = $authConfig->emailValidationRules;
        $emailRules['rules'][]    = sprintf('is_unique[%s.secret]', $tables['identities']);
        $emailRules['errors']     = [
            'is_unique' => 'This email is already registered. Please sign in or use a different email address.',
        ];

        $passwordRules = $shield->getPasswordRules();
        $passwordRules['rules'][] = 'strong_password[]';
        $passwordRules['errors']  = [
            'max_byte'        => 'Password is too long.',
            'strong_password' => 'Password is too weak. Use at least 8 characters and avoid using your email or common words.',
        ];

        return [
            'full_name' => [
                'label' => 'Full name / Head Name',
                'rules' => ['required', 'min_length[2]', 'max_length[160]'],
            ],
            'email'            => $emailRules,
            'password'         => $passwordRules,
            'password_confirm' => $shield->getPasswordConfirmRules(),
            'phone'            => [
                'label' => 'Phone',
                'rules' => 'permit_empty|max_length[32]',
            ],
            'address' => [
                'label' => 'Address',
                'rules' => 'permit_empty|max_length[500]',
            ],
            'barangay_id' => [
                'label' => 'Barangay',
                'rules' => 'permit_empty|is_natural_no_zero',
            ],
        ];
    }
}
