<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Jobs\StoreFile;
use App\Jobs\UpdateFileStatus;
use App\Jobs\ZipFile;
use App\Repository\FilesRepositoryInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FilesController extends Controller
{
    /** @var FilesRepository $filesRepository */
    private $filesRepository;

    public function __construct(FilesRepositoryInterface $filesRepository)
    {
      $this->filesRepository = $filesRepository;
    }

    /**
     * Return a listing of files
     * 
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        return response()->json($this->filesRepository->getList($request), Response::HTTP_OK);
    }

    /**
     * Get the specified resource.
     * @param int $id
     */
    public function show($id)
    {
        return response()->json($this->filesRepository->find($id), Response::HTTP_OK);
    }

    /**
     * Download file from S3 storage
     * 
     */
    public function download($id)
    {
        $file = $this->filesRepository->find($id);

        // Downloads are only available for zipped files
        if ($file->status === 'ZIPPED') {            
            try {
                return Storage::download($file->getPath(), "{$file->name}.zip");
                
            } catch (Exception $e) {
                throw new Exception('File is missing or file server is not available', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } else {
            throw new Exception('Download not yet available for this file', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Store file's metadata in database and dispatch a job to store file's content in a S3 FDS
     * 
     * @param FileUploadRequest $request    Validated request: fail is not file (pointed by "file" parameter) is uploaded
     */
    public function store(FileUploadRequest $request)
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->file('file');

        // Get and validate file's metadata
        $fileData = [
            'name'      => $uploadedFile->getClientOriginalName(),
            'size'      => $uploadedFile->getSize(),
            'status'    => 'LOADED',
            'md5'       => md5_file($uploadedFile->path()),
            'user_id'   => auth()->user()->id
        ];
        $validator = Validator::make(
            $fileData, 
            [
                'name'      => 'required|unique:files',
                'size'      => 'required|numeric',
                'status'    => 'required|string',
                'md5'       => 'string|max:32|unique:files',
                'user_id'   => 'numeric' 
            ], 
            ['md5.unique' => 'Duplicated file not allowed: similar file detected'], 
            ['name' => 'filename']
        );
        $fileData = $validator->validated();
        
        // Store file's metadata in database
        $file = $this->filesRepository->create($fileData);

        // Prevent temporary uploaded file from being deleted when request completes
        $fileRealPath = $uploadedFile->getRealPath();
        move_uploaded_file($fileRealPath, $fileRealPath);

        //  Asynchronous archive/compression process
        Bus::chain([
            // 1. Store file in the S3 DFS
            new StoreFile([
                'localPath' => $fileRealPath,
                'file_id'   => $file->id,
                'file_md5'  => $fileData['md5'],
                'file_name' => $fileData['name'],
            ]),
            // 2. Update file status to "STORED"
            new UpdateFileStatus([
                'file_id'       => $file->id,
                'file_md5'      => $fileData['md5'],
                'file_status'   => 'STORED',
                'file_size'     => null,                    // No need to update file size
            ]),
            // 3. Trigger "job message" to compress file using an external service (instashare-zipper)
            new ZipFile([
                'file_id'   => $file->id,
                'file_md5'  => $fileData['md5'],
                'file_name' => $fileData['name'],
            ]),
        ])->dispatch();

        return $file;
    }

    /**
     * Update data of the specified file. Only "name" is modifiable 
     * 
     * @param Request $request
     * @param int $id ID of the file's record to update 
     */
    public function update(UpdateFileRequest $request, $id)
    {
        $data = $request->validated();
        $file = $this->filesRepository->update($data, $id);

        return response()->json($file, Response::HTTP_ACCEPTED);
    }

    /**
     * Remove data of the specified file.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $this->filesRepository->delete($id);
        return response()->noContent();
    }
}
