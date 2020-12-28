<?php


namespace App\Controller;


use App\Entity\Article;
use App\Entity\ArticleReference;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArticleReferenceAdminController extends BaseController
{
    /**
     * @Route("/admin/article/{id}/reference", name="admin_article_add_reference", methods={"POST"})
     * @param Article $article
     * @param Request $request
     * @param UploaderHelper $uploaderHelper
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @return JsonResponse|RedirectResponse
     */
    public function uploadArticleReference(
        Article $article,
        Request $request,
        UploaderHelper $uploaderHelper,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    )
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('reference');

        //dump($uploadedFile);

        $violations = $validator->validate(
            $uploadedFile,
            [
                new File([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'image/*',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/plain'
                    ],
                ]),
                new NotNull([
                    'message' => 'Please select a file to upload!',
                ]),
            ]
        );

        if ($violations->count() > 0) {
            return $this->json($violations, 400);
        }

        $filename = $uploaderHelper->uploadArticleReference($uploadedFile);

        $articleReference = new ArticleReference($article);
        $articleReference->setFilename($filename);
        $articleReference->setOriginalFilename($uploadedFile->getClientOriginalName() ?? $filename);
        $articleReference->setMimeType($uploadedFile->getMimeType() ?? 'application/octet-stream');

        $entityManager->persist($articleReference);
        $entityManager->flush();

        return $this->json(
            $articleReference,
            201,
            [],
            [
                'groups' => ['main'],
            ]
        );
    }

    /**
     * @Route("/admin/article/{id}/references", name="admin_article_list_references", methods="GET")
     * @param Article $article
     * @return JsonResponse
     */
    public function getArticleReference(Article $article): JsonResponse
    {
        return $this->json(
            $article->getArticleReferences(),
            200,
            [],
            [
                'groups' => 'main',
            ]
        );
    }

    /**
     * @Route("/admin/article/references/{id}/download", name="admin_article_download_reference", methods="GET")
     * @param ArticleReference $articleReference
     * @param UploaderHelper $uploaderHelper
     * @return StreamedResponse
     */
    public function downloadArticleReference(ArticleReference $articleReference, UploaderHelper $uploaderHelper)
    {
        $article = $articleReference->getArticle();

        //$this->denyAccessUnlessGranted('MANAGE');

        $response = new StreamedResponse(function () use ($articleReference, $uploaderHelper) {
            $outputStream = fopen('php://output', 'wb');
            $fileStream = $uploaderHelper->readStream($articleReference->getFilePath(), false);

            stream_copy_to_stream($fileStream, $outputStream);
        });

        $response->headers->set('Content-Type', $articleReference->getMimeType());
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $articleReference->getOriginalFilename()
        );

        //dd($disposition);

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/admin/article/references/{id}", name="admin_article_update_reference", methods="PUT")
     * @param ArticleReference $articleReference
     * @param UploaderHelper $uploaderHelper
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function updateArticleReference(
        ArticleReference $articleReference,
        UploaderHelper $uploaderHelper,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Request $request,
        ValidatorInterface $validator
    )
    {
        $serializer->deserialize(
            $request->getContent(),
            ArticleReference::class,
            'json',
            [
                'object_to_populate' => $articleReference,
                'groups' => ['input']

            ]
        );

        $violations = $validator->validate($articleReference);
        if ($violations->count() > 0) {
            return $this->json($violations, 400);
        }

        $entityManager->persist($articleReference);
        $entityManager->flush();

        return $this->json(
            $articleReference,
            200,
            [],
            [
                'groups' => ['main']
            ]
        );
    }

    /**
     * @Route("/admin/article/references/{id}", name="admin_article_delete_references", methods="DELETE")
     * @param ArticleReference $articleReference
     * @param UploaderHelper $uploaderHelper
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws FileNotFoundException
     */
    public function deleteArticleReference(
        ArticleReference $articleReference,
        UploaderHelper $uploaderHelper,
        EntityManagerInterface $entityManager
    )
    {
        $entityManager->remove($articleReference);
        $entityManager->flush();

        $uploaderHelper->deleteFile($articleReference->getFilePath(), false);

        return new Response(null, 204);
    }
}