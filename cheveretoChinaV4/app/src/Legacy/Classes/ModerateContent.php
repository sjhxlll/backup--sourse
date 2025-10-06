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

use function Chevereto\Legacy\G\curlResolveCa;
use function Chevereto\Legacy\G\get_image_fileinfo;
use CURLFile;
use Exception;

class ModerateContent
{
    private string $imageFilename;

    private array $imageInfo;

    private $moderation;

    private string $error_message = '';

    private int $error_code = 0;

    private string $imageOptimized;

    public function __construct(string $imageFilename, array $info = [])
    {
        $this->imageFilename = $imageFilename;
        $this->imageInfo = $info;
        if ($this->imageInfo === []) {
            $this->imageInfo = get_image_fileinfo($this->imageFilename);
        }
        $this->optimizeImage();
        $url = "http://api.moderatecontent.com/moderate/?key=" . Settings::get('moderatecontent_key');
        $this->error_message = '';
        $this->error_code = 0;
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
            CURLOPT_POSTFIELDS => ['file' => new CURLFile($this->imageOptimized)],
        ]);
        $curl_response = curl_exec($ch);
        if (curl_errno($ch) !== 0) {
            $error_msg = curl_error($ch);
        }
        if ($curl_response === false) {
            $this->error_message = $error_msg ?? '';
            $this->error_code = 1402;
        } else {
            $json = json_decode($curl_response);
            if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                $this->error_message = 'Malformed content moderation response';
                $this->error_code = 1403;
            } else {
                $this->moderation = $json;
                $this->assertIsAllowed();
            }
        }
        curl_close($ch);
    }

    public function moderation(): object
    {
        return $this->moderation;
    }

    public function isSuccess(): bool
    {
        return $this->error_code === 0;
    }

    public function isError(): bool
    {
        return $this->error_code !== 0;
    }

    public function errorCode(): int
    {
        return $this->error_code;
    }

    public function errorMessage(): string
    {
        return $this->error_message;
    }

    private function assertIsAllowed(): void
    {
        $block = [];
        $blockRating = Settings::get('moderatecontent_block_rating');
        switch ($blockRating) {
            case 'a':
                $block[] = 'a';

            break;
            case 't':
                $block[] = 'a';
                $block[] = 't';

            break;
        }
        $ratings = [
            'a' => _s('adult'),
            't' => _s('teen'),
        ];
        foreach ($block as $rating) {
            if ($this->moderation->rating_letter == $rating) {
                throw new Exception(_s('Content of type %s is forbidden', $ratings[$rating]), 403);
            }
        }
    }

    private function optimizeImage(): void
    {
        $this->imageOptimized = $this->imageFilename;
        // if ($this->imageInfo['size'] > G\get_bytes('1 MB') && !G\is_animated_image($this->imageFilename)) {
        //     $this->imageOptimized = Upload::getTempNam(sys_get_temp_dir());
        //     if (copy($this->imageFilename, $this->imageOptimized)) {
        //         $option = $this->imageInfo['ratio'] >= 1 ? 'width' : 'height';
        //         Image::resize($this->imageOptimized, null, null, [$option => 300]);
        //     }
        // }
    }
}
