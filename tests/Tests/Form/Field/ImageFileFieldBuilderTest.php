<?php

namespace Dms\Core\Tests\Form\Field;

use Dms\Core\File\IUploadedFile;
use Dms\Core\File\IUploadedImage;
use Dms\Core\Form\Field\Builder\Field as Field;
use Dms\Core\Form\Field\Builder\ImageFieldBuilder;
use Dms\Core\Form\Field\Processor\FileMoverProcessor;
use Dms\Core\Form\Field\Processor\Validator\ImageDimensionsValidator;
use Dms\Core\Form\Field\Processor\Validator\ImageValidator;
use Dms\Core\Form\Field\Processor\Validator\TypeValidator;
use Dms\Core\Form\Field\Processor\Validator\UploadedFileValidator;
use Dms\Core\Form\Field\Processor\Validator\UploadedImageValidator;
use Dms\Core\Form\Field\Type\ImageType;
use Dms\Core\Language\Message;
use Dms\Core\Model\Type\Builder\Type;
use Dms\Core\Model\Type\ObjectType;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ImageFileFieldBuilderTest extends FileFieldBuilderTest
{
    /**
     * @param string $name
     * @param string $label
     *
     * @return ImageFieldBuilder
     */
    protected function field($name = 'name', $label = 'Name')
    {
        return Field::name($name)->label($label)->image();
    }

    protected function mockUploadedImage($width, $height, $size = 100, $extension = 'png')
    {
        $file = $this->getMockForAbstractClass(IUploadedImage::class);

        $file->expects($this->any())
                ->method('isValidImage')
                ->willReturn(true);

        $file->expects($this->any())
                ->method('getWidth')
                ->willReturn($width);

        $file->expects($this->any())
                ->method('getHeight')
                ->willReturn($height);

        $file->expects($this->any())
                ->method('getSize')
                ->willReturn($size);

        $file->expects($this->any())
                ->method('getExtension')
                ->willReturn($extension);

        $file->expects($this->any())
                ->method('hasUploadedSuccessfully')
                ->willReturn(true);

        $file->expects($this->any())
                ->method('getUploadError')
                ->willReturn(UPLOAD_ERR_OK);

        return $file;
    }

    public function testFieldWithProcessors()
    {
        $field = $this->field()
                ->minWidth(100)->maxWidth(500)
                ->minHeight(200)->maxHeight(1000)
                ->build();

        $this->assertEquals([
                new TypeValidator(Type::object(IUploadedImage::class)->nullable()),
                new UploadedFileValidator(Type::object(IUploadedImage::class)->nullable()),
                new UploadedImageValidator(Type::object(IUploadedImage::class)->nullable()),
                new ImageDimensionsValidator(Type::object(IUploadedImage::class)->nullable(), 100, 500, 200, 1000),
        ], $field->getProcessors());

        $this->assertSame(100, $field->getType()->get(ImageType::ATTR_MIN_WIDTH));
        $this->assertSame(500, $field->getType()->get(ImageType::ATTR_MAX_WIDTH));
        $this->assertSame(200, $field->getType()->get(ImageType::ATTR_MIN_HEIGHT));
        $this->assertSame(1000, $field->getType()->get(ImageType::ATTR_MAX_HEIGHT));
        $this->assertSame(null, $field->getType()->get(ImageType::ATTR_EXTENSIONS));

        $validFile = $this->mockUploadedImage(100, 500);
        $this->assertSame($validFile, $field->process($validFile));

        $invalidFile = $this->mockUploadedImage(50, 500);
        $this->assertFieldThrows($field, $invalidFile, [
                new Message(ImageDimensionsValidator::MESSAGE_MIN_WIDTH, [
                        'field'     => 'Name',
                        'input'     => $invalidFile,
                        'min_width' => 100,
                ])
        ]);

        $this->assertEquals(Type::object(IUploadedImage::class)->nullable(), $field->getProcessedType());
    }
}