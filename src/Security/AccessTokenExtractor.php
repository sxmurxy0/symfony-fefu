<?php

declare(strict_types=1);

namespace App\Security;

use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

class AccessTokenExtractor implements AccessTokenExtractorInterface
{
    #[Override]
    public function extractAccessToken(Request $request): ?string
    {
        $authorizationHeader = $request->headers->get('Authorization');

        if (null === $authorizationHeader) {
            return null;
        }

        if (str_starts_with($authorizationHeader, 'Bearer ')) {
            return substr($authorizationHeader, 7);
        }

        return $authorizationHeader;
    }
}
