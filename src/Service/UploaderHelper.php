<?php


namespace App\Service;


use Exception;
use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    const ARTICLE_IMAGE = 'article_images';
    const ARTICLE_REFERENCE = 'article_reference';

    private $filesystem;
    private $privateFilesystem;
    private $requestStackContext;
    private $logger;
    private $publicAssetBaseUrl;

    /**
     * UploaderHelper constructor.
     * @param FilesystemInterface $publicUploadFilesystem
     * @param FilesystemInterface $privateUploadFilesystem
     * @param RequestStackContext $requestStackContext
     * @param LoggerInterface $logger
     * @param string $uploadedAssetsBaseUrl
     */
    public function __construct(
        FilesystemInterface $publicUploadFilesystem,
        FilesystemInterface $privateUploadFilesystem,
        RequestStackContext $requestStackContext,
        LoggerInterface $logger,
        string $uploadedAssetsBaseUrl
    )
    {
        $this->filesystem = $publicUploadFilesystem;
        $this->privateFilesystem = $privateUploadFilesystem;
        $this->requestStackContext = $requestStackContext;
        $this->logger = $logger;
        $this->publicAssetBaseUrl = $uploadedAssetsBaseUrl;
    }

    /**
     * @param File $file
     * @param string|null $existingFilename
     * @return string
     * @throws FileExistsException
     * @throws Exception
     */
    public function uploadArticleImage(File $file, ?string $existingFilename): string
    {
        $newFilename = $this->uploadFile($file, self::ARTICLE_IMAGE, true);

        if ($existingFilename) {
            try {
                $result = $this->filesystem->delete(self::ARTICLE_IMAGE . '/' . $existingFilename);

                if ($result === false) {
                    throw new Exception(sprintf(
                        'Could not delete old uploaded file "%s"'
                        , $existingFilename
                    ));
                }
            } catch (FileNotFoundException $fileNotFoundException) {
                $this->logger->alert(sprintf(
                    'Old uploaded file "%s" was missing when trying to delete',
                    $existingFilename
                ));
            }
        }


        return $newFilename;
    }


    public function uploadArticleReference(File $file): string
    {
        return $this->uploadFile($file, self::ARTICLE_REFERENCE, false);
    }

    /**
     * @param string $path
     * @return string
     */
    public function getPublicPath(string $path): string
    {
        return $this->requestStackContext
            ->getBasePath() . $this->publicAssetBaseUrl . '/' . $path;
    }

    /**
     * @param File $file
     * @param string $directory
     * @param bool $isPublic
     * @return string
     * @throws FileExistsException
     */
    private function uploadFile(File $file, string $directory, bool $isPublic): string
    {
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME) . '-' . uniqid()) . '.' . $file->guessExtension();

        $filesystem = $isPublic ? $this->filesystem : $this->privateFilesystem;

        $stream = fopen($file->getPathname(), 'r');

        $result = $filesystem->writeStream(
            $directory . '/' . $newFilename,
            $stream
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $newFilename;
    }
}