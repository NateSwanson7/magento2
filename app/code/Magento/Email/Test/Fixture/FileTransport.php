<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Email\Test\Fixture;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class FileTransport implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'directory' => DirectoryList::TMP,
        'path' => 'mail/%uniqid%',
    ];

    private const CONFIG_FILE = 'mail-transport-config.json';

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $dataProcessor;

    /**
     * @var DataObjectFactory
     */
    private DataObjectFactory $dataObjectFactory;

    /**
     * @param Filesystem $filesystem
     * @param Json $json
     * @param ProcessorInterface $dataProcessor
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        Filesystem $filesystem,
        Json $json,
        ProcessorInterface $dataProcessor,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->filesystem = $filesystem;
        $this->json = $json;
        $this->dataProcessor = $dataProcessor;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters
     * <pre>
     *    $data = [
     *      'directory' => (string) Filesystem directory code. Optional. Default: tmp dir
     *      'path'      => (string) Relative path to "directory" where to save mails. Optional. Default: autogenerated
     *    ]
     * </pre>
     */
    public function apply(array $data = []): ?DataObject
    {
        $data = $this->dataProcessor->process($this, array_merge(self::DEFAULT_DATA, $data));
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $directory->writeFile(self::CONFIG_FILE, $this->json->serialize($data));

        return $this->dataObjectFactory->create(['data' => $data]);
    }

    /**
     * @inheritDoc
     */
    public function revert(DataObject $data): void
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $config = $this->json->unserialize($directory->readFile(self::CONFIG_FILE));
        $directory->delete(self::CONFIG_FILE);
        $directory = $this->filesystem->getDirectoryWrite($config['directory']);
        $directory->delete($config['path']);
    }
}
