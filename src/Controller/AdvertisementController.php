<?php

namespace App\Controller;

use App\Entity\Advertisement;
use App\Entity\AdvertisementImage;
use App\Form\AdvertisementFormType;
use App\Service\ImageUploader;
use App\Service\LocationProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/advertisement')]
class AdvertisementController extends AbstractController
{
    private function canManageAdvertisement(Advertisement $advertisement): bool
    {
        return $this->isGranted('ROLE_MODERATOR') ||
            ($this->getUser() === $advertisement->getUser());
    }

    #[Route('/', name: 'app_advertisement_index')]
    public function index(): Response
    {
        return $this->render('advertisement/index.html.twig', [
            'advertisements' => $this->getUser()->getAdvertisements(),
        ]);
    }

    #[Route('/new', name: 'app_advertisement_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        LocationProvider $locationProvider,
        ImageUploader $imageUploader,
    ): Response {
        $this->denyAccessUnlessGranted('create_ad');

        $advertisement = new Advertisement();
        $form = $this->createForm(AdvertisementFormType::class, $advertisement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the user
            $advertisement->setUser($this->getUser());

            $advertisement->setStatus('active');

            /** @var UploadedFile[] $imageFiles */
            $imageFiles = $form->get('images')->getData();

            foreach ($imageFiles as $position => $imageFile) {
                try {
                    $newFilename = $imageUploader->upload($imageFile);

                    $image = new AdvertisementImage();
                    $image->setFilename($newFilename);
                    $image->setPosition($position);
                    $image->setAdvertisement($advertisement);

                    $entityManager->persist($image);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }

            // Set timestamps
            $advertisement->setCreatedAt(new \DateTimeImmutable());
            $advertisement->setUpdatedAt(new \DateTime());


            $entityManager->persist($advertisement);
            $entityManager->flush();

            $this->addFlash('success', 'Advertisement created successfully!');
            return $this->redirectToRoute('app_advertisement_show', ['id' => $advertisement->getId()]);
        }

        return $this->render('advertisement/new.html.twig', [
            'form' => $form,
            'locations' => $locationProvider->getAllLocations()
        ]);
    }

    #[Route('/{id}', name: 'app_advertisement_show', requirements: ['id' => '\d+'])]
    public function show(Advertisement $advertisement): Response
    {
        return $this->render('advertisement/show.html.twig', [
            'advertisement' => $advertisement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_advertisement_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Advertisement $advertisement,
        EntityManagerInterface $entityManager,
        LocationProvider $locationProvider,
        ImageUploader $imageUploader
    ): Response {
        $this->denyAccessUnlessGranted('edit_ad', $advertisement);

        if (!$this->canManageAdvertisement($advertisement)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AdvertisementFormType::class, $advertisement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle new image uploads
            /** @var UploadedFile[] $imageFiles */
            $imageFiles = $form->get('images')->getData();

            foreach ($imageFiles as $position => $imageFile) {
                try {
                    $newFilename = $imageUploader->upload($imageFile);

                    $image = new AdvertisementImage();
                    $image->setFilename($newFilename);
                    $image->setPosition($position + count($advertisement->getAdvertisementImages()));
                    $image->setAdvertisement($advertisement);

                    $entityManager->persist($image);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }

            $advertisement->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Advertisement updated successfully!');
            return $this->redirectToRoute('app_advertisement_show', ['id' => $advertisement->getId()]);
        }

        return $this->render('advertisement/edit.html.twig', [
            'advertisement' => $advertisement,
            'form' => $form,
            'locations' => $locationProvider->getAllLocations()
        ]);
    }

    #[Route('/{id}/delete', name: 'app_advertisement_delete', methods: ['POST'])]
    public function delete(Request $request, Advertisement $advertisement, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canManageAdvertisement($advertisement)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$advertisement->getId(), $request->getPayload()->getString('_token'))) {
            // Delete images from filesystem
            foreach ($advertisement->getAdvertisementImages() as $image) {
                $imagePath = $this->getParameter('advertisements_images_directory').'/'.$image->getFilename();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($advertisement);
            $entityManager->flush();

            $this->addFlash('success', 'Advertisement was deleted successfully.');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/image/{id}/delete', name: 'app_advertisement_delete_image', methods: ['POST'])]
    public function deleteImage(
        AdvertisementImage $image,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $advertisement = $image->getAdvertisement();

        if (!$this->canManageAdvertisement($advertisement)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->getPayload()->getString('_token'))) {
            // Delete physical file
            $imagePath = $this->getParameter('advertisements_images_directory').'/'.$image->getFilename();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }

            $entityManager->remove($image);
            $entityManager->flush();

            $this->addFlash('success', 'Image deleted successfully.');
        }

        return $this->redirectToRoute('app_advertisement_edit', ['id' => $advertisement->getId()]);
    }
}
