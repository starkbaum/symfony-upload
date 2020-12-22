<?php


namespace App\Service;


use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    private $uploadPath;

    public function __construct(string $uploadPath)
    {

        $this->uploadPath = $uploadPath;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return string
     */
    public function uploadArticleImage(UploadedFile $uploadedFile): string
    {
        $destination = $this->uploadPath . '/article_images';

        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = Urlizer::urlize($originalFilename . '-' . uniqid()) . '.' . $uploadedFile->guessExtension();

        $uploadedFile->move(
            $destination,
            $newFilename
        );

        return $newFilename;
    }

}