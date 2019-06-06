
# Integration

To add a new endpoint to the API you need to create a controller class in your module
at the `api/` folder which contains a class using specific annotations. The controller
must implement the `ApiControllerInterface` interface. Every method can contain specific
annotations to describe whether it can be called through a HTTP request.

```
<?php

use Kajona\Api\System\ApiControllerInterface;

class MyController implements ApiControllerInterface
{
    /**
     * @api
     * @method GET
     * @path /my/endpoint
     */
    public function myEndpoint()
    {
        return [
            "Hello" => "World!",
        ];
    }
}

```

