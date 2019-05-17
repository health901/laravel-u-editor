<?php


namespace VRobin\UEditor\Uploader;


use Illuminate\Support\Facades\Storage;

class FileSystem
{
    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    public $storage;

    public function __construct()
    {
        $disk = config("UEditorUpload.core.disk");
        $this->disk($disk);
    }

    /**
     * Set disk for storage.
     *
     * @param string $disk Disks defined in `config/filesystems.php`.
     *
     * @return $this
     * @throws \Exception
     *
     */
    public function disk($disk)
    {
        try {
            $this->storage = Storage::disk($disk);
        } catch (\Exception $exception) {
            if (!array_key_exists($disk, config('filesystems.disks'))) {
                throw new \Exception("Disk [$disk] not configured, please add a disk config in `config/filesystems.php`.");
            }
            throw $exception;
        }

        return $this;
    }
}
