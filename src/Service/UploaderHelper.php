<?php


namespace App\Service;


use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Asset\Context\RequestStackContext;
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
     * @param UploadedFile $uploadedFile
     * @return string
     */
    public function uploadArticleImage(UploadedFile $uploadedFile): string
    {
        $destination = $this->uploadPath . '/' . self::ARTICLE_IMAGE;

        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = Urlizer::urlize($originalFilename . '-' . uniqid()) . '.' . $uploadedFile->guessExtension();

        $uploadedFile->move(
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