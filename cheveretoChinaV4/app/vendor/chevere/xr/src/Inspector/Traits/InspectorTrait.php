<?php

/*
 * This file is part of Chevere.
 *
 * (c) Rodolfo Berrios <rodolfo@chevere.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Chevere\Xr\Inspector\Traits;

use Chevere\Xr\Exceptions\StopException;
use Chevere\Xr\Interfaces\ClientInterface;
use Chevere\Xr\Message;

trait InspectorTrait
{
    public function __construct(
        protected ClientInterface $client,
    ) {
    }

    public function pause(
        string $t = '',
        string $e = '',
        int $f = 0,
    ): void {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
        $message = (new Message(
            backtrace: $backtrace,
        ))
            ->withTopic($t)
            ->withEmote($e)
            ->withFlags($f);

        try {
            $this->client->sendPause($message);
        } catch (StopException $e) {
            if (PHP_SAPI === 'cli') {
                echo '* ' . $e->getMessage() . PHP_EOL;
                $this->client->exit(255);
            }
        }
    }

    public function memory(
        string $t = '',
        string $e = '',
        int $f = 0,
    ): void {
        $memory = memory_get_usage(true);
        $this->sendMessage(
            body: sprintf('%.2F MB', $memory / 1000000),
            topic: $t,
            emote: $e,
            flags: $f,
        );
    }

    private function sendMessage(
        string $body = '',
        string $topic = '',
        string $emote = '',
        int $flags = 0
    ): void {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        array_shift($backtrace);
        $message = (new Message(
            backtrace: $backtrace,
        ))
            ->withBody($body)
            ->withTopic($topic)
            ->withEmote($emote)
            ->withFlags($flags);

        $this->client->sendMessage($message);
    }
}
