<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

abstract class SubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'candidate';
    }

    public function rules(): array
    {
        return [
            'frontend_url' => ['required', 'url', 'max:2048', $this->lanUrlRule()],
            'backend_api_url' => ['required', 'url', 'max:2048', $this->lanUrlRule()],
        ];
    }

    protected function lanUrlRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $host = parse_url((string) $value, PHP_URL_HOST);

            // Business rule: submitted systems must be reachable inside the LAN.
            if (! is_string($host) || ! $this->isInternalHost($host)) {
                $fail('The '.$attribute.' must be an internal LAN URL.');
            }
        };
    }

    private function isInternalHost(string $host): bool
    {
        $host = strtolower($host);

        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return true;
        }

        if (! filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return str_starts_with($host, '10.')
            || str_starts_with($host, '192.168.')
            || preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host) === 1;
    }
}
