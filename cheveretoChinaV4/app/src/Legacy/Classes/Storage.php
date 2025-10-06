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

use Aws\S3\S3Client;
use Chevere\Throwable\Exceptions\LogicException;
use function Chevereto\Encryption\decryptValues;
use function Chevereto\Encryption\encryptValues;
use function Chevereto\Encryption\hasEncryption;
use function Chevereto\Legacy\G\add_ending_slash;
use function Chevereto\Legacy\G\array_filter_array;
use function Chevereto\Legacy\G\check_value;
use function Chevereto\Legacy\G\datetimegmt;
use function Chevereto\Legacy\G\format_bytes;
use function Chevereto\Legacy\G\get_basename_without_extension;
use function Chevereto\Legacy\G\get_bytes;
use function Chevereto\Legacy\G\get_file_extension;
use function Chevereto\Legacy\G\get_filename_by_method;
use function Chevereto\Legacy\G\get_mimetype;
use function Chevereto\Legacy\G\is_https;
use function Chevereto\Legacy\G\is_url;
use function Chevereto\Legacy\G\nullify_string;
use function Chevereto\Legacy\G\starts_with;
use function Chevereto\Vars\env;
use Composer\CaBundle\CaBundle;
use Exception;
use Google\Service\Storage\ObjectAccessControl;
use Google\Service\Storage\StorageObject;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use obregonco\B2\Client;
use OpenStack\OpenStack;
use OSS\Core\OssException;
use OSS\OssClient;
use function Safe\json_encode;

class Storage
{
    public const ENCRYPTED_NAMES = [
        'server',
        'service',
        'account_id',
        'account_name',
        'key',
        'secret',
        'bucket',
    ];

    protected static array $apis = [
        8 => [
            'name' => 'Local',
            'type' => 'local',
            'url' => '',
        ],
        1 => [
            'name' => 'Amazon S3',
            'type' => 's3',
            'url' => 'https://s3.amazonaws.com/<bucket>/',
        ],
        9 => [
            'name' => 'S3 compatible',
            'type' => 's3compatible',
            'url' => '',
        ],
        2 => [
            'name' => 'Google Cloud',
            'type' => 'gcloud',
            'url' => 'https://storage.googleapis.com/<bucket>/',
        ],

        3 => [
            'name' => 'Microsoft Azure',
            'type' => 'azure',
            'url' => 'https://<account>.blob.core.windows.net/<container>/',
        ],
        10 => [
            'name' => 'Alibaba Cloud OSS',
            'type' => 'oss',
            'url' => 'https://<bucket>.<endpoint>/',
        ],
        6 => [
            'name' => 'SFTP',
            'type' => 'sftp',
            'url' => '',
        ],
        5 => [
            'name' => 'FTP',
            'type' => 'ftp',
            'url' => '',
        ],
        7 => [
            'name' => 'OpenStack',
            'type' => 'openstack',
            'url' => '',
        ],
        11 => [
            'name' => 'Backblaze B2 (legacy API)',
            'type' => 'b2',
            'url' => 'https://f002.backblazeb2.com/file/<bucket>/',
        ],
    ];

    public static function getSingle(int $var): array
    {
        return self::get(['id' => $var], [], 1);
    }

    public static function getAnon(
        string $type,
        string $name,
        string $url,
        string $bucket,
        ?string $key = null,
        ?string $secret = null,
        ?string $region = null,
        ?string $server = null,
        ?string $service = null,
        ?string $accountId = null,
        ?string $accountName = null
    ): array {
        return [
            'api_id' => self::getApiId($type),
            'name' => $name,
            'url' => rtrim($url, '/') . '/',
            'bucket' => $type == 'local' ? (rtrim($bucket, '/') . '/') : $bucket,
            'region' => $region,
            'server' => $server,
            'service' => $service,
            'account_id' => $accountId,
            'account_name' => $accountName,
            'key' => $key,
            'secret' => $secret,
            'id' => null,
            'is_https' => starts_with('https', $url),
            'is_active' => true,
            'capacity' => null,
            'space_used' => null,
        ];
    }

    public static function get(array $values = [], array $sort = [], int $limit = null): array
    {
        $get = DB::get([
            'table' => 'storages',
            'join' => 'LEFT JOIN ' . DB::getTable('storage_apis') . ' ON '
                . DB::getTable('storages') . '.storage_api_id = '
                . DB::getTable('storage_apis') . '.storage_api_id'
        ], $values, 'AND', $sort, $limit);
        if (isset($get[0]) && is_array($get[0])) {
            foreach ($get as $k => $v) {
                self::formatRowValues($get[$k], $v);
            }
        } elseif (!empty($get)) {
            self::formatRowValues($get);
        }

        return is_array($get) ? $get : [];
    }

    protected static function requiredByApi(int $api_id): array
    {
        $required = ['api_id', 'bucket'];
        $type = self::getApiType($api_id);
        if ($type != 'local') {
            $required[] = 'secret';
            if ($type != 'gcloud') {
                $required[] = 'key';
            }
        }

        return $required;
    }

    public static function uploadFiles(
        array|string $targets,
        array|int $storage,
        array $options = []
    ): array {
        $pathPrefix = $options['keyprefix'] ?? ''; // trailing slash
        if (!is_array($storage)) {
            $storage = self::getSingle($storage);
        } else {
            foreach (self::requiredByApi((int) $storage['api_id']) as $k) {
                if (!isset($storage[$k])) {
                    throw new Exception('Missing ' . $k . ' value', 600);
                }
            }
        }
        if (!isset($storage['api_type'])) {
            $storage['api_type'] = self::getApiType((int) $storage['api_id']);
        }
        $API = self::requireAPI($storage);
        $files = [];
        if (!empty($targets['file'])) {
            $files[] = $targets;
        } elseif (!is_array($targets)) {
            $files = ['file' => $targets, 'filename' => basename($targets)];
        } else {
            $files = $targets;
        }
        $disk_space_used = 0;
        $cache_control = 'public, max-age=31536000';
        $urn = '';
        foreach ($files as $k => $v) {
            $source_file = $v['file'];
            if (in_array($storage['api_type'], ['s3', 's3compatible', 'b2', 'azure', 'oss'])) {
                switch ($storage['api_type']) {
                    case 'oss':
                        $source_file = file_get_contents($v['file']);

                    break;
                    default:
                        $source_file = @fopen($v['file'], 'r');

                    break;
                }
                if ($source_file === false) {
                    throw new Exception('Failed to open file stream', 600);
                }
                $urn = $pathPrefix . $v['filename'];
            }
            switch ($storage['api_type']) {
                case 's3':
                case 's3compatible':
                    $API->putObject([
                        'Bucket' => $storage['bucket'],
                        'Key' => $urn,
                        'Body' => $source_file,
                        'ACL' => 'public-read',
                        'CacheControl' => $cache_control,
                        'ContentType' => $v['mime'],
                    ]);

                break;

                case 'azure':
                    $blobOptions = new CreateBlockBlobOptions();
                    $blobOptions->setContentType($v['mime']);
                    $API->createBlockBlob($storage['bucket'], $urn, $source_file, $blobOptions);

                break;

                case 'b2':
                    $API->upload([
                        'BucketName' => $storage['bucket'],
                        'FileName' => $urn,
                        'Body' => $source_file,
                    ]);

                break;

                case 'oss':
                    $API->putObject($storage['bucket'], $urn, $source_file);

                break;

                case 'gcloud':
                    // https://github.com/xown/gaufrette-gcloud/blob/master/src/Gaufrette/Adapter/GCloudStorage.php
                    $source_file = @file_get_contents($v['file']);
                    if (!$source_file) {
                        throw new Exception('Failed to open file stream', 600);
                    }
                    $gc_obj = new StorageObject();
                    $gc_obj->setName($pathPrefix . $v['filename']);
                    $gc_obj->setAcl('public-read');
                    $gc_obj->setCacheControl($cache_control);
                    $API->objects->insert($storage['bucket'], $gc_obj, [
                        'mimeType' => get_mimetype($v['file']),
                        'uploadType' => 'multipart',
                        'data' => $source_file,
                    ]);
                    $gc_obj_acl = new ObjectAccessControl();
                    $gc_obj_acl->setEntity('allUsers');
                    $gc_obj_acl->setRole('READER');
                    $API->objectAccessControls->insert($storage['bucket'], $gc_obj->name, $gc_obj_acl);

                break;

                case 'ftp':
                case 'sftp':
                case 'local':
                    $target_path = ($API instanceof LocalStorage ? $API->realPath() : $storage['bucket']) . $pathPrefix;
                    if ($pathPrefix !== '') {
                        $API->mkdirRecursive($pathPrefix);
                    }
                    $API->put([
                        'filename' => $v['filename'],
                        'source_file' => $source_file,
                        'path' => $target_path,
                    ]);
                    if (!$API instanceof LocalStorage) {
                        $API->chdir($storage['bucket']);
                    }

                break;

                case 'openstack':
                    $source_file = @fopen($v['file'], 'r');
                    if ($source_file === false) {
                        throw new Exception('Failed to open file stream', 600);
                    }
                    /** @var \OpenStack\ObjectStore\v1\Service $API */
                    $container = $API->getContainer($storage['bucket']);
                    $container->createObject(
                        [
                            'name' => $pathPrefix . $v['filename'],
                            'content' => $source_file,
                            'Cache-Control' => $cache_control
                        ]
                    );

                break;
            }

            $filesize = @filesize($v['file']);
            if ($filesize === false) {
                throw new Exception("Can't get filesize for " . $v['file'], 601);
            } else {
                $disk_space_used += $filesize;
            }

            $files[$k]['stored_file'] = $storage['url'] . $pathPrefix . $v['filename'];
        }
        if (in_array($storage['api_type'], ['ftp', 'sftp']) && is_object($API)) {
            $API->close();
        }
        if (isset($storage['id'])) {
            DB::update('settings', ['value' => $storage['id']], ['name' => 'last_used_storage']);
            DB::increment('storages', ['space_used' => '+' . $disk_space_used], ['id' => $storage['id']]);
        }

        return $files;
    }

    /**
     * Delete files from the external storage (using queues for non anon Storages).
     *
     * @param string|array $targets (key, single array key, multiple array key)
     * @param int|array $storage (storage id, storage array)
     */
    public static function deleteFiles(string|array $targets, int|array $storage): array|bool
    {
        if (!is_array($storage)) {
            $storage = Storage::getSingle($storage);
        } else {
            foreach (self::requiredByApi((int) $storage['api_id']) as $k) {
                if (!isset($storage[$k])) {
                    throw new Exception('Missing ' . $k . ' value', 600);
                }
            }
        }
        /** @var array $storage */
        $files = [];
        if (!empty($targets['key'])) {
            $files[] = $targets;
        } elseif (!is_array($targets)) {
            $files = [['key' => $targets]];
        } else {
            $files = $targets;
        }
        $storage_keys = [];
        foreach ($files as $k => $v) {
            $files[$v['key']] = $v;
            $storage_keys[] = $v['key'];
            unset($files[$k]);
        }
        $deleted = [];
        if (isset($storage['id'])) {
            $storage_keysCount = count($storage_keys);
            // Storage already exist
            for ($i = 0; $i < $storage_keysCount; ++$i) {
                $queue_args = [
                    'key' => $storage_keys[$i],
                    'size' => $files[$storage_keys[$i]]['size'],
                ];
                Queue::insert([
                    'type' => 'storage-delete',
                    'args' => json_encode($queue_args),
                    'join' => $storage['id']
                ]);
                $deleted[] = $storage_keys[$i];
            }
        } else {
            foreach ($storage_keys as $key) {
                self::deleteObject($key, $storage);
                $deleted[] = $key;
            }
        }

        return $deleted !== [] ? $deleted : false;
    }

    /**
     * Delete a single file from the external storage.
     *
     * @param string $key representation of the object (file) to delete relative to the bucket
     */
    public static function deleteObject(string $key, array $storage): void
    {
        $API = self::requireAPI($storage);
        switch (self::getApiType((int) $storage['api_id'])) {
            case 's3':
            case 's3compatible':
                /** @var S3Client $API */
                $API->deleteObject([
                    'Bucket' => $storage['bucket'],
                    'Key' => $key,
                ]);

            break;
            case 'b2':
                /** @var Client $API */
                try {
                    $API->deleteFile([
                        'BucketName' => $storage['bucket'],
                        'FileName' => $key,
                    ]);
                } catch (\obregonco\B2\Exceptions\NotFoundException $e) {
                    // File not found
                }

            break;
            case 'azure':
                /** @var BlobRestProxy $API */
                $API->deleteBlob($storage['bucket'], $key);

            break;
            case 'oss':
                /** @var OssClient $API */
                $API->deleteObject($storage['bucket'], $key);

            break;
            case 'gcloud':
                /** @var \Google\Service\Storage $API */
                $API->objects->delete($storage['bucket'], $key);

            break;
            case 'ftp':
            case 'sftp':
            case 'local':
                $API->delete($key);

            break;
            case 'openstack':
                /** @var \OpenStack\ObjectStore\v1\Service $API */
                $container = $API->getContainer($storage['bucket']);
                $container
                    ->getObject($key)
                    ->delete();

            break;
        }
    }

    public static function test(array|int $storage): void
    {
        $datetime = preg_replace('/(.*)_(\d{2}):(\d{2}):(\d{2})/', '$1_$2h$3m$4s', datetimegmt('Y-m-d_h:i:s'));
        $filename = 'Chevereto_test_' . $datetime . '.png';
        $file = PATH_PUBLIC_CONTENT_LEGACY_SYSTEM . 'favicon.png';
        self::uploadFiles([
            'file' => $file,
            'filename' => $filename,
            'mime' => 'image/png',
        ], $storage);
        self::deleteFiles(['key' => $filename, 'size' => filesize($file)], $storage);
    }

    public static function insert(array $values): int
    {
        if ($values === []) {
            throw new Exception('Empty values provided', 600);
        }
        $required = ['name', 'api_id', 'key', 'secret', 'bucket', 'url']; // Global
        $required_by_api = [
            's3' => ['region'],
            's3compatible' => ['region', 'server'],
            'oss' => ['server'],
            'ftp' => ['server'],
            'sftp' => ['server'],
        ];
        $storage_api = self::getApiType((int) $values['api_id']);
        if ($storage_api === 'local' && !(bool) env()['CHEVERETO_ENABLE_LOCAL_STORAGE']) {
            throw new Exception('Local storage API is forbidden', 403);
        }
        if ($storage_api == 'local') {
            unset($required[2], $required[3]); //  key, secret
        }
        if ($storage_api == 'gcloud') {
            $values['secret'] = trim($values['secret']);
            unset($required[2]); // key
        }
        if (isset($values['api_id']) && array_key_exists(self::getApiType((int) $values['api_id']), $required_by_api)) {
            foreach ($required_by_api[$storage_api] as $k => $v) {
                $required[] = $v;
            }
        }
        foreach ($required as $v) {
            if (!check_value($values[$v])) {
                throw new Exception("Missing $v value", 101);
            }
        }
        $validations = [
            'api_id' => [
                'validate' => is_numeric($values['api_id']),
                'message' => 'Expecting integer value for api_id, ' . gettype($values['api_id']) . ' given',
                'code' => 602,
            ],
            'url' => [
                'validate' => is_url($values['url']),
                'message' => 'Invalid storage URL given',
                'code' => 103,
            ],
        ];
        foreach ($validations as $k => $v) {
            if (!$v['validate']) {
                throw new Exception($v['message'], $v['code']);
            }
        }
        $values['url'] = add_ending_slash($values['url']);
        self::formatValues($values);
        self::test($values);
        if (hasEncryption()) {
            $values = encryptValues(self::ENCRYPTED_NAMES, $values);
        }

        return DB::insert('storages', $values);
    }

    public static function update(int $id, array $values, bool $checkCredentials = true): int
    {
        $storage = self::getSingle($id);
        if ($storage === []) {
            throw new Exception("Storage ID:$id doesn't exists", 100);
        }
        if (isset($values['url'])) {
            if (!is_url($values['url'])) {
                if (!$storage['url']) {
                    throw new Exception('Missing storage URL', 100);
                } else {
                    unset($values['url']);
                }
            } else {
                $values['url'] = add_ending_slash($values['url']);
            }
        }
        self::formatValues($values, 'null');
        if (isset($values['capacity']) && !empty($values['capacity']) && $values['capacity'] < $storage['space_used']) {
            throw new Exception(_s("Storage capacity can't be lower than its current usage (%s).", format_bytes($storage['space_used'])), 101);
        }
        $new_values = array_merge($storage, $values);
        if ($checkCredentials) {
            $isTestCredentials = intval($values['is_active'] ?? 0) == 1;
            if (!$isTestCredentials) {
                foreach (['key', 'secret', 'bucket', 'region', 'server', 'account_id', 'account_name'] as $v) {
                    if (isset($values[$v]) && $values[$v] !== $storage[$v]) {
                        $isTestCredentials = true;

                        break;
                    }
                }
            }
            if ($isTestCredentials) {
                self::test($new_values);
            }
        }
        if (hasEncryption()) {
            $values = encryptValues(self::ENCRYPTED_NAMES, $values);
        }

        return DB::update('storages', $values, ['id' => $id]);
    }

    public static function requireAPI(array $storage): object
    {
        $api_type = self::getApiType((int) $storage['api_id']);
        switch ($api_type) {
            case 's3':
            case 's3compatible':
                $factoria = [
                    'version' => '2006-03-01',
                    'region' => $storage['region'],
                    'command.params' => ['PathStyle' => true],
                    'credentials' => [
                        'key' => $storage['key'],
                        'secret' => $storage['secret'],
                    ],
                    'http' => [
                        'verify' => CaBundle::getBundledCaBundlePath(),
                    ],
                ];
                if ($api_type == 's3compatible') {
                    $factoria['endpoint'] = $storage['server'];
                }

                return new S3Client($factoria);

            case 'azure':
                $connectionString = 'DefaultEndpointsProtocol=https;AccountName=' . $storage['key'] . ';AccountKey=' . $storage['secret'];
                if ($storage['server']) {
                    $connectionString .= ';BlobEndpoint=' . $storage['server'];
                }

                try {
                    return BlobRestProxy::createBlobService($connectionString);
                } catch (ServiceException $e) {
                    throw new Exception('Azure storage client connect error: ' . $e->getMessage(), 0, $e);
                }

            case 'b2':

                try {
                    // key: account id
                    // secret: master application key
                    return new Client($storage['key'], ['applicationKey' => $storage['secret']]);
                } catch (Exception $e) {
                    throw new Exception('Backblaze B2 storage client connect error: ' . $e->getMessage(), 0, $e);
                }

            case 'oss':
                try {
                    return new OssClient($storage['key'], $storage['secret'], $storage['server']);
                } catch (OssException $e) {
                    throw new Exception('Alibaba storage client connect error: ' . $e->getMessage(), 0, $e);
                }

            case 'gcloud':
                $client = new \Google\Client();
                $client->setApplicationName('Chevereto Google Cloud Storage');
                $client->addScope('https://www.googleapis.com/auth/devstorage.full_control');
                $credentials = json_decode(trim($storage['secret']), true);
                $client->setAuthConfig($credentials);
                $client->fetchAccessTokenWithAssertion();
                if (!$client->getAccessToken()) {
                    throw new Exception('No access token');
                }

                return new \Google\Service\Storage($client);

            case 'ftp':
            case 'sftp':
                $class = 'Chevereto\Legacy\Classes\\' . ucfirst($api_type);

                return new $class([
                    'server' => $storage['server'],
                    'user' => $storage['key'],
                    'password' => $storage['secret'],
                    'path' => $storage['bucket'],
                ]);

            case 'openstack':
                $credentials = [
                    'authUrl' => $storage['server'],
                    'region' => $storage['region'] ?? null,
                    'username' => $storage['key'],
                    'password' => $storage['secret'],
                ];
                $credentials['tenantId'] = $storage['account_id'] ?? null;
                $credentials['tenantName'] = $storage['account_name'] ?? null;

                return (new OpenStack($credentials))->objectStoreV1();

            case 'local':
                return new LocalStorage($storage);
        }

        throw new LogicException();
    }

    public static function getAPIRegions(string $api): array
    {
        $regions = [
            's3' => [
                'us-east-1' => 'US East (N. Virginia)',
                'us-east-2' => 'US East (Ohio)',
                'us-west-1' => 'US West (N. California)',
                'us-west-2' => 'US West (Oregon)',

                'ca-central-1' => 'Canada (Central)',

                'ap-south-1' => 'Asia Pacific (Mumbai)',
                'ap-northeast-2' => 'Asia Pacific (Seoul)',
                'ap-southeast-1' => 'Asia Pacific (Singapore)',
                'ap-southeast-2' => 'Asia Pacific (Sydney)',
                'ap-northeast-1' => 'Asia Pacific (Tokyo)',

                'eu-central-1' => 'EU (Frankfurt)',
                'eu-west-1' => 'EU (Ireland)',
                'eu-west-2' => 'EU (London)',
                'eu-west-3' => 'EU (Paris)',

                'sa-east-1' => 'South America (Sao Paulo)',
            ],
        ];
        foreach ($regions['s3'] as $k => &$v) {
            $s3_subdomain = 's3' . ($k !== 'us-east-1' ? ('-' . $k) : null);
            $v = [
                'name' => $v,
                'url' => 'https://' . $s3_subdomain . '.amazonaws.com/',
            ];
        }

        return $regions[$api];
    }

    public static function getApiType(int $api_id): string
    {
        return self::$apis[$api_id]['type'];
    }

    public static function getStorageValidFilename(
        string $filename,
        int $storage_id,
        string $filenaming,
        string $destination
    ): string {
        if ($filenaming == 'id') {
            return $filename;
        }
        $extension = get_file_extension($filename);
        $wanted_names = [];
        for ($i = 0; $i < 25; ++$i) {
            if ($i > 0 && $i < 5) {
                $filenaming = $filenaming == 'random' ? 'random' : 'mixed';
            } elseif ($i > 15) {
                $filenaming = 'random';
            }
            $filename_by_method = get_filename_by_method($filenaming, $filename);
            $wanted_names[] = get_basename_without_extension($filename_by_method);
        }
        $taken_names = [];
        if ($storage_id !== 0) {
            $stock_qry = 'SELECT DISTINCT image_name, image_id FROM ' . DB::getTable('images') . ' WHERE image_storage_id=:image_storage_id AND image_extension=:image_extension AND image_name IN(' . '"' . implode('","', $wanted_names) . '"' . ') ';
            $stock_binds = [
                'storage_id' => $storage_id,
                'extension' => $extension,
            ];
            $datefolder = rtrim(preg_replace('#' . CHV_PATH_IMAGES . '#', '', $destination, 1), '/');
            if (preg_match('#\d{4}\/\d{2}\/\d{2}#', $datefolder)) {
                $datefolder = str_replace('/', '-', $datefolder);
                $stock_qry .= 'AND DATE(image_date)=:image_date ';
                $stock_binds['date'] = $datefolder;
            }
            $stock_qry .= 'ORDER BY image_id DESC;';

            try {
                $db = DB::getInstance();
                $db->query($stock_qry);
                foreach ($stock_binds as $k => $v) {
                    $db->bind(':image_' . $k, $v);
                }
                $images_stock = $db->fetchAll();
                foreach ($images_stock as $k => $v) {
                    $taken_names[] = $v['image_name'];
                }
            } catch (Exception $e) {
            }
        }
        if ($taken_names !== []) {
            foreach ($wanted_names as $candidate) {
                if (in_array($candidate, $taken_names)) {
                    continue;
                }
                $return = $candidate;

                break;
            }
        } else {
            $return = $wanted_names[0];
        }

        return isset($return) ? ($return . '.' . $extension) : self::getStorageValidFilename($filename, $storage_id, $filenaming, $destination);
    }

    public static function getEnabledApis(): array
    {
        $apis = self::$apis;
        if (!(bool) env()['CHEVERETO_ENABLE_LOCAL_STORAGE']) {
            unset($apis[8]);
        }

        return $apis;
    }

    public static function getApiId(string $type): int
    {
        foreach (self::$apis as $id => $api) {
            if ($api['type'] === $type) {
                return $id;
            }
        }

        return 0;
    }

    protected static function formatValues(array &$values, string $junk = 'keep'): void
    {
        if (isset($values['capacity'])) {
            nullify_string($values['capacity']);
            if (!is_null($values['capacity'])) {
                $values['capacity'] = get_bytes($values['capacity']);
                if (!is_numeric($values['capacity'])) {
                    throw new Exception('Invalid storage capacity value. Make sure to use a valid format.', 100);
                }
            }
        }
        if (isset($values['is_https'])) {
            $protocol_stock = ['http', 'https'];
            if ($values['is_https'] != 1) {
                $protocol_stock = array_reverse($protocol_stock);
            }
            $values['url'] = preg_replace('#^https?://#', '', $values['url'], 1);
            $values['url'] = $protocol_stock[1] . '://' . $values['url'];
        } elseif (isset($values['url'])) {
            $values['is_https'] = (int) is_https($values['url']);
        }

        if (isset($values['api_id']) && in_array(self::getApiType((int) $values['api_id']), ['ftp', 'sftp']) && isset($values['bucket'])) {
            $values['bucket'] = add_ending_slash($values['bucket']);
        }

        if (in_array($junk, ['null', 'remove']) && isset($values['api_id'])) {
            $junk_values_by_api = [
                1 => ['server'],
                5 => ['region'],
            ];
            if (isset($junk_values_by_api[$values['api_id']])) {
                switch ($junk) {
                    case 'null':
                        foreach ($junk_values_by_api[$values['api_id']] as $k => $v) {
                            $values[$v] = null;
                        }

                    break;
                    case 'remove':
                        $values = array_filter_array($values, $junk_values_by_api[$values['api_id']], 'rest');

                    break;
                }
            }
        }
    }

    protected static function formatRowValues(array &$values, array $row = []): void
    {
        $values = DB::formatRow($row !== [] ? $row : $values);
        $values['url'] = is_url($values['url'])
            ? add_ending_slash($values['url'])
            : null;
        $values['usage_label'] = (
            $values['capacity'] == 0 ? _s('Unlimited') : format_bytes($values['capacity'], 2)
        ) . ' / ' . format_bytes(
            $values['space_used'],
            2
        ) . ' ' . _s('used');
        if (hasEncryption()) {
            $values = decryptValues(self::ENCRYPTED_NAMES, $values);
        }
    }

    public static function regenStorageStats(int $storageId): string
    {
        $storage = Storage::getSingle($storageId);
        if ($storage === []) {
            throw new Exception(sprintf("Error: Storage id %s doesn't exists", $storageId), 100);
        }
        $query = 'UPDATE ' . DB::getTable('storages') . ' SET storage_space_used = (SELECT IFNULL(SUM(image_size) + SUM(image_thumb_size) + SUM(image_medium_size),0) FROM ' . DB::getTable('images') . ' WHERE image_storage_id = :storageId) WHERE storage_id = :storageId';
        $db = DB::getInstance();
        $db->query($query);
        if ($storageId != 0) {
            $db->bind(':storageId', $storageId);
        }
        $db->exec();

        return sprintf('Storage %s stats re-generated', $storageId != 0 ? ('"' . $storage['name'] . '" (' . $storage['id'] . ')') : 'local');
    }

    public static function migrateStorage(int $sourceStorageId, int $targetStorageId): string
    {
        if ($sourceStorageId === $targetStorageId) {
            throw new Exception(sprintf('You have to provide two different storage ids (same id %s provided)', $sourceStorageId), 100);
        }
        $sourceStorage = $sourceStorageId == 0 ? 'local' : Storage::getSingle($sourceStorageId);
        $targetStorage = $targetStorageId == 0 ? 'local' : Storage::getSingle($targetStorageId);
        $error_message = ["Storage id %s doesn't exists", "Storage ids %s doesn't exists"];
        $error = [];
        foreach (['source', 'target'] as $v) {
            $object = $v;
            $prop = $v . 'Storage';
            $id = $prop . 'Id';
            if ($$prop == false) {
                $error[] = $$id;
            } elseif (is_array($$prop) == false) {
                $$prop = ['name' => $$prop, 'type' => $$prop, 'api_type' => $$prop];
            }
        }
        if ($error !== []) {
            throw new Exception(str_replace('%s', implode(', ', $error), $error_message[count($error) - 1]));
        }
        $db = DB::getInstance();
        $query = 'UPDATE ' . DB::getTable('images') . ' SET image_storage_id = :targetStorageId WHERE ';
        // local (null) -> external
        if ($sourceStorageId == 0) {
            $query .= 'ISNULL(image_storage_id)';
        // external -> external
        } else {
            $query .= 'image_storage_id = :sourceStorageId';
        }
        $db->query($query);
        if ($sourceStorageId != 0) {
            $db->bind(':sourceStorageId', $sourceStorageId);
        }
        $db->bind(':targetStorageId', $targetStorageId == 0 ? null : $targetStorageId);
        $db->exec();
        $rowCount = $db->rowCount();
        if ($rowCount > 0) {
            $return = [];
            if ($sourceStorageId != 0) {
                $return[] = static::regenStorageStats($sourceStorageId);
            }
            if ($targetStorageId != 0) {
                $return[] = static::regenStorageStats($targetStorageId);
            }
            array_unshift($return, strtr('OK: %s image(s) migrated from "%source" to "%target"', [
                '%s' => $rowCount,
                '%source' => $sourceStorage['name'],
                '%target' => $targetStorage['name'],
            ]));

            return implode(' - ', $return);
        } else {
            throw new Exception('No content to migrate', 404);
        }
    }
}
