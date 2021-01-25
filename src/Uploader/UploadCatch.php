<?php namespace Pangdongxing\UEditor\Uploader;


/**
 * Class UploadCatch
 * 图片远程抓取
 *
 * @package Pangdongxing\UEditor\Uploader
 */
class UploadCatch  extends Upload {

    public function doUpload()
    {
        $imgUrl = str_replace("&amp;", "&", $this->config['imgUrl']);
        //http开头验证
        if (strpos($imgUrl, "http") !== 0) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_LINK");
            return false;
        }
        //获取请求头并检测死链
        /*$heads = get_headers($imgUrl);

        if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
            $this->stateInfo = $this->getStateInfo("ERROR_DEAD_LINK");
            return false;
        }

        //格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower(strrchr($imgUrl, '.'));
        if (!in_array($fileType, $this->config['allowFiles']) ) {
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

        ob_end_clean();*/

        //忽略cdn文件
        if(strpos($imgUrl, config('UEditorUpload.core.cdn.baseUrl')) === 0) {
            $fullName = substr($imgUrl, strlen(config('UEditorUpload.core.cdn.baseUrl') . DIRECTORY_SEPARATOR .
                    config('UEditorUpload.core.cdn.prefix')) + 1);
            $filePath = cdn_resource_path($fullName);
            $this->oriName = basename($imgUrl);
            $this->fileSize = filesize($filePath);
            $this->fileType = $this->getFileExt();
            $this->fullName = $fullName;
            $this->filePath = $filePath;
            $this->fileUrl = $imgUrl;
            $this->fileName = basename($imgUrl);
            $this->stateInfo = $this->stateMap[0];
            return true;
        } else {
            $ch = curl_init($imgUrl);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_NOBODY, 0); // 只取body头
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $img = curl_exec($ch);

            curl_close($ch);

            preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);

            $this->oriName = $m ? $m[1] : "";
            $this->fileSize = strlen($img);
            $this->fileType = $this->getFileExt();
            if(empty($this->fileType) && strpos($imgUrl, 'wx_fmt') !== false) {
                preg_match('/wx_fmt=(.+?)&/', $imgUrl, $extRes);
                $this->fileType = isset($extRes[1]) ? ('.' . $extRes[1]) : '';
            }
            $this->fullName = $this->getFullName();
            $this->filePath = $this->getFilePath();
            $this->fileUrl = config('UEditorUpload.core.cdn.url') . $this->fullName;
            $this->fileName = basename($this->filePath);
            $dirname = dirname($this->filePath);

            //检查文件大小是否超出限制
            if (!$this->checkSize()) {
                $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
                return false;
            }

            if(config('UEditorUpload.core.mode') == 'local'){
                //创建目录失败
                if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
                    $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
                    return false;
                } else if (!is_writeable($dirname)) {
                    $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
                    return false;
                }

                //移动文件
                if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
                    $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
                    return false;
                } else { //移动成功
                    $this->stateInfo = $this->stateMap[0];
                    return true;
                }
            } else {
                $this->stateInfo = $this->getStateInfo("ERROR_UNKNOWN_MODE");
                return false;
            }
        }





    }

    /**
     * 获取文件扩展名
     * @return string
     */
    protected function getFileExt()
    {
        return strtolower(strrchr($this->oriName, '.'));
    }
}
