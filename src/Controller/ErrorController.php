<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorController extends AbstractController
{
    public function show(\Throwable $exception): Response
    {
        $statusCode = $exception instanceof HttpException ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        return $this->render('error/index.html.twig', [
            'status_code' => $statusCode,
            'message' => $statusCode === 404
                ? 'Page not found'
                : ($statusCode === 403 ? 'Access denied' : 'An error occurred'),
            'exception' => $exception,
        ]);
    }
}