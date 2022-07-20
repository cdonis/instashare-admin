<?php

namespace App\Http\Requests;

use App\Repository\FilesRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $fileID = "";
        $idFromURL = $this->segment(3);       // Get ID from URL
        if (isset($idFromURL)) {
            $filesRepository = resolve(FilesRepositoryInterface::class);  
            $file = $filesRepository->find($idFromURL);
            if ($file) $fileID = $file->id;
        }

        // "name" is the only attribute that can be modified from users
        return [
            'name'   => [
                'max:255', 
                'string', 
                'regex:/^[0-9a-zA-ZáéíóúÁÉÍÓÚñÑ._\s]+$/',
                Rule::unique('files')->ignore($fileID)
            ],
        ];
    }
}
