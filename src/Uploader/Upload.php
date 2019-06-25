<?php

namespace VRobin\UEditor\Uploader;


/**
 * Abstract Class Upload
 * 文件上传抽象类
 *
 *
 * @package VRobin\UEditor\Uploader
 */
abstract class Upload
{
    protected $fileField; //文件域名
    protected $file; //文件上传对象
    protected $base64; //文件上传对象
    protected $config; //配置信息
    protected $oriName; //原始文件名
    protected $fileName; //新文件名
    protected $fullName; //完整文件名,即从当前配置目录开始的URL
    protected $fileUrl;
    protected $fileSize; //文件大小
    protected $fileType; //文件类型
    protected $stateInfo; //上传状态信息,
    protected $stateMap; //上传状态映射表，国际化用户需考虑此处数据的国际化

    abstract function prepare();     //准备工作

    public function __construct(array $config, $request)
    {
        $this->config = $config;
        $this->request = $request;
        $this->fileField = $this->config['fieldName'];
        if (isset($config['allowFiles'])) {
            $this->allowFiles = $config['allowFiles'];
        } else {
            $this->allowFiles = [];
        }

        $stateMap = [
            "SUCCESS", //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
            trans("UEditor::upload.upload_max_filesize"),
            trans("UEditor::upload.upload_error"),
            trans("UEditor::upload.no_file_uploaded"),
            trans("UEditor::upload.upload_file_empty"),
            "ERROR_TMP_FILE" => trans("UEditor::upload.ERROR_TMP_FILE"),
            "ERROR_TMP_FILE_NOT_FOUND" => trans("UEditor::upload.ERROR_TMP_FILE_NOT_FOUND"),
            "ERROR_SIZE_EXCEED" => trans("UEditor::upload.ERROR_SIZE_EXCEED"),
            "ERROR_TYPE_NOT_ALLOWED" => trans("UEditor::upload.ERROR_TYPE_NOT_ALLOWED"),
            "ERROR_CREATE_DIR" => trans("UEditor::upload.ERROR_CREATE_DIR"),
            "ERROR_DIR_NOT_WRITEABLE" => trans("UEditor::upload.ERROR_DIR_NOT_WRITEABL"),
            "ERROR_FILE_MOVE" => trans("UEditor::upload.ERROR_FILE_MOVE"),
            "ERROR_FILE_NOT_FOUND" => trans("UEditor::upload.ERROR_FILE_NOT_FOUND"),
            "ERROR_WRITE_CONTENT" => trans("UEditor::upload.ERROR_WRITE_CONTENT"),
            "ERROR_UNKNOWN" => trans("UEditor::upload.ERROR_UNKNOWN"),
            "ERROR_DEAD_LINK" => trans("UEditor::upload.ERROR_DEAD_LINK"),
            "ERROR_HTTP_LINK" => trans("UEditor::upload.ERROR_HTTP_LINK"),
            "ERROR_HTTP_CONTENTTYPE" => trans("UEditor::upload.ERROR_HTTP_CONTENTTYPE"),
            "ERROR_UNKNOWN_MODE" => trans("UEditor::upload.ERROR_UNKNOWN_MODE"),
        ];
        $this->stateMap = $stateMap;

    }

    /**
     * 上传操作
     */
    public function doUpload()
    {
        $file = $this->request->file($this->fileField);
        if (empty($file)) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return false;
        }
        if (!$file->isValid()) {
            $this->stateInfo = $this->getStateInfo($file->getError());
            return false;

        }
        $this->file = $file;
        $this->oriName = $this->file->getClientOriginalName();
        $this->fileSize = $this->file->getSize();
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->fileName = basename($this->fullName);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return false;
        }
        //检查是否不允许的文件格式
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return false;
        }
        return $this->saveFile(file_get_contents($this->file->getRealPath()));
    }

    protected function saveFile($file_stream)
    {
        $fs = new FileSystem();
        try {
            $r = $fs->storage->put($this->fullName, $file_stream);
            if ($r) {
                $this->fileUrl = $fs->storage->url(trim($this->fullName,'/'));
            }
            $this->stateInfo = $this->stateMap[0];

        } catch (\Exception $exception) {
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
            return false;
        }
    }

    /**
     *
     *
     *
     * @return array
     */

    public function upload()
    {
        if ($this->prepare()) {
            $this->doUpload();
        }
        return $this->getFileInfo();
    }


    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    protected function getStateInfo($errCode)
    {
        return !$this->stateMap[$errCode] ? $this->stateMap["ERROR_UNKNOWN"] : $this->stateMap[$errCode];
    }

    /**
     * 文件大小检测
     * @return bool
     */
    protected function checkSize()
    {
        return $this->fileSize <= ($this->config["maxSize"]);
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    protected function getFileExt()
    {
        return '.' . $this->file->guessExtension();
    }

    /**
     * 重命名文件
     * @return string
     */
    protected function getFullName()
    {
        //替换日期事件
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $this->config["pathFormat"];
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $format = str_replace("{hh}", $d[4], $format);
        $format = str_replace("{ii}", $d[5], $format);
        $format = str_replace("{ss}", $d[6], $format);
        $format = str_replace("{time}", $t, $format);

        //过滤文件名的非法自负,并替换文件名
        $oriName = substr($this->oriName, 0, strrpos($this->oriName, '.'));
        $oriName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
        $format = str_replace("{filename}", $oriName, $format);

        //替换随机字符串  数值太大可能导致部分环境报错
        $randNum = rand(1, getrandmax()) . rand(1, getrandmax());
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
        }

        $ext = $this->getFileExt();
        return $format . $ext;
    }

    /**
     * 文件类型检测
     * @return bool
     */
    protected function checkType()
    {

        return in_array($this->getFileExt(), $this->config["allowFiles"]);
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo()
    {
        return array(
            "state" => $this->stateInfo,
            "url" => $this->fileUrl,
            "title" => $this->fileName,
            "original" => $this->oriName,
            "type" => $this->fileType,
            "size" => $this->fileSize
        );
    }

}
