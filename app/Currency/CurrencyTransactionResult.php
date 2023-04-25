<?php namespace App\Currency;

class CurrencyTransactionResult {

    public static string $success = 'Success';
    public static string $invalidRecipientAddress = 'Transaction has invalid recipient address, it does not belong to us.';
    public static string $invalidTransaction = 'This transaction is not found in blockchain.';
    public static string $doublePrevention = 'This transaction is ours. Use previous transaction id, which is your initial deposit.';
    public static string $invalidCurrency = 'This currency is not supported.';
    public static string $invalidTokenDeposit = 'Token transaction is not found';

}
