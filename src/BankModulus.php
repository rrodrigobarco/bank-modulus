<?php

namespace Cs278\BankModulus;

use Cs278\BankModulus\BankAccountNormalizer\DefaultNormalizer;
use Cs278\BankModulus\BankAccountNormalizer\NormalizerInterface;
use Cs278\BankModulus\Exception\CannotValidateException;
use Cs278\BankModulus\Exception\Util as E;
use Cs278\BankModulus\Spec\SpecInterface;
use Cs278\BankModulus\Spec\VocaLinkV390;
use Webmozart\Assert\Assert;

/**
 * Simple class to validate UK bank account details.
 *
 * This wraps around the low level API to provide a simple interface for third-party
 * integrations.
 */
final class BankModulus
{
    private $spec;
    private $normalizer;

    /**
     * Constructor.
     *
     * @param SpecInterface       $spec       Banking specification to check against.
     * @param NormalizerInterface $normalizer Strategy to normalize account numbers/sort codes.
     */
    public function __construct(SpecInterface $spec = null, NormalizerInterface $normalizer = null)
    {
        $this->spec = $spec ?: new VocaLinkV390();
        $this->normalizer = $normalizer ?: new DefaultNormalizer();
    }

    /**
     * Normalize the supplied sort code and account number.
     *
     * The result is returned by reference.
     *
     * @param string $sortCode
     * @param string $accountNumber
     */
    public function normalize(&$sortCode, &$accountNumber)
    {
        try {
            Assert::string($sortCode, 'Sort code must be a string');
            Assert::string($accountNumber, 'Account number must be a string');
        } catch (\InvalidArgumentException $e) {
            throw E::wrap($e);
        }

        $account = new BankAccount($sortCode, $accountNumber);
        $account = $this->normalizeBankAccount($account);

        $sortCode = $account->getSortCode()->format('%s%s%s');
        $accountNumber = $account->getAccountNumber();
    }

    /**
     * Check if account number / sort code are not invalid.
     *
     * If the specification cannot validate the bank account they are assumed
     * to be valid.
     *
     * @param string $sortCode
     * @param string $accountNumber
     *
     * @return bool True if the details are valid or not known to be invalid
     */
    public function check($sortCode, $accountNumber)
    {
        try {
            Assert::string($sortCode, 'Sort code must be a string');
            Assert::string($accountNumber, 'Account number must be a string');
        } catch (\InvalidArgumentException $e) {
            throw E::wrap($e);
        }

        $result = $this->lookup($sortCode, $accountNumber);

        if ($result->isValidated()) {
            return $result->isValid();
        }

        return true;
    }

    /**
     * Perform evaluation of the supplied sort code and account number.
     *
     * This will normalize the supplied input and then perform modulus check.
     *
     * @param string $sortCode
     * @param string $accountNumber
     *
     * @return Result
     */
    public function lookup($sortCode, $accountNumber)
    {
        try {
            Assert::string($sortCode, 'Sort code must be a string');
            Assert::string($accountNumber, 'Account number must be a string');
        } catch (\InvalidArgumentException $e) {
            throw E::wrap($e);
        }

        $account = new BankAccount($sortCode, $accountNumber);
        $account = $this->normalizeBankAccount($account);

        try {
            $valid = $this->spec->check($account);
            $validated = true;
        } catch (CannotValidateException $e) {
            $validated = false;
            $valid = null;
        }

        return new Result($account, $validated, $valid);
    }

    private function normalizeBankAccount(BankAccountInterface $account)
    {
        if ($this->normalizer->supports($account)) {
            return $this->normalizer->normalize($account);
        }

        return BankAccountNormalized::createFromBankAccount($account);
    }
}