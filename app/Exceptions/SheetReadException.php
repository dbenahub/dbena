<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class SheetReadException extends RuntimeException
{
    public static function notShared(): self
    {
        return new self(__('sheets.error.not_shared'));
    }

    public static function notFound(): self
    {
        return new self(__('sheets.error.not_found'));
    }

    public static function tooLarge(int $bytes): self
    {
        return new self(__('sheets.error.too_large', ['mb' => round($bytes / 1048576, 1)]));
    }

    public static function empty(): self
    {
        return new self(__('sheets.error.empty'));
    }

    public static function network(string $reason): self
    {
        return new self(__('sheets.error.network', ['reason' => $reason]));
    }

    public static function missingCredentials(string $path): self
    {
        return new self(__('sheets.error.missing_credentials', ['path' => $path]));
    }

    public static function badCredentials(string $reason): self
    {
        return new self($reason);
    }
}
