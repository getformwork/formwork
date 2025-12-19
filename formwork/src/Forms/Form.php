<?php

namespace Formwork\Forms;

use Formwork\Fields\FieldCollection;
use Formwork\Files\File;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\Request;
use Formwork\Http\RequestMethod;
use Formwork\Http\ResponseStatus;
use Formwork\Utils\Arr;
use LogicException;

class Form
{
    private bool $submitted = false;

    private bool $valid = false;

    /**
     * @var array<File>
     */
    private array $uploadedFiles = [];

    private ?string $defaultUploadsDestination = null;

    /**
     * @var array<string, mixed>
     */
    private array $requestData = [];

    private FormData $formData;

    public function __construct(
        private string $name,
        private FieldCollection $fields,
        private FileUploader $fileUploader,
    ) {}

    /**
     * Process the form submission from the given request
     *
     * @param bool $uploadFiles   Whether to process file uploads
     * @param bool $preserveEmpty Whether to preserve the value of empty fields
     */
    public function processRequest(Request $request, bool $uploadFiles = true, bool $preserveEmpty = true): static
    {
        if ($this->submitted) {
            throw new LogicException(sprintf('Form "%s" has already been processed.', $this->name));
        }

        if ($request->method() === RequestMethod::POST) {
            $this->submitted = true;

            // Get request data (merged from query, input, and files)
            $this->requestData = Arr::extend(
                $request->query()->toArray(),
                $request->input()->toArray(),
                $request->files()->toArray()
            );

            // Set field values from request data
            $this->fields->setValues($this->requestData, null);

            // Validate all fields
            $this->valid = $this->fields->isValid();

            // Process uploads only if validation passed
            if ($this->valid && $uploadFiles) {
                $this->processUploads();
            }

            // Set form data (must be done after validation and uploads)
            $this->formData = new FormData($this->fields
                ->filter(fn($field) => $field->type() !== 'upload' && ($preserveEmpty || !$field->isEmpty()))
                ->extract('value'));
        }

        return $this;
    }

    /**
     * Set default uploads destination for upload fields
     */
    public function setDefaultUploadsDestination(?string $path): static
    {
        $this->defaultUploadsDestination = $path;
        return $this;
    }

    /**
     * Get form data
     */
    public function data(): FormData
    {
        if (!$this->submitted) {
            throw new LogicException(sprintf('Form "%s" has not been submitted yet.', $this->name));
        }

        return $this->formData;
    }

    /**
     * Check if the form was submitted
     */
    public function isSubmitted(): bool
    {
        return $this->submitted;
    }

    /**
     * Check if the form is valid (only meaningful after submission)
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get appropriate HTTP response status based on form validity
     */
    public function getResponseStatus(): ResponseStatus
    {
        return $this->valid || !$this->submitted
            ? ResponseStatus::OK
            : ResponseStatus::UnprocessableEntity;
    }

    /**
     * Get the form name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the field collection
     */
    public function fields(): FieldCollection
    {
        return $this->fields;
    }

    /**
     * Get uploaded files
     *
     * @return array<File>
     */
    public function uploadedFiles(): array
    {
        if (!$this->submitted) {
            throw new LogicException(sprintf('Form "%s" has not been submitted yet.', $this->name));
        }
        return $this->uploadedFiles;
    }

    /**
     * Handle file uploads for all upload fields
     */
    private function processUploads(): void
    {
        foreach ($this->fields->filterBy('type', 'upload') as $field) {
            if ($field->isEmpty()) {
                continue;
            }

            $files = $field->isMultiple() ? $field->value() : [$field->value()];

            foreach ($files as $file) {
                $destination = $field->destination() ?? $this->defaultUploadsDestination
                    ?? throw new LogicException('No destination specified for file upload.');

                $this->uploadedFiles[] = $this->fileUploader->upload(
                    $file,
                    $destination,
                    $field->filename(),
                    $field->acceptMimeTypes(),
                    $field->overwrite(),
                );
            }
        }
    }
}
