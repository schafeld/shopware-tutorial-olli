<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Controller;

use GotoWebinarGoogleSheetsExport\Service\CsvExportService;
use GotoWebinarGoogleSheetsExport\Service\GoogleSheetsService;
use GotoWebinarGoogleSheetsExport\Service\OrderExportService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Admin API controller for manual exports and OAuth management
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class AdminApiController extends AbstractController
{
    public function __construct(
        private readonly GoogleSheetsService $googleSheetsService,
        private readonly OrderExportService $orderExportService,
        private readonly CsvExportService $csvExportService,
        private readonly EntityRepository $exportRepository
    ) {
    }

    /**
     * Generate Google OAuth authorization URL
     */
    #[Route(
        path: '/api/_action/gotowebinar-sheets/oauth/authorize',
        name: 'api.action.gotowebinar_sheets.oauth.authorize',
        methods: ['POST']
    )]
    public function getAuthUrl(Request $request): JsonResponse
    {
        try {
            $redirectUri = $request->request->get('redirectUri');
            
            if (!$redirectUri) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Redirect URI is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            $authUrl = $this->googleSheetsService->getAuthorizationUrl($redirectUri);

            return new JsonResponse([
                'success' => true,
                'authUrl' => $authUrl
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle OAuth callback and exchange code for tokens
     */
    #[Route(
        path: '/api/_action/gotowebinar-sheets/oauth/callback',
        name: 'api.action.gotowebinar_sheets.oauth.callback',
        methods: ['POST']
    )]
    public function handleOAuthCallback(Request $request): JsonResponse
    {
        try {
            $code = $request->request->get('code');
            $redirectUri = $request->request->get('redirectUri');

            if (!$code || !$redirectUri) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Authorization code and redirect URI are required'
                ], Response::HTTP_BAD_REQUEST);
            }

            $token = $this->googleSheetsService->authenticate($code, $redirectUri);

            return new JsonResponse([
                'success' => true,
                'message' => 'Successfully connected to Google Sheets'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Trigger manual export
     */
    #[Route(
        path: '/api/_action/gotowebinar-sheets/export/manual',
        name: 'api.action.gotowebinar_sheets.export.manual',
        methods: ['POST']
    )]
    public function manualExport(Request $request, Context $context): JsonResponse
    {
        try {
            if (!$this->googleSheetsService->isConfigured()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Google Sheets integration is not configured'
                ], Response::HTTP_BAD_REQUEST);
            }

            $limit = (int) ($request->request->get('limit') ?? 50);
            
            // This logic is duplicated from the scheduled task handler
            // Consider extracting to a service method
            $pendingExports = $this->orderExportService->getPendingExports($context, $limit);

            if (empty($pendingExports)) {
                return new JsonResponse([
                    'success' => true,
                    'exported' => 0,
                    'failed' => 0,
                    'message' => 'No pending exports found'
                ]);
            }

            $rows = [];
            $exportMap = [];

            foreach ($pendingExports as $export) {
                $row = [
                    $export->getCustomerFirstName(),
                    $export->getCustomerLastName(),
                    $export->getOrderNumber(),
                    $export->getProductNumber(),
                    $export->getSalesChannelName(),
                    $export->getCustomerEmail(),
                ];

                $rows[] = $row;
                $exportMap[] = $export->getId();
            }

            // Get config values
            $sheetId = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService')
                ->get('GotoWebinarGoogleSheetsExport.config.googleSheetId');
            $worksheetName = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService')
                ->get('GotoWebinarGoogleSheetsExport.config.worksheetName') ?? 'Bestellungen';

            if (!$sheetId) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Google Sheet ID is not configured'
                ], Response::HTTP_BAD_REQUEST);
            }

            try {
                $this->googleSheetsService->appendRows($sheetId, $worksheetName, $rows);

                foreach ($exportMap as $exportId) {
                    $this->orderExportService->updateExportStatus($exportId, 'success', $context);
                }

                return new JsonResponse([
                    'success' => true,
                    'exported' => count($rows),
                    'failed' => 0,
                    'message' => sprintf('Successfully exported %d row(s)', count($rows))
                ]);
            } catch (\Exception $e) {
                foreach ($exportMap as $exportId) {
                    $this->orderExportService->updateExportStatus($exportId, 'failed', $context, $e->getMessage());
                }

                return new JsonResponse([
                    'success' => false,
                    'exported' => 0,
                    'failed' => count($rows),
                    'message' => 'Export failed: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get export statistics
     */
    #[Route(
        path: '/api/_action/gotowebinar-sheets/export/stats',
        name: 'api.action.gotowebinar_sheets.export.stats',
        methods: ['GET']
    )]
    public function getStats(Context $context): JsonResponse
    {
        try {
            $stats = $this->orderExportService->getExportStats($context);

            return new JsonResponse([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Download CSV export
     */
    #[Route(
        path: '/api/_action/gotowebinar-sheets/export/csv',
        name: 'api.action.gotowebinar_sheets.export.csv',
        methods: ['GET']
    )]
    public function downloadCsv(Request $request, Context $context): Response
    {
        try {
            $limit = (int) ($request->query->get('limit') ?? 100);
            
            $exports = $this->orderExportService->getRecentExports($context, $limit);
            $csv = $this->csvExportService->generateCsv($exports);

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="webinar-exports-' . date('Y-m-d') . '.csv"');

            return $response;
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
