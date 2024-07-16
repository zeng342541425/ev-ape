<?php

namespace App\Services\Common;

use App\Constants\HttpCode;
use Exception;
use Illuminate\Http\UploadedFile;
use Modules\Common\Exceptions\StatusData;
use ZipArchive;

class UploadFileService
{

    # 文件類型
    const FILE_TYPE_IMAGE = 'image';
    const FILE_TYPE_VIDEO = 'video';
    const FILE_TYPE_MEDIA = 'media';
    const FILE_TYPE_PDF = 'pdf';
    const FILE_TYPE_OTHER = 'other';

    # 文件類型
    const FILE_TYPE = [
        self::FILE_TYPE_IMAGE => [
            "name" => "(jpg/png)",
            "accept" => ".png, .jpg, .jpeg",
            "type" => [
                "image/jpeg",
                "image/png"
            ]
        ],
        self::FILE_TYPE_VIDEO => [
            "name" => "(mp4)",
            "accept" => ".mp4,",
            "type" => [
                "video/mp4",
            ]
        ],
        self::FILE_TYPE_MEDIA => [
            "name" => "(mp4)",
            "accept" => ".mp4,.png, .jpg, .jpeg",
            "type" => [
                "image/jpeg",
                "image/png",
                "video/mp4",
            ]
        ],
        self::FILE_TYPE_PDF => [
            "name" => "(pdf)",
            "accept" => ".pdf",
            "type" => [
                "application/pdf"
            ]
        ],
        self::FILE_TYPE_OTHER => [
            "name" => "(word/pdf/jpg/png)",
            "accept" => ".doc, .docx, .png, .jpg, .jpeg, .pdf",
            "type" => [
                "image/jpeg",
                "image/png",
                "application/pdf",
                "application/msword",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            ]
        ]
    ];

    public $file;

    public $disk;

    /**
     * 設置文件
     * @param $file
     * @return UploadFileService
     * @throws Exception
     */
    public static function file($file)
    {
        return (new self())->setFile($file);
    }

    /**
     * 文件
     * @param UploadedFile $file
     * @return UploadFileService
     */
    public function setFile($file): static
    {
        if (!$file instanceof UploadedFile) {
            throw new Exception(trans('exceptions.FILE_UPLOAD_FAILED') . '1', 207);
        }
        $this->file = $file;
        return $this;
    }

    /**
     * 設置磁盤
     * @param $disk
     * @return UploadFileService
     */
    public function setDisk($disk): static
    {
        $this->disk = $disk;
        return $this;
    }

    /**
     * 獲取磁盤
     * @return mixed
     */
    public function getDisk(): mixed
    {
        return $this->disk ?? config('filesystems.default');
    }

    /**
     * 上傳文件
     * @param $file
     * @param $randomFolder
     * @return array
     * @throws Exception
     */
    public function upload($path, $save_as = null): array
    {
        $disk = $this->getDisk();
        if ($save_as) {
            $bool = $this->file->storeAs($path, $save_as, $disk);
        } else {
            $bool = $this->file->store($path, $disk);
        }

        if (!$bool) {
            throw new Exception('上傳失敗', 207);
        }

        return [
            'path' => config('filesystems.disks.' . $disk . '.path') . '/' . $bool,
            'url' => config('filesystems.disks.' . $disk . '.url') . '/' . $bool,
        ];
    }

    /**
     * 文件無效
     * @return $this
     * @throws Exception
     */
    public function isFileValid(): static
    {
        if (!$this->file->isFile() || !$this->file->isValid()) {
            throw new Exception('格式不正確', 207);
        }
        return $this;
    }

    /**
     * 是否是圖片
     * @return $this
     * @throws Exception
     */
    public function isImage(): static
    {
        return $this->checkType(self::FILE_TYPE_IMAGE);
    }

    /**
     * 是否是圖片
     * @return $this
     * @throws Exception
     */
    public function isMedia(): static
    {
        return $this->checkType(self::FILE_TYPE_MEDIA);
    }

    /**
     * 是否是圖片
     * @return $this
     * @throws Exception
     */
    public function isVideo(): static
    {
        return $this->checkType(self::FILE_TYPE_VIDEO);
    }

    /**
     * 驗證類型
     * @param $typee
     * @return $this
     * @throws Exception
     */
    public function checkType($type): static
    {
        if (!in_array($this->file->getMimeType(), self::FILE_TYPE[$type]['type'])) {
            throw new Exception(trans('exceptions.FILE_TYPE_ERROR'), 207);
        }
        return $this;
    }

    /**
     * 下載文件
     *
     * @param $url
     * @return void
     */
    public function readFile($url): void
    {
        $path_info = pathinfo($url);

        $file_name = $path_info['basename'];

        $file_name = storage_path('app/public/'.$file_name);
        // $file_name = storage_path($file_name);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $file_content = curl_exec($ch);
        curl_close($ch);
        $downloaded_file = fopen($file_name, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);

        // 以只讀和二進制模式打開文件
        $file = fopen ( $file_name, "rb" );

        // 告訴瀏覽器這是一個文件流格式的文件
        Header ( "Content-type: application/octet-stream" );
        Header ( "Access-Control-Allow-Origin: *");

        // 請求範圍的度量單位
        Header ( "Accept-Ranges: bytes" );

        //Content-Length 是指定包含于請求或響應中數據的字節長度
        Header ( "Accept-Length: " . filesize ( $file_name ) );

        // 用來告訴瀏覽器，文件是可以當作附件被下載，下載後的文件名稱為$file_name該變量的值。
        Header ( "Content-Disposition: attachment; filename=" . $file_name );

        // 讀取文件内容，直接輸出給瀏覽器
        echo fread ( $file, filesize ( $file_name ) );

        // 關閉文件句柄
        fclose ( $file );

        // 刪除本地文件
        unlink($file_name);

        exit ();

    }

    /**
     * 打包zip文件，不提供下載
     *
     * @param string $filename
     * @param array $data
     * @return string
     */
    public function zipArchive(string $filename, array $data=[]): string
    {
        if (count($data) > 0) {
            $zip = new ZipArchive;

            // 打開和創建zip文件
            $f = $zip->open($filename,ZipArchive::CREATE|ZipArchive::OVERWRITE);
            $files = [];
            if ($f === true) {
                foreach ($data as $url){

                    $path_info = pathinfo($url);

                    $file_name = $path_info['basename'];

                    $file_name = storage_path('app/public/'.$file_name);
                    // $file_name = storage_path($file_name);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch,CURLOPT_URL,$url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $file_content = curl_exec($ch);
                    curl_close($ch);
                    $downloaded_file = fopen($file_name, 'w');
                    fwrite($downloaded_file, $file_content);
                    fclose($downloaded_file);

                    $zip->addFile($file_name, $path_info['basename']);

                    if (!in_array($file_name, $files)) {
                        $files[] = $file_name;
                    }

                }

            }

            $zip->close();

            if ($files) {
                foreach($files as $file) {
                    unlink($file);
                }
            }

        }

        return $filename;
    }
}
