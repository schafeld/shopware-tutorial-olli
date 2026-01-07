<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

/**
 * Main plugin class for GoTo Webinar Google Sheets Export
 * 
 * This plugin automatically exports order data to Google Sheets when customers
 * purchase products from a configurable category (default: "GotoWebinar").
 */
class GotoWebinarGoogleSheetsExport extends Plugin
{
    /**
     * Called when plugin is installed
     */
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        
        // Migration will create the database table
        // No additional setup needed here
    }

    /**
     * Called when plugin is uninstalled
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        
        // Keep data by default
        if ($uninstallContext->keepUserData()) {
            return;
        }
        
        // If user chooses to remove data, drop the table
        $connection = $this->container->get('Doctrine\DBAL\Connection');
        $connection->executeStatement('DROP TABLE IF EXISTS `gotowebinar_order_export`');
    }

    /**
     * Called when plugin is activated
     */
    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
    }

    /**
     * Called when plugin is deactivated
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
    }
}
