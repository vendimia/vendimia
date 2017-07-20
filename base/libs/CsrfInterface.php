<?php
namespace Vendimia;

interface CsrfInterface
{
    /**
     * Generates a new CSRF token
     */
    public function generateToken();

    /**
     * Obtains the generated token
     */
    public function getToken();

    /**
     * Validates a CSRF token agains the one stored.
     */
    public function validate($source = null);
}