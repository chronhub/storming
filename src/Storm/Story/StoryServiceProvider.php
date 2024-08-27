<?php

declare(strict_types=1);

namespace Storm\Story;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Story\Build\AttributeMap;
use Storm\Story\Console\ListStoryMessageCommand;

class StoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public const string DEFAULT_STORY_CONTEXT_ID = 'storm.story.context.default';

    protected array $commands = [
        ListStoryMessageCommand::class,
    ];

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    public function register(): void
    {
        $this->registerMapper();

        $this->app->bind(self::DEFAULT_STORY_CONTEXT_ID, DefaultStoryContext::class);
    }

    protected function registerMapper(): void
    {
        $this->app->singleton(AttributeMap::class, function () {
            $map = new AttributeMap(config('storm.scan', []));
            $map->build();

            return $map;
        });
    }

    public function provides(): array
    {
        return [
            AttributeMap::class,
            self::DEFAULT_STORY_CONTEXT_ID,
        ];
    }
}
