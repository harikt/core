<?php declare(strict_types = 1);

namespace Dms\Core\File;

use Dms\Core\Exception\InvalidOperationException;

/**
 * The image interface.
 * 
 * @author Elliot Levin <elliot@aanet.com.au>
 */
interface IImage extends IFile
{
    /**
     * Returns whether the file is a valid image
     *
     * @return bool
     */
    public function isValidImage() : bool;

    /**
     * Gets the image width in pixels.
     * 
     * @return int
     * @throws InvalidOperationException if the file is not a valid image
     */
    public function getWidth() : int;

    /**
     * Gets the image height in pixels.
     *
     * @return int
     * @throws InvalidOperationException if the file is not a valid image
     */
    public function getHeight() : int;

    /**
     * {@inheritdoc}
     *
     * @return IImage
     */
    public function moveTo(string $fullPath) : IFile;

    /**
     * {@inheritdoc}
     *
     * @return IImage
     */
    public function copyTo(string $fullPath) : IFile;
}
