<?php
namespace Vendimia;

/**
 * Interface for object that can be converted to array
 */
interface AsArrayInterface
{
    /**
     * Returns an array representation of this object
     */
    public function asArray(): array;
}
