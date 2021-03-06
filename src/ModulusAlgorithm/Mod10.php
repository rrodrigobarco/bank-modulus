<?php

namespace Cs278\BankModulus\ModulusAlgorithm;

final class Mod10 extends BaseAlgorithm
{
    /**
     * @param string $input
     * @param array  $weights
     */
    public function __construct($input, array $weights)
    {
        $result = array_sum(array_map(function ($a, $b) {
            return $a * $b;
        }, self::toChars($input), $weights));
        assert(is_int($result));

        parent::__construct(
            $result,
            10
        );
    }
}
