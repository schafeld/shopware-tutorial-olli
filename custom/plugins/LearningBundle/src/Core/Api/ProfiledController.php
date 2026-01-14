<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class ProfiledController extends AbstractController
{
    private Stopwatch $stopwatch;

    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * @Route("/api/_action/learning/profiled-operation", 
     *  name="api.action.learning.profiled",
     *  methods={"GET"}
     * )
     */
    public function profiledOperation(Request $request): JsonResponse
    {
        // Start profiling
        $this->stopwatch->start('complex_operation');

        // Simulate a complex operation
        $this->stopwatch->start('database_query');
        usleep(50000); // 50 ms
        $this->stopwatch->stop('database_query');

        $this->stopwatch->start('external_api_call');
        usleep(100000); // 100 ms
        $this->stopwatch->stop('external_api_call');

        // Stop profiling
        $event = $this->stopwatch->stop('complex_operation');

        return new JsonResponse([
            'success' => true,
            'profiling' => [
                'duration_ms' => $event->getDuration(),
                'memory_mb' => $event->getMemory() / 1024 / 1024,
            ]
        ]);
    }
}