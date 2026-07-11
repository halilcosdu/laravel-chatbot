<?php

namespace HalilCosdu\ChatBot\Responses;

use Closure;
use Generator;
use Illuminate\Database\Eloquent\Model;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * A single-use text stream tied to its local chatbot thread.
 *
 * @implements IteratorAggregate<int, string>
 */
final class StreamedThreadResponse implements IteratorAggregate
{
    private ?Model $assistantMessage = null;

    private bool $started = false;

    /**
     * @param  Closure(): Generator<int, string, mixed, Model>  $stream
     */
    public function __construct(
        public readonly Model $thread,
        private readonly Closure $stream,
    ) {}

    /**
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        if ($this->started) {
            throw new LogicException('A streamed chatbot response can only be consumed once.');
        }

        $this->started = true;
        $generator = ($this->stream)();

        yield from $generator;

        $this->assistantMessage = $generator->getReturn();
    }

    public function assistantMessage(): ?Model
    {
        return $this->assistantMessage;
    }

    public function completed(): bool
    {
        return $this->assistantMessage !== null;
    }
}
