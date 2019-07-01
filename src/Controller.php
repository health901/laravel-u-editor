<?php

namespace VRobin\UEditor;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use VRobin\UEditor\Uploader\UploadScrawl;
use VRobin\UEditor\Uploader\UploadFile;
use VRobin\UEditor\Uploader\UploadCatch;
use VRobin\UEditor\Uploader\Lists;


class Controller extends BaseController
{


    public function __construct()
    {

    }


    public function server(Request $request)
    {
        $config = config('UEditorUpload.upload');

        $action = $request->get('action');


        switch ($action) {

            case 'config':
                $result = $config;
                break;
            case 'uploadimage':
                $upConfig = array(
                    "pathFormat" => $config['imagePathFormat'],
                    "maxSize" => $config['imageMaxSize'],
                    "allowFiles" => $config['imageAllowFiles'],
                    'fieldName' => $config['imageFieldName'],
                );
                $result = with(new UploadFile($upConfig, $request))->upload();
                break;
            case 'uploadscrawl':
                $upConfig = array(
                    "pathFormat" => $config['scrawlPathFormat'],
                    "maxSize" => $config['scrawlMaxSize'],
                    //   "allowFiles" => $config['scrawlAllowFiles'],
                    "oriName" => "scrawl.png",
                    'fieldName' => $config['scrawlFieldName'],
                );
                $result = with(new UploadScrawl($upConfig, $request))->upload();

                break;
            case 'uploadvideo':
                $upConfig = array(
                    "pathFormat" => $config['videoPathFormat'],
                    "maxSize" => $config['videoMaxSize'],
                    "allowFiles" => $config['videoAllowFiles'],
                    'fieldName' => $config['videoFieldName'],
                );
                $result = with(new UploadFile($upConfig, $request))->upload();

                break;
            case 'uploadfile':
            default:
                $upConfig = array(
                    "pathFormat" => $config['filePathFormat'],
                    "maxSize" => $config['fileMaxSize'],
                    "allowFiles" => $config['fileAllowFiles'],
                    'fieldName' => $config['fileFieldName'],
                );
                $result = with(new UploadFile($upConfig, $request))->upload();

                break;

            /* 列出图片 */
            case 'listimage':
                $result = with(new Lists(
                    $config['imageManagerAllowFiles'],
                    $config['imageManagerListSize'],
                    $config['imageManagerListPath'],
                    $request))->getList();
                break;
            /* 列出文件 */
            case 'listfile':
                $result = with(new Lists(
                    $config['fileManagerAllowFiles'],
                    $config['fileManagerListSize'],
                    $config['fileManagerListPath'],
                    $request))->getList();
                break;

            /* 抓取远程文件 */
            case 'catchimage':

                $upConfig = array(
                    "pathFormat" => $config['catcherPathFormat'],
                    "maxSize" => $config['catcherMaxSize'],
                    "allowFiles" => $config['catcherAllowFiles'],
                    "oriName" => "remote.png",
                    'fieldName' => $config['catcherFieldName'],
                );

                $sources = $request->get($upConfig['fieldName']);
                $list = [];
                foreach ($sources as $imgUrl) {
                    $upConfig['imgUrl'] = $imgUrl;
                    $uploader = new UploadCatch($upConfig, $request);
                    $info = $uploader->upload();

                    array_push($list, array(
                        "state" => $info["state"],
                        "url" => $info["url"],
                        "size" => $info["size"],
                        "title" => htmlspecialchars($info["title"]),
                        "original" => htmlspecialchars($info["original"]),
                        "source" => $imgUrl
                    ));
                }
                $result = [
                    'state' => count($list) ? 'SUCCESS' : 'ERROR',
                    'list' => $list
                ];


                break;
        }

        return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE)->withCallback($request->input('callback'));

    }


}
