<?php

namespace Modules\FluidPay\Support;

class Config
{
    public const DOCUMENT_INVOICES = 'invoices';
    public const DOCUMENT_RETAINERS = 'retainers';

    public static function documentKeys(): array
    {
        return [self::DOCUMENT_INVOICES, self::DOCUMENT_RETAINERS];
    }

    /**
     * Default configuration for a given document type.
     */
    public static function defaults(string $document = self::DOCUMENT_INVOICES): array
    {
        $base = [
            'payment' => [
                'types' => [
                    'card' => true,
                    'ach' => true,
                ],
                'card' => [
                    'requireCVV' => true,
                    'strict_mode' => false,
                    'mask_number' => false,
                ],
                'ach' => [
                    'sec_code' => 'web',
                    'showSecCode' => false,
                    'verifyAccountRouting' => true,
                ],
                'calculateFees' => true,
            ],
            'user' => [
                'showName' => true,
                'showEmail' => true,
                'showPhone' => true,
                'showTitle' => true,
                'showInline' => true,
            ],
            'billing' => [
                'show' => true,
                'showTitle' => true,
            ],
            'shipping' => [
                'show' => true,
                'showTitle' => true,
            ],
        ];

        if ($document === self::DOCUMENT_RETAINERS) {
            $base['shipping']['show'] = false;
            $base['shipping']['showTitle'] = false;
        }

        return $base;
    }

    /**
     * Retrieve configuration merged with defaults for a document type.
     */
    public static function get(string $document = self::DOCUMENT_INVOICES): array
    {
        $defaults = static::defaults($document);
        $stored = setting("fluidpay.$document") ?? setting("fluidpay_{$document}");

        if (is_string($stored)) {
            $decoded = json_decode($stored, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $stored = $decoded;
            } else {
                $stored = [];
            }
        }

        if (! is_array($stored)) {
            $stored = [];
        }

        return static::merge($defaults, $stored);
    }

    /**
     * Sanitize incoming request data against defaults before persisting.
     */
    public static function sanitize(array $input, string $document = self::DOCUMENT_INVOICES): array
    {
        return static::merge(static::defaults($document), $input);
    }

    /**
     * Available ACH SEC code options.
     */
    public static function secCodeOptions(): array
    {
        return [
            'web' => 'WEB',
            'ccd' => 'CCD',
            'ppd' => 'PPD',
            'tel' => 'TEL',
        ];
    }

    protected static function merge(array $defaults, array $input, array $path = []): array
    {
        $result = $defaults;

        foreach ($defaults as $key => $defaultValue) {
            $currentPath = array_merge($path, [$key]);

            if (is_array($defaultValue)) {
                $result[$key] = static::merge(
                    $defaultValue,
                    isset($input[$key]) && is_array($input[$key]) ? $input[$key] : [],
                    $currentPath
                );

                continue;
            }

            if (! array_key_exists($key, $input)) {
                $result[$key] = $defaultValue;
                continue;
            }

            $value = $input[$key];

            if (is_bool($defaultValue)) {
                if (is_string($value)) {
                    $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $value = $filtered === null ? $defaultValue : $filtered;
                } else {
                    $value = (bool) $value;
                }
            } elseif (is_string($defaultValue)) {
                $value = is_string($value) ? $value : (string) $value;
            } elseif (is_numeric($defaultValue)) {
                $value = is_numeric($value) ? $value : $defaultValue;
            }

            if (static::isSecCodePath($currentPath)) {
                $value = static::normaliseSecCode($value);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    protected static function isSecCodePath(array $path): bool
    {
        return $path === ['payment', 'ach', 'sec_code'];
    }

    protected static function normaliseSecCode(string $code): string
    {
        $code = strtolower($code);
        $allowed = array_keys(static::secCodeOptions());

        return in_array($code, $allowed, true) ? $code : 'web';
    }
}
