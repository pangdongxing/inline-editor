<?php namespace Pangdongxing\UEditor;


class Lists
{

    private $allowFiles;
    private $listSize;
    private $path;
    private $request;


    public function __construct($allowFiles, $listSize, $path, $request)
    {
        $this->allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);
        $this->listSize = $listSize;
        $this->path = $path;
        $this->request = $request;
    }

    public function getList()
    {

        $size = $this->request->get('size', $this->listSize);
        $start = $this->request->get('start', 0);
        $end = $start + $size;
        /* 获取文件列表 */
        $path = cdn_resource_path() . ltrim($this->path,'/');

        $files = $this->getFiles($path, $this->allowFiles);
        if (!count($files)) {
            return [
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => count($files)
            ];
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
            $list[] = $files[$i];
        }


        /* 返回数据 */
        return [
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        ];
    }
    /**
     * 遍历获取目录下的指定类型的文件
     * @param $path
     * @param $allowFiles
     * @param array $files
     * @return array
     */
    protected function getFiles($path, $allowFiles, &$files = array())
    {

        if (!is_dir($path)) return null;
        $files = [];
        if(substr($path, strlen($path) - 1) != '/') $path .= '/';
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getFiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
                        $files[] = array(
                            'url' => str_replace(cdn_resource_path(), config('UEditorUpload.core.cdn.url') . '/', $path2),
                            'mtime' => filemtime($path2)
                        );
                    }
                }
            }
        }
        return $files;
    }

}
