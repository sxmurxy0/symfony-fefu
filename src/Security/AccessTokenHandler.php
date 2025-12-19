<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\AccessTokenRepository;
use Override;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private AccessTokenRepository $repository
    ) {
    }

    #[Override]
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $accessTokenEntity = $this->repository->findOneByValue($accessToken);
        if (!$accessTokenEntity) {
            throw new BadCredentialsException('Invalid access token!');
        } elseif (!$accessTokenEntity->isValid()) {
            throw new BadCredentialsException('Access token has expired!');
        }

        return new UserBadge($accessTokenEntity->getUser()->getUserIdentifier());
    }
}
