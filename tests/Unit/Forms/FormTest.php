<?php

namespace Formwork\Tests\Unit\Forms;

use Formwork\Cms\App;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Files\File;
use Formwork\Files\Services\FileUploader;
use Formwork\Forms\Form;
use Formwork\Http\Files\UploadedFile;
use Formwork\Http\Request;
use Formwork\Http\RequestMethod;
use Formwork\Http\ResponseStatus;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Form::class)]
final class FormTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = App::instance();

        // `FieldFactory` is registered lazily by the schemes service loader,
        // so make sure it is available regardless of the test execution order
        $this->app->getService(Schemes::class);
    }

    public function testNameAndFieldsAreAvailable(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type' => 'text',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $this->assertSame('contact', $form->name());
        $this->assertSame($fields, $form->fields()->toArray());
    }

    public function testNewFormIsNotSubmittedAndIsInvalid(): void
    {
        $form = $this->createForm('contact');

        $this->assertFalse($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertSame(ResponseStatus::OK, $form->getResponseStatus());
    }

    public function testDataCannotBeReadBeforeSubmission(): void
    {
        $form = $this->createForm('contact');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Form "contact" has not been submitted yet.');

        $form->data();
    }

    public function testUploadedFilesCannotBeReadBeforeSubmission(): void
    {
        $form = $this->createForm('contact');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Form "contact" has not been submitted yet.');

        $form->uploadedFiles();
    }

    public function testNonPostRequestDoesNotSubmitTheForm(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type'  => 'text',
                'value' => 'original',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $request = $this->createRequest(RequestMethod::GET, input: ['name' => 'changed']);

        $this->assertSame($form, $form->processRequest($request));

        $this->assertFalse($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertSame(ResponseStatus::OK, $form->getResponseStatus());
        $this->assertSame(['name' => 'original'], (new FieldCollection($fields))->extract('value'));
    }

    public function testPostRequestSubmitsTheForm(): void
    {
        $form = $this->createForm('contact');

        $form->processRequest($this->createRequest(RequestMethod::POST));

        $this->assertTrue($form->isSubmitted());
    }

    public function testProcessRequestCannotBeCalledTwice(): void
    {
        $form = $this->createForm('contact');

        $request = $this->createRequest(RequestMethod::POST);

        $form->processRequest($request);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Form "contact" has already been processed.');

        $form->processRequest($request);
    }

    public function testProcessRequestReturnsTheSameForm(): void
    {
        $form = $this->createForm('contact');

        $this->assertSame($form, $form->processRequest($this->createRequest(RequestMethod::POST)));
    }

    public function testRequestInputOverridesQueryData(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type' => 'text',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest(
            $this->createRequest(
                RequestMethod::POST,
                input: ['name' => 'input'],
                query: ['name' => 'query'],
            ),
        );

        $this->assertSame(['name' => 'input'], $form->data()->toArray());
    }

    public function testMissingTextFieldSetsEmptyValue(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type'    => 'text',
                'default' => 'Default name',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(RequestMethod::POST));

        $this->assertSame(['name' => ''], $form->data()->toArray());
    }

    public function testSubmittedValueOverridesFieldDefault(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type'    => 'text',
                'default' => 'Default name',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(RequestMethod::POST, input: ['name' => 'Submitted name']));

        $this->assertSame(['name' => 'Submitted name'], $form->data()->toArray());
    }

    public function testValidSubmissionHasOkResponseStatus(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type'     => 'text',
                'required' => true,
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(RequestMethod::POST, input: ['name' => 'Sempronius']));

        $this->assertTrue($form->isValid());
        $this->assertSame(ResponseStatus::OK, $form->getResponseStatus());
    }

    public function testInvalidSubmissionHasUnprocessableEntityResponseStatus(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type'     => 'text',
                'required' => true,
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(RequestMethod::POST));

        $this->assertFalse($form->isValid());
        $this->assertSame(ResponseStatus::UnprocessableEntity, $form->getResponseStatus());
    }

    public function testInvalidSubmissionDoesNotInvokeTheUploader(): void
    {
        $uploader = $this->createMock(FileUploader::class);
        $uploader->expects($this->never())->method('upload');
        $form = $this->createForm('upload', [
            'name'  => $this->createField('name', ['type' => 'text', 'required' => true]),
            'photo' => $this->createField('photo', ['type' => 'upload', 'destination' => '/uploads']),
        ], $uploader);

        $form->processRequest($this->createRequest(
            RequestMethod::POST,
            files: ['photo' => $this->uploadData()],
        ));

        $this->assertFalse($form->isValid());
        $this->assertSame(ResponseStatus::UnprocessableEntity, $form->getResponseStatus());
        $this->assertSame([], $form->uploadedFiles());
    }

    public function testFormDataContainsNonUploadFields(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type' => 'text',
            ]),
            'email' => $this->createField('email', [
                'type' => 'email',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(RequestMethod::POST, input: [
            'name'  => 'Sempronius',
            'email' => 'sempronius@example.com',
        ]));

        $this->assertSame([
            'name'  => 'Sempronius',
            'email' => 'sempronius@example.com',
        ], $form->data()->toArray());
    }

    public function testUploadFieldsAreExcludedFromFormData(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type' => 'text',
            ]),
            'attachment' => $this->createField('attachment', [
                'type' => 'upload',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(RequestMethod::POST, input: [
            'name' => 'Sempronius',
        ], files: [
            'attachment' => $this->uploadData(),
        ]), uploadFiles: false);

        $this->assertSame(['name' => 'Sempronius'], $form->data()->toArray());
    }

    public function testUploadFailureDoesNotFabricateAnUploadedFile(): void
    {
        $uploader = $this->createStub(FileUploader::class);
        $uploader->method('upload')->willThrowException(new \RuntimeException('upload failed'));
        $form = $this->createForm('upload', [
            'photo' => $this->createField('photo', ['type' => 'upload', 'destination' => '/uploads']),
        ], $uploader);

        try {
            $form->processRequest($this->createRequest(
                RequestMethod::POST,
                files: ['photo' => $this->uploadData()],
            ));
            $this->fail('The upload exception should have propagated.');
        } catch (\RuntimeException) {
            // Expected.
        }

        $this->assertSame([], $form->uploadedFiles());
    }

    public function testPreserveEmptyKeepsEmptyFields(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type' => 'text',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(RequestMethod::POST), preserveEmpty: true);

        $this->assertSame(['name' => ''], $form->data()->toArray());
    }

    public function testPreserveEmptyFalseRemovesEmptyFields(): void
    {
        $fields = [
            'name' => $this->createField('name', [
                'type' => 'text',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(RequestMethod::POST), preserveEmpty: false);

        $this->assertSame([], $form->data()->toArray());
    }

    public function testPreserveEmptyFalseChangesOnlyTheFormDataProjection(): void
    {
        $fields = [
            'name'    => $this->createField('name', ['type' => 'text']),
            'message' => $this->createField('message', ['type' => 'text']),
        ];
        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(
            RequestMethod::POST,
            input: ['name' => 'Alice'],
        ), preserveEmpty: false);

        $this->assertSame(['name' => 'Alice'], $form->data()->toArray());
        $this->assertSame('', $form->fields()->get('message')->value());
    }

    public function testSetDefaultUploadsDestinationReturnsTheSameForm(): void
    {
        $form = $this->createForm('contact');

        $this->assertSame($form, $form->setDefaultUploadsDestination('/uploads'));
    }

    public function testUploadsAreNotProcessedWhenUploadProcessingIsDisabled(): void
    {
        $uploader = $this->createMock(FileUploader::class);

        $uploader
            ->expects($this->never())
            ->method('upload');

        $form = $this->createForm(
            'contact',
            [
                'attachment' => $this->createField('attachment', [
                    'type' => 'upload',
                ]),
            ],
        );

        $form->processRequest($this->createRequest(RequestMethod::POST, files: [
            'attachment' => $this->uploadData(),
        ]), uploadFiles: false);

        $this->assertSame([], $form->uploadedFiles());
    }

    public function testUploadsAreNotProcessedWhenValidationFails(): void
    {
        $uploader = $this->createMock(FileUploader::class);

        $uploader
            ->expects($this->never())
            ->method('upload');

        $fields = [
            'name' => $this->createField('name', [
                'type'     => 'text',
                'required' => true,
            ]),
            'attachment' => $this->createField('attachment', [
                'type' => 'upload',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(RequestMethod::POST, files: [
            'attachment' => $this->uploadData(),
        ]));

        $this->assertFalse($form->isValid());
        $this->assertSame([], $form->uploadedFiles());
    }

    public function testEmptyUploadIsIgnored(): void
    {
        $uploader = $this->createMock(FileUploader::class);
        $uploader
            ->expects($this->never())
            ->method('upload');

        $fields = [
            'attachment' => $this->createField('attachment', [
                'type' => 'upload',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $form->processRequest($this->createRequest(RequestMethod::POST));

        $this->assertSame([], $form->uploadedFiles());
    }

    public function testSingleUploadIsProcessed(): void
    {
        $uploadedFile = new File('/uploads/photo.jpg');

        $uploader = $this->createMock(FileUploader::class);

        $uploader
            ->expects($this->once())
            ->method('upload')
            ->with(
                $this->isInstanceOf(UploadedFile::class),
                '/uploads',
                'photo',
                ['image/jpeg'],
                true,
            )
            ->willReturn($uploadedFile);

        $fields = [
            'attachment' => $this->createField('attachment', [
                'type'        => 'upload',
                'destination' => '/uploads',
                'filename'    => 'photo',
                'accept'      => ['jpg'],
                'overwrite'   => true,
            ]),
        ];

        $form = $this->createForm('contact', $fields, $uploader);

        $form->processRequest($this->createRequest(RequestMethod::POST, files: [
            'attachment' => $this->uploadData(),
        ]));

        $this->assertSame([$uploadedFile], $form->uploadedFiles());
    }

    public function testDefaultUploadDestinationIsUsedWhenFieldHasNoDestination(): void
    {
        $uploadedFile = new File('/uploads/photo.jpg');
        $uploader = $this->createMock(FileUploader::class);

        $uploader
            ->expects($this->once())
            ->method('upload')
            ->with(
                $this->isInstanceOf(UploadedFile::class),
                '/uploads',
                null,
                ['image/jpeg'],
                false,
            )
            ->willReturn($uploadedFile);

        $fields = [
            'attachment' => $this->createField('attachment', [
                'type'   => 'upload',
                'accept' => ['jpg'],
            ]),
        ];

        $form = $this->createForm('contact', $fields, $uploader);

        $form->setDefaultUploadsDestination('/uploads');

        $form->processRequest($this->createRequest(RequestMethod::POST, files: [
            'attachment' => $this->uploadData(),
        ]));

        $this->assertSame([$uploadedFile], $form->uploadedFiles());
    }

    public function testDefaultUploadDestinationIsUsedOnlyWhenFieldDestinationIsMissing(): void
    {
        $uploaded = new File('/uploads/result.jpg');
        $uploader = $this->createMock(FileUploader::class);
        $uploader->expects($this->once())
            ->method('upload')
            ->with($this->isInstanceOf(UploadedFile::class), '/default', null, [], false)
            ->willReturn($uploaded);

        $form = $this->createForm('upload', [
            'photo' => $this->createField('photo', ['type' => 'upload', 'accept' => '']),
        ], $uploader);
        $form->setDefaultUploadsDestination('/default');
        $form->processRequest($this->createRequest(RequestMethod::POST, files: ['photo' => $this->uploadData()]));

        $this->assertSame([$uploaded], $form->uploadedFiles());
    }

    public function testFieldUploadDestinationTakesPrecedenceOverDefaultDestination(): void
    {
        $uploadedFile = new File('/field-uploads/photo.jpg');
        $uploader = $this->createMock(FileUploader::class);

        $uploader
            ->expects($this->once())
            ->method('upload')
            ->with(
                $this->isInstanceOf(UploadedFile::class),
                '/field-uploads',
                null,
                ['image/jpeg'],
                false,
            )
            ->willReturn($uploadedFile);

        $fields = [
            'attachment' => $this->createField('attachment', [
                'type'        => 'upload',
                'destination' => '/field-uploads',
                'accept'      => ['jpg'],
            ]),
        ];

        $form = $this->createForm('contact', $fields, $uploader);

        $form->setDefaultUploadsDestination('/default-uploads');

        $form->processRequest($this->createRequest(RequestMethod::POST, files: [
            'attachment' => $this->uploadData(),
        ]));

        $this->assertSame([$uploadedFile], $form->uploadedFiles());
    }

    public function testMissingUploadDestinationThrows(): void
    {
        $fields = [
            'attachment' => $this->createField('attachment', [
                'type' => 'upload',
            ]),
        ];

        $form = $this->createForm('contact', $fields);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No destination specified for file upload.');

        $form->processRequest($this->createRequest(RequestMethod::POST, files: [
            'attachment' => $this->uploadData(),
        ]));
    }

    public function testMultipleUploadsAreProcessed(): void
    {
        $firstResult = new File('/uploads/first.jpg');
        $secondResult = new File('/uploads/second.jpg');

        $uploader = $this->createMock(FileUploader::class);

        $uploader
            ->expects($this->exactly(2))
            ->method('upload')
            ->with(
                $this->isInstanceOf(UploadedFile::class),
                '/uploads',
                null,
                ['image/jpeg'],
                false,
            )
            ->willReturnCallback(static fn(UploadedFile $file): File => match ($file->clientName()) {
                'first.jpg'  => $firstResult,
                'second.jpg' => $secondResult,
                default      => throw new LogicException('Unexpected uploaded file.'),
            });

        $fields = [
            'attachments' => $this->createField('attachments', [
                'type'        => 'upload',
                'accept'      => ['jpg'],
                'multiple'    => true,
                'destination' => '/uploads',
            ]),
        ];

        $form = $this->createForm('contact', $fields, $uploader);

        $form->processRequest($this->createRequest(RequestMethod::POST, files: [
            'attachments' => [
                'name'      => ['first.jpg', 'second.jpg'],
                'full_path' => ['first.jpg', 'second.jpg'],
                'type'      => ['image/jpeg', 'image/jpeg'],
                'tmp_name'  => ['/tmp/first.jpg', '/tmp/second.jpg'],
                'error'     => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                'size'      => ['123', '456'],
            ],
        ]));

        $this->assertSame([$firstResult, $secondResult], $form->uploadedFiles());
    }

    #[DataProvider('fieldTypesProvider')]
    public function testFieldTypeValues(
        string $type,
        array $config,
        mixed $submittedValue,
        mixed $expectedSubmittedValue,
        mixed $expectedMissingValue,
    ): void {
        $fieldName = $type;

        $form = $this->createForm('test', [
            $fieldName => $this->createField($fieldName, [
                'type' => $type,
                ...$config,
            ]),
        ]);

        $form->processRequest($this->createRequest(RequestMethod::POST, input: [
            $fieldName => $submittedValue,
        ]));

        $this->assertSame(
            [$fieldName => $expectedSubmittedValue],
            $form->data()->toArray(),
        );

        $form = $this->createForm('test', [
            $fieldName => $this->createField($fieldName, [
                'type' => $type,
                ...$config,
            ]),
        ]);

        $form->processRequest($this->createRequest(RequestMethod::POST));

        $this->assertSame(
            [$fieldName => $expectedMissingValue],
            $form->data()->toArray(),
        );
    }

    public static function fieldTypesProvider(): iterable
    {
        yield 'array' => [
            'array',
            [],
            ['first', 'second'],
            ['first', 'second'],
            [],
        ];

        yield 'checkbox' => [
            'checkbox',
            [],
            true,
            true,
            false,
        ];

        yield 'color' => [
            'color',
            [],
            '#FF0000',
            '#ff0000',
            null,
        ];

        yield 'date' => [
            'date',
            [],
            '2026-01-15',
            '2026-01-15 00:00:00',
            null,
        ];

        yield 'duration' => [
            'duration',
            [],
            '3600',
            3600,
            null,
        ];

        yield 'email' => [
            'email',
            [],
            'sempronius@example.com',
            'sempronius@example.com',
            '',
        ];

        yield 'file' => [
            'file',
            [],
            'document.pdf',
            'document.pdf',
            null,
        ];

        yield 'files' => [
            'files',
            [],
            ['document.pdf', 'image.jpg'],
            ['document.pdf', 'image.jpg'],
            [],
        ];

        yield 'image' => [
            'image',
            [],
            'photo.jpg',
            'photo.jpg',
            null,
        ];

        yield 'images' => [
            'images',
            [],
            ['photo.jpg', 'another.jpg'],
            ['photo.jpg', 'another.jpg'],
            [],
        ];

        yield 'markdown' => [
            'markdown',
            [],
            '**Hello**',
            '**Hello**',
            '',
        ];

        yield 'number' => [
            'number',
            [],
            '42',
            42,
            null,
        ];

        yield 'page' => [
            'page',
            [],
            '/about',
            '/about',
            null,
        ];

        yield 'password' => [
            'password',
            [],
            'secret',
            'secret',
            '',
        ];

        yield 'range' => [
            'range',
            [],
            '50',
            50,
            null,
        ];

        yield 'select' => [
            'select',
            [
                'options' => [
                    'first'  => 'First',
                    'second' => 'Second',
                ],
            ],
            'second',
            'second',
            '',
        ];

        yield 'slug' => [
            'slug',
            [],
            'my-page',
            'my-page',
            '',
        ];

        yield 'tags' => [
            'tags',
            [],
            ['first', 'second'],
            ['first', 'second'],
            [],
        ];

        yield 'template' => [
            'template',
            [],
            'default',
            'default',
            null,
        ];

        yield 'text' => [
            'text',
            [],
            'Sempronius',
            'Sempronius',
            '',
        ];

        yield 'textarea' => [
            'textarea',
            [],
            'A description',
            'A description',
            '',
        ];

        yield 'togglegroup' => [
            'togglegroup',
            [],
            'first',
            'first',
            null,
        ];
    }

    #[DataProvider('validSelectValueProvider')]
    public function testSelectFieldAcceptsDeclaredOptions(array $options, mixed $submitted, mixed $expected): void
    {
        $form = $this->submitSelect($options, $submitted);

        $this->assertTrue($form->isValid());
        $this->assertSame(['role' => $expected], $form->data()->toArray());
    }

    /**
     * @return iterable<string, array{array<int|string, string>, mixed, mixed}>
     */
    public static function validSelectValueProvider(): iterable
    {
        $named = ['admin' => 'Administrator', 'editor' => 'Editor'];
        $numeric = [1 => 'One', 2 => 'Two'];

        yield 'first option' => [$named, 'admin', 'admin'];
        yield 'last option' => [$named, 'editor', 'editor'];
        yield 'empty value' => [$named, '', ''];
        yield 'missing value' => [$named, null, ''];
        yield 'numeric option' => [$numeric, '1', 1];
        yield 'other numeric option' => [$numeric, '2', 2];
    }

    #[DataProvider('invalidSelectValueProvider')]
    public function testSelectFieldRejectsValuesOutsideItsOptions(array $options, mixed $submitted): void
    {
        $form = $this->submitSelect($options, $submitted);

        $this->assertFalse($form->isValid());
        $this->assertSame(ResponseStatus::UnprocessableEntity, $form->getResponseStatus());
    }

    /**
     * @return iterable<string, array{array<int|string, string>, mixed}>
     */
    public static function invalidSelectValueProvider(): iterable
    {
        $named = ['admin' => 'Administrator', 'editor' => 'Editor'];
        $numeric = [1 => 'One', 2 => 'Two'];

        yield 'unknown option' => [$named, 'nobody'];
        yield 'different case' => [$named, 'Admin'];
        yield 'surrounding space' => [$named, ' admin'];
        yield 'option label' => [$named, 'Administrator'];
        yield 'number for named options' => [$named, '1'];
        yield 'zero for named options' => [$named, '0'];
        yield 'unknown number' => [$numeric, '3'];
        yield 'zero' => [$numeric, '0'];
        yield 'leading zero' => [$numeric, '01'];
        yield 'decimal number' => [$numeric, '1.9'];
        yield 'exponent' => [$numeric, '1e0'];
        yield 'text for numeric options' => [$numeric, 'admin'];
    }

    /**
     * Values that PHP would silently convert to an existing array key must not match it
     */
    #[DataProvider('jugglingSelectValueProvider')]
    public function testSelectFieldDoesNotAcceptValuesConvertedToAnOptionKey(mixed $submitted): void
    {
        $form = $this->submitSelect([1 => 'One', 2 => 'Two'], $submitted);

        $this->assertFalse($form->isValid());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function jugglingSelectValueProvider(): iterable
    {
        yield 'true' => [true];
        yield 'float with the same integer part' => [1.9];
        yield 'negative float' => [-1.5];
    }

    #[DataProvider('nonScalarSelectValueProvider')]
    public function testSelectFieldRejectsNonScalarValuesWithoutFailing(mixed $submitted): void
    {
        $form = $this->submitSelect(['admin' => 'Administrator', 'editor' => 'Editor'], $submitted);

        $this->assertFalse($form->isValid());
        $this->assertSame(ResponseStatus::UnprocessableEntity, $form->getResponseStatus());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonScalarSelectValueProvider(): iterable
    {
        yield 'list' => [['admin']];
        yield 'associative array' => [['admin' => 'admin']];
        yield 'nested array' => [[['admin']]];
    }

    /**
     * @param array<int|string, string> $options
     */
    private function submitSelect(array $options, mixed $submitted): Form
    {
        $form = $this->createForm('test', [
            'role' => $this->createField('role', ['type' => 'select', 'options' => $options]),
        ]);

        return $form->processRequest($this->createRequest(RequestMethod::POST, input: ['role' => $submitted]));
    }

    private function createField(string $name, array $data): Field
    {
        return $this->app->getService(FieldFactory::class)->make($name, $data);
    }

    private function createForm(string $name, array $fields = [], ?FileUploader $uploader = null): Form
    {
        return new Form($name, new FieldCollection($fields), $uploader ?? $this->createStub(FileUploader::class));
    }

    private function createRequest(RequestMethod $method, array $input = [], array $query = [], array $files = []): Request
    {
        return new Request($input, $query, [], $files, ['REQUEST_METHOD' => $method->value]);
    }

    /**
     * @return array<string, mixed>
     */
    private function uploadData(string $name = 'photo.jpg', int $error = UPLOAD_ERR_OK): array
    {
        return [
            'name'      => $name,
            'full_path' => $name,
            'type'      => 'image/jpeg',
            'tmp_name'  => '/tmp/' . $name,
            'error'     => $error,
            'size'      => '123',
        ];
    }
}
