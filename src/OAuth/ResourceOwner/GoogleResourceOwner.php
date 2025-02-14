<?php

/*
 * This file is part of the HWIOAuthBundle package.
 *
 * (c) Hardware Info <opensource@hardware.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HWI\Bundle\OAuthBundle\OAuth\ResourceOwner;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Geoffrey Bachelet <geoffrey.bachelet@gmail.com>
 * @author Alexander <iam.asm89@gmail.com>
 */
final class GoogleResourceOwner extends GenericOAuth2ResourceOwner
{
    /**
     * {@inheritdoc}
     */
    protected array $paths = [
        'identifier' => 'id',
        'nickname' => 'name',
        'realname' => 'name',
        'firstname' => 'given_name',
        'lastname' => 'family_name',
        'email' => 'email',
        'profilepicture' => 'picture',
    ];

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationUrl($redirectUri, array $extraParameters = [])
    {
        $url = parent::getAuthorizationUrl($redirectUri, array_merge([
            'access_type' => $this->options['access_type'],
            'approval_prompt' => $this->options['approval_prompt'],
            'request_visible_actions' => $this->options['request_visible_actions'],
            'prompt' => $this->options['prompt'],
        ], $extraParameters));

        // This parameter have specific value (uses "&" as a separator of domains)
        if (null !== $this->options['hd']) {
            $url .= '&hd='.implode('&', array_map('trim', explode(',', $this->options['hd'])));
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeToken($token)
    {
        $response = $this->httpRequest($this->normalizeUrl($this->options['revoke_token_url'], ['token' => $token]));

        return 200 === $response->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'authorization_url' => 'https://accounts.google.com/o/oauth2/auth',
            'access_token_url' => 'https://accounts.google.com/o/oauth2/token',
            'revoke_token_url' => 'https://accounts.google.com/o/oauth2/revoke',
            'infos_url' => 'https://www.googleapis.com/oauth2/v1/userinfo',

            'scope' => 'https://www.googleapis.com/auth/userinfo.profile',

            'access_type' => null,
            'approval_prompt' => null,
            'display' => null,
            // Identifying a particular hosted domain account to be accessed (for example, 'mycollege.edu')
            'hd' => null,
            'login_hint' => null,
            'prompt' => null,
            'request_visible_actions' => null,
        ]);

        $resolver
            // @link https://developers.google.com/accounts/docs/OAuth2WebServer#offline
            ->setAllowedValues('access_type', ['online', 'offline', null])
            // sometimes we need to force for approval prompt (e.g. when we lost refresh token)
            ->setAllowedValues('approval_prompt', ['force', 'auto', null])
            // @link https://developers.google.com/accounts/docs/OAuth2Login#authenticationuriparameters
            ->setAllowedValues('display', ['page', 'popup', 'touch', 'wap', null])
            ->setAllowedValues('login_hint', ['email address', 'sub', null])
            ->setAllowedValues('prompt', ['consent', 'select_account', null])
        ;
    }
}
