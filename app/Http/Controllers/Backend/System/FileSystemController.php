<?php

namespace App\Http\Controllers\Backend\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\System\FileSystem\makeDirectoryRequest;
use App\Http\Requests\Backend\System\FileSystem\DeleteDirectoryRequest;
use App\Http\Requests\Backend\System\FileSystem\FileRequest;
use App\Http\Requests\Backend\System\FileSystem\LogDetailRequest;
use App\Http\Requests\Backend\System\FileSystem\UploadRequest;
use App\Http\Requests\Backend\System\FileSystem\UploadsRequest;
use App\Util\FileSystem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class FileSystemController extends Controller
{
    /**
     * 獲取文件列表
     *
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $directory = $request->post('directory', '') ?? '';
        $search = $request->post('search', null) ?? null;
        $offset = $request->post('offset', 0) ?? 0;
        $length = $request->post('length', 100) ?? 100;
        $offset = ($offset - 1 < 0 ? 0 : $offset - 1) * $length;
        $fileSystem = new FileSystem($directory);
        return $this->success($fileSystem->lists($offset, $length, $search)->toArray(), __('message.common.search.success'));
    }

    /**
     * 創建文件夾
     *
     * @param makeDirectoryRequest $request
     * @return Response
     */
    public function makeDirectory(makeDirectoryRequest $request): Response
    {
        try {
            $validated = $request->validated();
            $directory = $validated['directory'];
            $fileSystem = new FileSystem($directory);
            if ($fileSystem->makeDirectory($directory)) {
                activity()
                    ->useLog('file')
                    ->causedBy($request->user())
                    ->log(':causer.name 創建了文件夾 ' . $directory);
                return $this->success(msg: __('message.common.create.success'));
            }
            return $this->error(__('message.common.create.fail'));
        } catch (LogicException $exception) {
            return $this->error($exception->getMessage());
        }
    }

    /**
     * 刪除文件夾
     *
     * @param DeleteDirectoryRequest $request
     * @return Response
     */
    public function deleteDirectory(DeleteDirectoryRequest $request): Response
    {
        $validated = $request->validated();
        $directory = $validated['directory'];
        $fileSystem = new FileSystem($directory);
        if ($fileSystem->deleteDirectory($directory)) {
            activity()
                ->useLog('file')
                ->causedBy($request->user())
                ->log(':causer.name 刪除了文件夾 ' . $directory);
            return $this->success(msg: __('message.common.delete.success'));
        }
        return $this->error(__('message.common.delete.fail'));
    }

    /**
     * 上傳文件
     *
     * @param UploadRequest $request
     * @return Response
     */
    public function upload(UploadRequest $request): Response
    {
        $validated = $request->validated();
        $directory = $validated['directory'];
        $fileSystem = new FileSystem($directory);
        try {
            if (isset($validated['name'])) {
                $path = $fileSystem->putFileAs($request, 'file', $validated['name']);
            } else {
                $path = $fileSystem->putFile($request);
            }
            activity()
                ->useLog('file')
                ->causedBy($request->user())
                ->withProperties($path)
                ->log(':causer.name 上傳文件');
            return $this->success([
                'path' => $path,
                'realPath' => asset('storage/' . $path)
            ], __('message.common.create.success'));
        } catch (Throwable $exception) {
            return $this->error($exception->getMessage());
        }
    }

    /**
     * 上傳文件 markdown
     *
     * @param UploadsRequest $request
     * @return Response
     */
    public function upload_md(UploadsRequest $request): Response
    {
        $validated = $request->validated();
        $directory = $validated['directory'];
        $fileSystem = new FileSystem($directory);
        try {
            $res = [];
            $files = $request->file('file');
            foreach ($files as $k => $file) {
                $full_name = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $name = str_ireplace('.' . $extension, '', $file->getClientOriginalName());
                $has_file = false;
                $file_num = 0;
                do {
                    if ($fileSystem->getDisk()->exists($directory . '/' . $full_name)) {
                        $file_num++;
                        $has_file = true;
                        $full_name = $name . "_" . $file_num . "." . $extension;
                        continue;
                    } else {
                        $has_file = false;
                        $path = $fileSystem->putFileAs_md($request, 'file', $k, $full_name);
                    }
                } while ($has_file);
                activity()
                    ->useLog('file')
                    ->causedBy($request->user())
                    ->withProperties($path)
                    ->log(':causer.name 上傳文件');
                $res[] = [
                    'name' => $full_name,
                    'path' => $path,
                    'realPath' => asset('storage/' . $path)
                ];
            }
            return $this->success(msg: __('message.common.create.success'))
                ->withData(["files" => $res]);
        } catch (Throwable $exception) {
            return $this->error($exception->getMessage());
        }
    }

    /**
     * 下載文件(流)
     *
     * @param FileRequest $request
     * @return Response
     */
    public function download(FileRequest $request): Response
    {
        $validated = $request->validated();
        $fileSystem = new FileSystem('');
        try {
            activity()
                ->useLog('file')
                ->causedBy($request->user())
                ->log(':causer.name 下載了文件 ' . $validated['file']);
            return $fileSystem->download($validated['file']);
        } catch (Exception $exception) {
            return $this->error($exception->getMessage());
        }
    }

    /**
     * 刪除文件
     *
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $paths = $request->post('paths');
        $fileSystem = new FileSystem('');
        if ($fileSystem->delete($paths)) {
            activity()
                ->useLog('file')
                ->causedBy($request->user())
                ->withProperties($paths)
                ->log(':causer.name 刪除了文件');
            return $this->success(msg: __('message.common.delete.success'));
        }
        return $this->error(__('message.common.delete.fail'));
    }

    public function uploadImage(Request $request): Response
    {
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            if (getimagesize($image) !== false) {
                if ($path = $image->store('public/image')) {
                    activity()
                        ->useLog('file')
                        ->causedBy($request->user())
                        ->withProperties($image)
                        ->log(':causer.name 上傳圖片');
                    return $this->success([
                        'path' => asset(Str::of($path)->replace('public', 'storage'))
                    ], __('message.common.upload.success'));
                }
            }
            return $this->error(__('message.common.upload.image_type_error'));
        }
        return $this->error(__('message.common.upload.need_image'));
    }

    public function uploadFile(Request $request): Response
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if ($file->extension() === 'bin') {
                return $this->error(__('message.common.upload.file_cannot_empty'));
            }
            if (in_array($file->extension(), [
                'jpg', 'jpeg', 'png', 'bmp', 'wav', 'xls', 'xlsx', 'csv', 'pdf', 'doc', 'docx', 'txt', 'mp4'
            ])) {
                if ($path = $file->store('public/file')) {
                    activity()
                        ->useLog('file')
                        ->causedBy($request->user())
                        ->withProperties($file)
                        ->log(':causer.name 上傳文件');
                    return $this->success([
                        'path' => asset(Str::of($path)->replace('public', 'storage'))
                    ], __('message.common.upload.success'));
                }
            }
            return $this->error(__('message.common.upload.file_type_error'));
        }
        return $this->error(__('message.common.upload.need_file'));
    }

    public function removeFile(Request $request): Response
    {
        $path = $request->post('path');
        if ($path) {
            $path = Str::of($path)->replace(config('app.url') . '/storage', 'public');
            if (Storage::exists($path)) {
                Storage::delete($path);
                activity()
                    ->useLog('file')
                    ->causedBy($request->user())
                    ->withProperties($path)
                    ->log(':causer.name 刪除文件');
                return $this->success(msg: __('message.common.delete.success'));
            }
            return $this->error(__('message.common.upload.file_does_not_exist'));
        }
        return $this->success(msg: __('message.common.delete.success'));
    }


    /**
     * 獲取文件列表
     *
     * @return Response
     */
    public function logList(Request $request): Response
    {
        $directory = $request->post('directory', '') ?? '';
        $search = $request->post('search', null) ?? null;
        $fileSystem = new FileSystem($directory, 'logs');
        $offset = $request->post('offset', 0) ?? 0;
        $length = $request->post('length', 100) ?? 100;
        return $this->success($fileSystem->lists($offset, $length, $search)->toArray(), __('message.common.search.success'));
    }

    /**
     * 獲取文件信息
     * @param LogDetailRequest $request
     * @return Response
     */
    public function logDetail(LogDetailRequest $request): Response
    {
        $param = $request->validated();
        $fileSystem = new FileSystem('/', 'logs');
        $contents = $fileSystem->getDisk()->get($param['file']);
        if ($contents === null) {
            return $this->error(__('message.file.not_found'));
        }
        return $this->success([
            'file' => $contents
        ], __('message.common.search.success'));
    }

}
