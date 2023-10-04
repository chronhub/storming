<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Message\Message;
use Storm\Reporter\Subscriber\NameReporter;

class AttributeServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        // create cache file from a config file which include directories to scan, autoload classes and namespaces
        // and exclude directories or tests
    }

    public function register(): void
    {

        //tmp
        //        $this->app->bind(MessageFilter::class, fn () => new class implements MessageFilter
        //        {
        //            public function allows(Message $message): bool
        //            {
        //                return true;
        //            }
        //        });

        //$this->app->when(NameReporter::class)->needs('$name')->give('foo');

        $this->app->singleton(Loader::class);
    }

    public function provides(): array
    {
        return [Loader::class];
    }
}
