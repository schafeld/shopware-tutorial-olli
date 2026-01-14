<?php declare(strict_types=1);

namespace Learning\Bundle\Controller;

use Learning\Bundle\Exception\ProductViewException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/learning/test-errors")
 */
class TestErrorController extends AbstractController 
{
    /**
     * @Route("/product-not-found", name="learning.test.product_not_found", methods={"GET"})
     */
    public function testProductNotFound(): JsonResponse
    {
        throw ProductViewException::productNotFound('test-product-id');
    }

    /**
     * @Route("/invalid-data", name="learning.test.invalid_data", methods={"GET"})
     */
    public function testInvalidData(): JsonResponse
    {
        throw ProductViewException::invalidViewData('Test invalid data error');
    }

    /**
     * @Route("/database-error", name="learning.test.database_error", methods={"GET"})
     */
    public function testDatabaseError(): JsonResponse
    {
        $previous = new \PDOException('Test Database connection failed');
        throw ProductViewException::databaseError($previous);
    }

    /**
     * @Route("/http-error", name="learning.test.http_error", methods={"GET"})
     */
    public function testHttpError(): JsonResponse
    {
        throw new NotFoundHttpException('This is a test 404 error');
    }

    /**
     * @Route("/fatal-error", name="learning.test.fatal_error", methods={"GET"})
     */
    public function testFatalError(): JsonResponse
    {
        // Trigger a fatal error
        $array = [];
        $array['undefined_key']->nonExistentMethod();
        return new JsonResponse(['success' => true]);
    }
}