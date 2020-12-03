<?php namespace Pangdongxing\UEditor;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Pangdongxing\UEditor\Uploader\UploadScrawl;
use Pangdongxing\UEditor\Uploader\UploadFile;
use Pangdongxing\UEditor\Uploader\UploadCatch;

class LumenController extends BaseController
{

    public function __construct()
    {
        if(app()->environment() == 'local') {
            header('Content-Type: text/html;charset=utf-8');
            header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
            header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE'); // 允许请求的类型
            header('Access-Control-Allow-Credentials: true'); // 设置是否允许发送 cookies
            header('Access-Control-Allow-Headers: Content-Type,Content-Length,Accept-Encoding,X-Requested-with, Origin'); // 设置允许自定义请求头的字段
        }
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
                if (config('UEditorUpload.core.mode') == 'local') {
                    $result = with(new Lists(
                        $config['imageManagerAllowFiles'],
                        $config['imageManagerListSize'],
                        $config['imageManagerListPath'],
                        $request))->getList();
                } else {
                    $result = with(new Lists(
                        $config['imageManagerAllowFiles'],
                        $config['imageManagerListSize'],
                        $config['imageManagerListPath'],
                        $request))->getList();
                }

                break;
            /* 列出文件 */
            case 'listfile':
                if (config('UEditorUpload.core.mode') == 'local') {
                    $result = with(new Lists(
                        $config['fileManagerAllowFiles'],
                        $config['fileManagerListSize'],
                        $config['fileManagerListPath'],
                        $request))->getList();
                } else {
                    $result = with(new Lists(
                        $config['imageManagerAllowFiles'],
                        $config['imageManagerListSize'],
                        $config['imageManagerListPath'],
                        $request))->getList();
                }
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
                    $info = with(new UploadCatch($upConfig, $request))->upload();
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

        if (strpos($_SERVER['HTTP_USER_AGENT'],"Triden")) {
            //如果是IE 特殊处理header
            return response($result,200)->header('Content-Type', 'text/html;charset=utf-8');
        } else{
            return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE)->header('Content-Type', 'text/json;charset=utf-8');
        }

    }


}
