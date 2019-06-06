<?php

$config["providers"] = [];

/*
$config["providers"][0] = [
    "name" => "Winsrv01",
    "client_id" => "824e247e-9557-4e20-854a-83292f8dd84f", // the Id of the Client wanting an access token, as regiestered in the ClientId parameter when registering the Client in ADFS.
    "resource" => "https://192.168.60.209", // The resource server that the Client wants an access token to, as registered in the Identifier parameter of the Relying Party trust
    "redirect_uri" => "http://192.168.60.209/agp/authorization/callback", // The redirect uri that is associated with the Client. Must match the RedirectUri value associated with the Client in ADFS.
    "authorization_url" => "https://winsrv01.artemeon.de/adfs/oauth2/authorize",
    "token_url" => "https://winsrv01.artemeon.de/adfs/oauth2/token",
    "cert_file" => null,
    "claim_mapping" => [
        \Kajona\Oauth2\System\ProviderManager::CLAIM_USERNAME => "unique_name",
        \Kajona\Oauth2\System\ProviderManager::CLAIM_EMAIL => "email",
        \Kajona\Oauth2\System\ProviderManager::CLAIM_FIRSTNAME => "first_name",
        \Kajona\Oauth2\System\ProviderManager::CLAIM_LASTNAME => "last_name",
    ],
    'redirect_detector' => new DefaultRedirectDetector()
];
*/

$config["https_verify"] = true;
