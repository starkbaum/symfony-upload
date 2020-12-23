<?php


namespace App\Service;


use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    const ARTICLE_IMAGE = 'article_images';

    private $filesystem;
    private $requestStackContext;

    public function __construct(FilesystemInterface $publicUploadFilesystem, RequestStackContext $requestStackContext)
    {

        $this->filesystem = $publicUploadFilesystem;
        $this->requestStackContext = $requestStackContext;
    }

    /**
     * @param File $file
     * @return string
     */
    public function uploadArticleImage(File $file): string
    {
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME) . '-' . uniqid()) . '.' . $file->guessExtension();

        $this->filesystem->write(
            self::ARTICLE_IMAGE . '/' . $newFilename,
            file_get_contents($file->getPathname())
        );


        return $newFilename;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getPublicPath(string $path): string
    {
        return $this->requestStackContext
            ->getBasePath() . '/uploads/' . $path;
    }

}