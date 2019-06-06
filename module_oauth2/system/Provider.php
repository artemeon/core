<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Oauth2\System;

/**
 * @author christoph.kappestein@artemeon.de
 * @since 7.0
 */
class Provider
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $resource;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var string
     */
    private $authorizationUrl;

    /**
     * @var string
     */
    private $tokenUrl;

    /**
     * @var string
     */
    private $certFile;

    /**
     * @var array
     */
    private $claimMapping;

    /**
     * @var RedirectDetectorInterface
     */
    private $redirectDetector;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId(string $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param string $resource
     */
    public function setResource(string $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @param string $redirectUri
     */
    public function setRedirectUri(string $redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * @return string
     */
    public function getAuthorizationUrl()
    {
        return $this->authorizationUrl;
    }

    /**
     * @param string $authorizationUrl
     */
    public function setAuthorizationUrl(string $authorizationUrl)
    {
        $this->authorizationUrl = $authorizationUrl;
    }

    /**
     * @return string
     */
    public function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * @param string $tokenUrl
     */
    public function setTokenUrl(string $tokenUrl)
    {
        $this->tokenUrl = $tokenUrl;
    }

    /**
     * @return string
     */
    public function getCertFile()
    {
        return $this->certFile;
    }

    /**
     * @param string $certFile
     */
    public function setCertFile($certFile)
    {
        $this->certFile = $certFile;
    }

    /**
     * @return array
     */
    public function getClaimMapping()
    {
        return $this->claimMapping;
    }

    /**
     * @param array $claimMapping
     */
    public function setClaimMapping($claimMapping)
    {
        $this->claimMapping = $claimMapping;
    }

    /**
     * @return RedirectDetectorInterface
     */
    public function getRedirectDetector(): RedirectDetectorInterface
    {
        return $this->redirectDetector;
    }

    /**
     * @param RedirectDetectorInterface $redirectDetector
     */
    public function setRedirectDetector(RedirectDetectorInterface $redirectDetector)
    {
        $this->redirectDetector = $redirectDetector;
    }


}

