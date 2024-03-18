<?php

declare(strict_types=1);

namespace Storm\Annotation;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;
use Storm\Chronicler\Attribute\ChroniclerMap;
use Storm\Chronicler\Attribute\Subscriber\StreamSubscriberMap;
use Storm\Message\Attribute\MessageMap;
use Storm\Reporter\Attribute\DeclaredQueue;
use Storm\Reporter\Attribute\ReporterMap;
use Storm\Reporter\Attribute\Subscriber\SubscriberMap;
use Storm\Reporter\Exception\MessageNotFound;

use function count;
use function is_array;
use function is_object;

class InMemoryKernelStorage implements KernelStorage
{
    public function __construct(
        protected ReporterMap $reporters,
        protected SubscriberMap $subscribers,
        protected MessageMap $messages,
        protected ChroniclerMap $chroniclers,
        protected StreamSubscriberMap $streamSubscribers,
        protected Application $app
    ) {
    }

    public function findMessage(string $messageName): iterable
    {
        return $this->messages->find($messageName);
    }

    public function getReporterByMessage(array|object $message, ?string $messageClassName = null): string
    {
        if (is_array($message) && $messageClassName === null) {
            throw new InvalidArgumentException('Message class name is required when message is an array');
        }

        $messageClass = is_object($message) ? $message::class : $messageClassName;

        $reporters = $this->findReporterOfMessage($messageClass);

        if ($reporters === []) {
            throw MessageNotFound::withMessageName($messageClass);
        }

        // we do not deal with multiple event reporters
        if (count($reporters) > 1) {
            throw new RuntimeException('Multiple reporters found for message '.$messageClass);
        }

        return $reporters[0];
    }

    public function findReporterOfMessage(string $messageName): array
    {
        return $this->messages
            ->getEntries()
            ->filter(fn (array $messageHandlers, string $message) => $message === $messageName)
            ->values()
            ->collapse()
            ->pluck('reporterId')
            ->unique()
            ->toArray();
    }

    public function getMessages(): Collection
    {
        return $this->messages->getEntries();
    }

    public function getReporters(): Collection
    {
        return $this->reporters->getEntries();
    }

    public function getChroniclers(): Collection
    {
        return $this->chroniclers->getEntries();
    }

    public function getStreamSubscribers(): Collection
    {
        return $this->streamSubscribers->getEntries();
    }

    public function getDeclaredQueues(): DeclaredQueue
    {
        return $this->reporters->getDeclaredQueue();
    }
}
