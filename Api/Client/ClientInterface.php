<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Api\Client;

interface ClientInterface
{
    public function getResource($resource, $errors = []);

    public function postResource($resource, $data, $errors = []);

    public function putResource($resource, $resourceId, $data, $errors = []);

    public function deleteResource($resource, $resourceId, $errors = []);

    public function setApiKey($key);

    public function showResponseErrors($toggle);
}
