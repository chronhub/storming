<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

enum Mode: string
{
    case SYNC = 'sync';

    case ASYNC = 'async';

    case DELEGATE = 'delegate';

    case DELEGATE_MERGE = 'delegate_merge_with_default';

    public function isSync(): bool
    {
        return $this === self::SYNC;
    }

    public function isAsync(): bool
    {
        return $this === self::ASYNC;
    }

    public function isDelegateMerge(): bool
    {
        return $this === self::DELEGATE_MERGE;
    }

    public function isDelegate(): bool
    {
        return $this === self::DELEGATE;
    }

    public static function toArray(): array
    {
        return [
            self::SYNC->value,
            self::ASYNC->value,
            self::DELEGATE->value,
            self::DELEGATE_MERGE->value,
        ];
    }
}
