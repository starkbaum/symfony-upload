<?php


namespace App\Service;


use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    const ARTICLE_IMAGE = 'article_images';

    private $uploadPath;
    private $requestStackContext;

    public function __construct(string $uploadPath, RequestStackContext $requestStackContext)
    {

        $this->uploadPath = $uploadPath;
        $this->requestStackContext = $requestStackContext;
    }

    /**
     * @param File $file
     * @return string
     */
    public function uploadArticleImage(File $file): string
    {
        $destination = $this->uploadPath . '/' . self::ARTICLE_IMAGE;

        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME) . '-' . uniqid()) . '.' . $file->guessExtension();

        $file->move(
            $destination,
            $newFilename
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