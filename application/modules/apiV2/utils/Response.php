<?php

/**
 * @OA\Schema(
 *     title="Api Response"
 * )
 */
class Util_Response {
    /**
     * @OA\Property(
     *     description="was the request successful",
     *     type="boolean"
     * )"
     */
    protected $success;

    /**
     * @OA\Property(
     *     description="description of the response",
     *     type="string"
     * )"
     */
    protected $message;

    /**
     * @OA\Property(
     *     description="addtional data",
     *     type="mixed"
     * )"
     */
    protected $data;
}