<?php

declare(strict_types=1);

return [
    'reporters' => [
        \Storm\Reporter\ReportCommand::class,
        \Storm\Reporter\ReportEvent::class,
        \Storm\Reporter\ReportQuery::class,
    ],

    'reporter_subscribers' => [
        \Storm\Reporter\Subscriber\MakeMessage::class,
        \Storm\Reporter\Subscriber\MessageDecorators::class,
        \Storm\Reporter\Subscriber\RouteMessage::class,
        \Storm\Reporter\Subscriber\QueryRouteMessage::class,
        \Storm\Reporter\Subscriber\HandleCommand::class,
        \Storm\Reporter\Subscriber\HandleEvent::class,
        \Storm\Reporter\Subscriber\HandleQuery::class,
        \Storm\Reporter\Subscriber\TransactionalCommand::class,
        \Storm\Reporter\Subscriber\CorrelationHeaderCommand::class,
    ],

    'message_handlers' => [

    ],

    'chroniclers' => [
        \Storm\Chronicler\Connection\PgsqlTransactionalChronicler::class,
    ],

    'stream_subscribers' => [
        \Storm\Chronicler\Subscriber\AppendOnlyStream::class,
        \Storm\Chronicler\Subscriber\DeleteStream::class,
        \Storm\Chronicler\Subscriber\FilterCategories::class,
        \Storm\Chronicler\Subscriber\FilterStreams::class,
        \Storm\Chronicler\Subscriber\BeginTransaction::class,
        \Storm\Chronicler\Subscriber\CommitTransaction::class,
        \Storm\Chronicler\Subscriber\RollbackTransaction::class,
        \Storm\Chronicler\Subscriber\RetrieveAllStream::class,
        \Storm\Chronicler\Subscriber\RetrieveAllBackwardStream::class,
        \Storm\Chronicler\Subscriber\RetrieveFilteredStream::class,
        \Storm\Chronicler\Subscriber\StreamExists::class,
        \Storm\Chronicler\Publisher\EventPublisherSubscriber::class,
        \Storm\Reporter\Subscriber\CorrelationHeaderCommand::class,
    ],

    'aggregate_repositories' => [

    ],
];
