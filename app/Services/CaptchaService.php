<?php

declare(strict_types=1);

namespace App\Services;

class CaptchaService
{
    private const SESSION_KEY = 'drms_captcha_code';
    public const HONEYPOT_KEY = 'drms_website_hp';

    /** Generate code, save to session, and return SVG image content */
    public function generateSvg(?string $forcedCode = null): string
    {
        if ($forcedCode !== null && $forcedCode !== '') {
            $code = strtoupper($forcedCode);
        } else {
            $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
            $length = 5;
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $code = strtoupper($code);
        }

        try {
            $session = function_exists('session') ? session() : \Config\Services::session();
            $session->set(self::SESSION_KEY, $code);
        } catch (\Throwable $e) {
            // Ignore in CLI testing context
        }

        $width = 160;
        $height = 44;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" style="background:#2b1b1d;border-radius:6px;user-select:none;">';

        // Background noise lines
        for ($i = 0; $i < 4; $i++) {
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);
            $x2 = random_int(0, $width);
            $y2 = random_int(0, $height);
            $stroke = ['#c62828', '#e57373', '#ff8a80', '#b71c1c'][random_int(0, 3)];
            $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $stroke . '" stroke-width="1.5" opacity="0.4"/>';
        }

        // Characters with random transforms
        $colors = ['#ffffff', '#ffcdd2', '#ffebee', '#ef9a9a', '#ffffff'];
        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $char = $code[$i];
            $x = 20 + ($i * 24);
            $y = 30 + random_int(-2, 2);
            $angle = random_int(-15, 15);
            $color = $colors[$i % count($colors)];
            $svg .= '<text x="' . $x . '" y="' . $y . '" fill="' . $color . '" font-family="monospace, sans-serif" font-size="22" font-weight="bold" transform="rotate(' . $angle . ' ' . $x . ' ' . $y . ')">' . $char . '</text>';
        }

        $svg .= '</svg>';
        return $svg;
    }

    /** Verify user input against session code */
    public function verify(?string $input): bool
    {
        try {
            $session = function_exists('session') ? session() : \Config\Services::session();
            $expected = $session->get(self::SESSION_KEY);
            if (empty($expected) || empty($input)) {
                return false;
            }

            $isValid = hash_equals(strtoupper(trim((string) $expected)), strtoupper(trim((string) $input)));
            if ($isValid) {
                $session->remove(self::SESSION_KEY);
            }

            return $isValid;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Verify that the hidden honeypot field is empty */
    public static function isHoneypotClean(array $post, string $key = self::HONEYPOT_KEY): bool
    {
        return empty($post[$key]);
    }
}
