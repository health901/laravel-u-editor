<?php

namespace VRobin\UEditor\Uploader;
/**
 *
 *
 * Class UploadFile
 *
 * 文件/图像普通上传
 *
 * @package VRobin\UEditor\Uploader
 */
class UploadFile extends Upload
{


    public function prepare()
    {
        return true;
    }
}
