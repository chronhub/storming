<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

/**
 * @template TDEvent of DomainEvent
 *
 * @template-covariant TEvent of TDEvent
 */
interface DomainEvent extends Messaging {}
