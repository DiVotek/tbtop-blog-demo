<?php

namespace App\Admin;

use App\Admin\Pages\DashboardPage;
use Tbtop\Admin\Navigation\NavGroup;
use Tbtop\Admin\Panels\Panel;
use Tbtop\Admin\Panels\PanelConfig;

class AdminPanel extends Panel
{
    public function configure(PanelConfig $panel): PanelConfig
    {
        return $panel
            ->id('admin')
            ->prefix('admin')
            ->guard('web')
            ->middleware(['web'])
            ->pages([
                DashboardPage::class,
            ])
            ->discoverPages(
                in: app_path('Admin/Pages'),
                for: 'App\\Admin\\Pages',
            )
            ->navigationGroups([
                NavGroup::make('Overview')->icon('home'),
                NavGroup::make('Content')->icon('file-text')->collapsible(),
            ])
            ->rootView('admin')
            ->mcp(['auth:sanctum', 'abilities:tbtop-mcp']);
    }
}
