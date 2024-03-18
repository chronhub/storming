<?php

declare(strict_types=1);

namespace Storm\Annotation;

use Illuminate\Container\RewindableGenerator;
use RuntimeException;
use Storm\Message\Attribute\MessageHandler;

use function iterator_to_array;
use function sprintf;

class MessageServiceLocator
{
    const UNPROCESSABLE_FOUND_MESSAGE = 'Message %s found but it belongs to %s reporter id and expected %s reporter id';

    public function __construct(protected KernelStorage $container)
    {
    }

    public function get(string $reporterId, string $messageName): ?array
    {
        $messageHandlers = $this->container->findMessage($messageName);

        if ($messageHandlers instanceof RewindableGenerator) {
            $messageHandlers = iterator_to_array($messageHandlers);

            // todo queue handling wip
            foreach ($messageHandlers as $messageHandler) {
                /** @phpstan-ignore-next-line */
                $this->assertReporterCanProcessMessage($messageHandler, $reporterId);
            }

            return $messageHandlers;
        }

        return null;
    }

    protected function assertReporterCanProcessMessage(MessageHandler $messageHandler, string $reporterId): void
    {
        if ($messageHandler->reporterId() !== $reporterId) {
            throw new RuntimeException(sprintf(
                self::UNPROCESSABLE_FOUND_MESSAGE,
                $messageHandler->name(),
                $messageHandler->reporterId(),
                $reporterId
            ));
        }
    }
}
