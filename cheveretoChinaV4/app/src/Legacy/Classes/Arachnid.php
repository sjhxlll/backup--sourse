<?php

/*
 * This file is part of Chevereto.
 *
 * (c) Rodolfo Berrios <rodolfo@chevereto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chevereto\Legacy\Classes;

use function Chevere\Message\message;
use Chevere\Throwable\Exceptions\LogicException;
use function Chevereto\Legacy\G\curlResolveCa;
use CURLFile;

class Arachnid
{
    private array $scan = [];

    private string $errorMessage = '';

    private int $errorCode = 0;

    public function __construct(string $authorization, private string $filePath)
    {
        $url = "https://api.arachnid.c3p.ca/api/images/scan";
        $this->errorMessage = '';
        $this->errorCode = 0;
        $ch = curl_init();
        curlResolveCa($ch);
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'image' => new CURLFile($this->filePath)
            ],
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: $authorization"
            ],
        ]);
        $curl_response = curl_exec($ch);
        if (curl_errno($ch) !== 0) {
            $error_msg = curl_error($ch);
        }
        if ($curl_response === false) {
            $this->errorMessage = $error_msg ?? '';
            $this->errorCode = 100;
        } else {
            $json = json_decode($curl_response, true);
            if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                $this->errorMessage = 'Malformed Arachnid response';
                $this->errorCode = 200;
            } else {
                $this->scan = $json;
            }
        }
        curl_close($ch);
    }

    public function scan(): array
    {
        return $this->scan;
    }

    public function isSuccess(): bool
    {
        return $this->errorCode === 0;
    }

    public function errorCode(): int
    {
        return $this->errorCode;
    }

    public function errorMessage(): string
    {
        return $this->errorMessage;
    }

    public function assertIsAllowed(): void
    {
        if ($this->scan !== []) {
            throw new LogicException(
                message(_s('CSAM content is forbidden', '')),
                403
            );
        }
    }
}
