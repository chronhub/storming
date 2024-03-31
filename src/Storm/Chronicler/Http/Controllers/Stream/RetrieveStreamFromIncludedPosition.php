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

final readonly class RetrieveStreamFromIncludedPosition
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
                $request->get('limit'),
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
                'from' => 'required|integer|min:1|not_in:0',
                'limit' => 'required|integer|gt:from',
            ]
        )->validate();
    }

    private function queryFilter(int $from, int $limit): QueryFilter
    {
        return new readonly class($from, $limit) implements QueryFilter
        {
            public function __construct(
                private int $from,
                private int $limit,
            ) {
            }

            public function apply(): callable
            {
                return function (Builder $query): void {
                    $query
                        ->where('position', '>=', $this->from)
                        ->orderBy('position')
                        ->limit($this->limit);
                };
            }
        };
    }
}
