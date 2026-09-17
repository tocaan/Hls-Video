<?php

namespace  HlsVideos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadVideoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * `model_type` reaches the controller as a static call target, so it is
     * never trusted from the request. Only the aliases and class-strings
     * listed in `hls-videos.videoable_models` are accepted, which also means
     * an app that never defines that key cannot attach a model at all.
     *
     * @return array
     */
    public function rules()
    {
        $videoableModels = (array) config('hls-videos.videoable_models', []);

        return [
            'file' => 'required',
            'chunk' => 'nullable|integer',
            'chunks' => 'nullable|integer',
            'model_type' => [
                'nullable',
                'string',
                Rule::in(array_merge(array_keys($videoableModels), array_values($videoableModels))),
            ],
            'model_id' => ['nullable', 'required_with:model_type'],
        ];
    }

    /**
     * Resolve the validated `model_type` to its allowlisted class-string.
     *
     * Accepts either the alias (the config array key) or the class-string
     * itself, so apps that were passing a FQCN keep working.
     */
    public function videoableModelClass(): ?string
    {
        $modelType = $this->input('model_type');

        if (! is_string($modelType) || $modelType === '') {
            return null;
        }

        $videoableModels = (array) config('hls-videos.videoable_models', []);

        if (isset($videoableModels[$modelType])) {
            return $videoableModels[$modelType];
        }

        return in_array($modelType, $videoableModels, true) ? $modelType : null;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is the consuming app's job: point
     * `hls-videos.uploader_access_middleware` at whatever guard and
     * permission checks the upload endpoint should sit behind.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
