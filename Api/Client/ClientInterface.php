<?php

namespace Taxjar\SalesTax\Api\Client;

/**
 * Interface for a TaxJar client.
 */
interface ClientInterface
{
    /**
     * Performs an HTTP "GET" request of the given resource
     *
     * @param string $resource
     * @param array $errors
     * @return mixed
     */
    public function getResource($resource, $errors = []);

    /**
     * Performs an HTTP "POST" request of the given resource
     *
     * @param string $resource
     * @param mixed $data
     * @param array $errors
     * @return mixed
     */
    public function postResource($resource, $data, $errors = []);

    /**
     * Performs an HTTP "PUT" request with an existing resource
     *
     * @param string $resource
     * @param string|int $resourceId
     * @param mixed $data
     * @param array $errors
     * @return mixed
     */
    public function putResource($resource, $resourceId, $data, $errors = []);

    /**
     * Performs an HTTP "DELETE" request with an existing resource
     *
     * @param string $resource
     * @param string|int $resourceId
     * @param array $errors
     * @return mixed
     */
    public function deleteResource($resource, $resourceId, $errors = []);

    /**
     * Set the TaxJar API key.
     *
     * @param string $key
     * @return mixed
     */
    public function setApiKey($key);

    /**
     * Toggles response error output.
     *
     * @param bool $toggle
     * @return mixed
     */
    public function showResponseErrors($toggle);
}
