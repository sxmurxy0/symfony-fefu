<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AccessToken;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Override;

/**
 * @extends AbstractCrudController<AccessToken>
 */
class AccessTokenCrudController extends AbstractCrudController
{
    #[Override]
    public static function getEntityFqcn(): string
    {
        return AccessToken::class;
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('createdAt')
            ->add('expiresAt');
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('value')->hideOnForm(),
            AssociationField::new('user'),
            DateTimeField::new('createdAt')
                ->setFormat(DateTimeField::FORMAT_MEDIUM)->hideOnForm(),
            DateTimeField::new('expiresAt')
                ->setFormat(DateTimeField::FORMAT_MEDIUM)
        ];
    }
}
