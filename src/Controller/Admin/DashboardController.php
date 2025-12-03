<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AccessToken;
use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Override;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/api/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    #[Override]
    public function index(): Response
    {
        return $this->render('@EasyAdmin/page/content.html.twig');
    }

    #[Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Booking API Admin')
            ->setDefaultColorScheme('light');
    }

    #[Override]
    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::section('Routes'),
            MenuItem::linkToDashboard('Admin Dashboard', 'fa fa-home'),
            MenuItem::linkToRoute('Specification', 'fa-solid fa-share-from-square', 'api_doc'),

            MenuItem::section('Entities'),
            MenuItem::linkToCrud('AccessTokens', 'fa-solid fa-key', AccessToken::class),
            MenuItem::linkToCrud('Users', 'fa fa-user', User::class),
            MenuItem::linkToCrud('Bookings', 'fa-solid fa-calendar', Booking::class),
            MenuItem::linkToCrud('Houses', 'fa-solid fa-house-flag', House::class),

            MenuItem::section('Other'),
            MenuItem::linkToLogout('Logout', 'fa fa-sign-out')
        ];
    }
}
