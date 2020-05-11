<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops;

use Whoops\Handler\HandlerInterface;

use function method_exists;

trait HandlerOptionsAwareTrait
{
    abstract public function getOptions(): array;

    /**
     * @param HandlerInterface $errorHandler
     */
    private function applyOptions(HandlerInterface $errorHandler): void
    {
        $options = $this->getOptions();

        foreach ($options as $optionName => $optionVal) {
            if (method_exists($errorHandler, $optionName)) {
                $errorHandler->{$optionName}(...(array) $optionVal);
            }
        }
    }
}
