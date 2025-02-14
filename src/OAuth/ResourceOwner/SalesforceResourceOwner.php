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

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Tyler Pugh <tylerism@gmail.com>
 */
final class SalesforceResourceOwner extends GenericOAuth2ResourceOwner
{
    /**
     * {@inheritdoc}
     */
    protected array $paths = [
        'identifier' => 'user_id',
        'nickname' => 'nick_name',
        'realname' => 'nick_name',
        'email' => 'email',
        'profilepicture' => 'photos.picture',
    ];

    /**
     * {@inheritdoc}
     */
    public function getUserInformation(array $accessToken, array $extraParameters = [])
    {
        // SalesForce returns the infos_url in the OAuth Response Token
        $this->options['infos_url'] = $accessToken['id'];

        return parent::getUserInformation($accessToken, $extraParameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetUserInformationRequest($url, array $parameters = [])
    {
        // Salesforce requires format parameter in order for API to return json response
        $url = $this->normalizeUrl($url, [
            'format' => $this->options['format'],
        ]);

        // Salesforce require to pass the OAuth token as 'oauth_token' instead of 'access_token'
        $url = str_replace('access_token', 'oauth_token', $url);

        return $this->httpRequest($url);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'sandbox' => false,
            'authorization_url' => 'https://login.salesforce.com/services/oauth2/authorize',
            'access_token_url' => 'https://login.salesforce.com/services/oauth2/token',

            // @see SalesforceResourceOwner::getUserInformation()
            'infos_url' => null,

            // @see SalesforceResourceOwner::doGetUserInformationRequest()
            'format' => 'json',
        ]);

        $sandboxTransformation = function (Options $options, $value) {
            if (!$options['sandbox']) {
                return $value;
            }

            return preg_replace('~login\.~', 'test.', $value, 1);
        };

        $resolver
            ->setNormalizer('authorization_url', $sandboxTransformation)
            ->setNormalizer('access_token_url', $sandboxTransformation)
        ;

        $resolver->addAllowedTypes('sandbox', 'bool');
    }
}
