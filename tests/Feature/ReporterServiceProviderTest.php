<?php

declare(strict_types=1);

namespace Feature;

use Storm\Contract\Message\Header;
use Storm\Contract\Message\MessageProducer;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Message\Message;
use Storm\Message\SyncMessageProducer;
use Storm\Reporter\ManageReporter;
use Storm\Reporter\Subscriber\HandleCommand;
use Storm\Reporter\Subscriber\MakeMessage;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tracker\GenericListener;

it('register', function () {
    expect($this->app->has(MessageProducer::class))->toBeTrue()
        ->and($this->app[MessageProducer::class])->toBeInstanceOf(SyncMessageProducer::class);
});

it('test reporter manager', function () {
    $message = new Message(SomeCommand::fromContent(['id' => 1]));

    $result = null;
    $handler = function (SomeCommand $command) use (&$result) {
        $result = $command->toContent();
    };

    /** @var ManageReporter $manager */
    $manager = $this->app[ManageReporter::class];

    $reporter = $manager->create('command-default');
    $reporter->subscribe(MakeMessage::class, HandleCommand::class);

    $reporter->subscribe(new GenericListener(Reporter::DISPATCH_EVENT, function (MessageStory $story) use ($handler) {
        $story->withHandlers([$handler]);
    }, 1000));

    /** @var ?Message $expected */
    $expected = null;
    $reporter->subscribe(new GenericListener(Reporter::DISPATCH_EVENT, function (MessageStory $story) use (&$expected) {
        $expected = $story->message();
    }, -1));

    $reporter->relay($message);

    expect($expected)->toBeInstanceOf(Message::class)
        ->and($expected->event()->toContent())->toBe(['id' => 1])
        ->and($result)->toBe(['id' => 1])
        ->and($expected->header(Header::REPORTER_ID))->toBe('reporter-command-default');
});

it('dispatch array command', function () {
    $message = [
        'headers' => [Header::EVENT_TYPE => SomeCommand::class],
        'content' => ['id' => 1],
    ];

    $result = null;
    $handler = function (SomeCommand $command) use (&$result) {
        $result = $command->toContent();
    };

    /** @var ManageReporter $manager */
    $manager = $this->app[ManageReporter::class];

    $reporter = $manager->create('command-default');
    $reporter->subscribe(MakeMessage::class, HandleCommand::class);

    $reporter->subscribe(new GenericListener(Reporter::DISPATCH_EVENT, function (MessageStory $story) use ($handler) {
        $story->withHandlers([$handler]);
    }, 1000));

    /** @var ?Message $expected */
    $expected = null;
    $reporter->subscribe(new GenericListener(Reporter::DISPATCH_EVENT, function (MessageStory $story) use (&$expected) {
        $expected = $story->message();
    }, -1));

    $reporter->relay($message);

    expect($expected)->toBeInstanceOf(Message::class)
        ->and($expected->event())->toBeInstanceOf(SomeCommand::class)
        ->and($expected->event()->toContent())->toBe(['id' => 1])
        ->and($result)->toBe(['id' => 1])
        ->and($expected->header(Header::REPORTER_ID))->toBe('reporter-command-default');
});
