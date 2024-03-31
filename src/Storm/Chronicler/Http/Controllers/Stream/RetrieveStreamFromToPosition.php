<?php

declare(strict_types=1);

namespace Storm\Chronicler\Http\Controllers\Stream;

use Generator;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use stdClass;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Chronicler\Http\ResponseFactory;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Stream\StreamName;
use Throwable;

use function array_map;
use function iterator_to_array;

final readonly class RetrieveStreamFromToPosition
{
    public function __construct(
        private Chronicler $chronicler,
        private Factory $validator,
        private ResponseFactory $response,
        private StreamEventSerializer $serializer
    ) {
    }

    /**
     * @throws ValidationException
     * @throws NoStreamEventReturn
     * @throws StreamNotFound
     * @throws Throwable
     */
    public function __invoke(Request $request): ResponseFactory
    {
        $this->validateRequest($request);

        $streamEvents = $this->retrieveStreamEvents($request);

        return $this->response->withStatusCode(200)->withData($streamEvents);
    }

    private function retrieveStreamEvents(Request $request): array
    {
        $streamEvents = $this->chronicler->retrieveFiltered(
            new StreamName($request->get('name')),
            $this->queryFilter(
                $request->get('from'),
                $request->get('to'),
                $request->get('direction')
            )
        );

        return $this->convertStreamEventsToArray($streamEvents);
    }

    private function convertStreamEventsToArray(Generator $streamEvents): array
    {
        return array_map(
            fn (stdClass $streamEvent) => $this->serializer->toStreamEvent($streamEvent)->jsonSerialize(),
            iterator_to_array($streamEvents)
        );
    }

    /**
     * @throws ValidationException
     */
    private function validateRequest(Request $request): void
    {
        $this->validator->make($request->all(),
            [
                'name' => 'required|string',
                'from' => 'required|integer|min:0|not_in:0',
                'to' => 'required|integer|gt:from',
                'direction' => 'required|string|in:asc,desc',
            ]
        )->validate();
    }

    // todo abstract this to a QueryFilterFactory
    private function queryFilter(int $from, int $to, string $direction): QueryFilter
    {
        return new readonly class($from, $to, $direction) implements QueryFilter
        {
            public function __construct(
                private int $from,
                private int $to,
                private string $direction
            ) {
            }

            public function apply(): callable
            {
                return function (Builder $query): void {
                    $query->whereBetween('position', [$this->from, $this->to]);
                    $query->orderBy('position', $this->direction);
                };
            }
        };
    }
}
