<?php

namespace VRobin\UEditor\Uploader;

/**
 * Class UploadCatch
 * 图片远程抓取
 *
 * @package VRobin\UEditor\Uploader
 */
class UploadCatch extends Upload
{

    public function prepare()
    {
        return true;
    }

    public function doUpload()
    {
        $imgUrl = str_replace("&amp;", "&", $this->config['imgUrl']);
        $parts = parse_url($imgUrl);
        //http开头验证
        if (strpos($imgUrl, "http") !== 0) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_LINK");
            return false;
        }

        //微信图片
        if (strpos($imgUrl, "qpic.cn") !== 0) {
            $imgUrl = str_replace("&tp=webp","",$imgUrl);
            $img = $this->download($imgUrl);
            $this->oriName = rand(0,100).'weixinpic.png';
        }else{
            //获取请求头并检测死链
            $heads = get_headers($imgUrl);

            if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
                $this->stateInfo = $this->getStateInfo("ERROR_DEAD_LINK");
                return false;
            }

            //格式验证(扩展名验证和Content-Type验证)
            $fileType = strtolower(strrchr($imgUrl, '.'));
            if (!in_array($fileType, $this->config['allowFiles'])) {
                $this->stateInfo = $this->getStateInfo("ERROR_HTTP_CONTENTTYPE");
                return false;
            }

            //打开输出缓冲区并获取远程图片
            ob_start();
            $context = stream_context_create(
                array('http' => array(
                    'follow_location' => false // don't follow redirects
                ))
            );
            readfile($imgUrl, false, $context);
            $img = ob_get_contents();

            ob_end_clean();

            preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);


            $this->oriName = $m ? $m[1] : "";
        }


        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return false;
        }
        return $this->saveFile($img);
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    protected function getFileExt()
    {
        return strtolower(strrchr($this->oriName, '.'));
    }

    protected function download($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }
}
