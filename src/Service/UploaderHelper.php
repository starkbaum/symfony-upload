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

    private $filesystem;
    private $requestStackContext;
    private $logger;

    /**
     * UploaderHelper constructor.
     * @param FilesystemInterface $publicUploadFilesystem
     * @param RequestStackContext $requestStackContext
     * @param LoggerInterface $logger
     */
    public function __construct(FilesystemInterface $publicUploadFilesystem, RequestStackContext $requestStackContext, LoggerInterface $logger)
    {

        $this->filesystem = $publicUploadFilesystem;
        $this->requestStackContext = $requestStackContext;
        $this->logger = $logger;
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
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME) . '-' . uniqid()) . '.' . $file->guessExtension();

        $stream = fopen($file->getPathname(), 'r');

        $result = $this->filesystem->writeStream(
            self::ARTICLE_IMAGE . '/' . $newFilename,
            $stream
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

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