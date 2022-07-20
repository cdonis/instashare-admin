<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Jobs\StoreFile;
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
            throw new Exception('Download not available for this file', Response::HTTP_NOT_FOUND);
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
            'md5'       => md5_file($uploadedFile->path())
        ];
        $validator = Validator::make($fileData, [
            'name' => 'required|unique:files',
            'size' => 'required|numeric',
            'status' => 'required|string',
            'md5' => 'string|max:32',
        ], [], ['name' => 'filename']);
        
        $fileData = $validator->validated();

        // Check if the file is already uploaded (with a different name: validator ensure unique 'name')
        $fileWithSameMD5 = $this->filesRepository->findByFields([['md5', $fileData['md5']]]);
        if ($fileWithSameMD5) {
            // Set status similar to the existing file
            $fileData['status'] = $fileWithSameMD5->status;
        } 
        
        // Store file's metadata in database
        $file = $this->filesRepository->create($fileData);
        
        // If file is not already uploaded or uploaded and failed to store during previous upload 
        if (!$fileWithSameMD5 || ($fileWithSameMD5 && $file->status === 'FAILED')) {
            // Prevent temporary uploaded file from being deleted when request completes
            $fileRealPath = $uploadedFile->getRealPath();
            move_uploaded_file($fileRealPath, $fileRealPath);

            // Asynchronously:
            Bus::chain([
                // 1. Store file in the S3 DFS
                new StoreFile([
                    'localPath' => $fileRealPath,
                    'file_id'   => $file->id,
                    'file_md5'  => $fileData['md5'],
                    'file_name' => $fileData['name'],
                ]),
                // 2. Update file status to "STORED"
                function (FilesRepositoryInterface $filesRepository) use ($file, $fileRealPath) {
                    try {
                        $filesRepository->update(['status' => 'STORED'], $file->id);
                    } catch (Exception $e) {
                        // Log error: failed to update file status
                        Log::error('Failed to update file status', [
                            'file_id'           => $file->id,
                            'file_md5'          => $file->md5,
                            'file_name'         => $file->name,
                            'status_previous'   => $file->status,
                            'status_new'        => 'STORED',
                            'error_code'        => $e->getCode(),
                            'error_message'     => $e->getMessage()
                        ]);
                    }
                    
                    // Free server resources: delete temporary file
                    if (file_exists($fileRealPath))
                        unlink($fileRealPath);
                }
                // 3. Using an external service, compress the file to be ready for download
                // TODO
            ])->dispatch();
        }

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
