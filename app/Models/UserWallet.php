<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class UserWallet
{
    private static ?string $lastError = null;

    public static function getLastError(): ?string
    {
        return self::$lastError;
    }

    public static function getByUserId(Config $config, int $userId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM user_wallets WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function ensureExists(Config $config, int $userId): void
    {
        $pdo = Database::pdo($config);
        // Block recruiter wallets at application layer
        try {
            $r = $pdo->prepare('SELECT role FROM users WHERE user_id = :u');
            $r->execute([':u' => $userId]);
            $role = (string)($r->fetchColumn() ?: '');
            if ($role === 'recruiter') {
                return; // do not create wallet
            }
        } catch (\Throwable $e) { /* ignore and proceed best-effort */ }
        // Try insert with defaults; ignore if exists
        $stmt = $pdo->prepare('INSERT IGNORE INTO user_wallets (user_id) VALUES (:uid)');
        $stmt->execute([':uid' => $userId]);
    }

    /**
     * Perform a wallet transaction with balance update and audit in transactions table.
     * amount must be positive. is_earning determines add/subtract.
     * Returns true on success, false if would go negative on spend.
     */
    public static function transact(Config $config, int $userId, string $typeName, int $amount, bool $isEarning, ?string $description = null, ?string $refType = null, ?int $refId = null): bool
    {
        self::$lastError = null;
        // No coin transactions for recruiters
        $pdo = Database::pdo($config);
        try {
            $r = $pdo->prepare('SELECT role FROM users WHERE user_id = :u');
            $r->execute([':u' => $userId]);
            if ((string)($r->fetchColumn() ?: '') === 'recruiter') {
                self::$lastError = 'role_blocked_recruiter';
                return false;
            }
        } catch (\Throwable $e) { /* fall through */ }
        if ($amount <= 0) {
            $amount = abs($amount);
        }
        self::ensureExists($config, $userId);

    $module = ($refType === 'mentorship_request') ? 'mentorship' : 'system';
        try {
            $typeId = TransactionType::ensure($config, $typeName, $amount, $isEarning, $module);
        } catch (\Throwable $e) {
            self::$lastError = 'type_ensure_failed:' . substr($e->getMessage(), 0, 120);
            return false;
        }
        $manageTx = !$pdo->inTransaction();
        if ($manageTx) {
            $pdo->beginTransaction();
        }
        try {
            // Get current balance
            $wallet = self::getByUserId($config, $userId);
            if (!$wallet) {
                self::$lastError = 'wallet_not_found';
                if ($manageTx && $pdo->inTransaction()) { $pdo->rollBack(); }
                return false;
            }
            $newBalance = (int)$wallet['balance'] + ($isEarning ? $amount : -$amount);
            if ($newBalance < 0) {
                self::$lastError = 'insufficient_balance';
                if ($manageTx && $pdo->inTransaction()) { $pdo->rollBack(); }
                return false;
            }

            // Insert transaction (store positive amount)
            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type_id, amount, description, reference_entity_type, reference_entity_id) VALUES (:uid, :tid, :amt, :desc, :rtype, :rid)');
            $stmt->execute([
                ':uid' => $userId,
                ':tid' => $typeId,
                ':amt' => $amount,
                ':desc' => $description,
                ':rtype' => $refType,
                ':rid' => $refId,
            ]);

            // Update wallet
            if ($isEarning) {
                $stmt = $pdo->prepare('UPDATE user_wallets SET balance = balance + :amt1, total_earned = total_earned + :amt2, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid');
                $stmt->execute([':amt1' => $amount, ':amt2' => $amount, ':uid' => $userId]);
            } else {
                $stmt = $pdo->prepare('UPDATE user_wallets SET balance = balance - :amt1, total_spent = total_spent + :amt2, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid');
                $stmt->execute([':amt1' => $amount, ':amt2' => $amount, ':uid' => $userId]);
            }

            if ($manageTx && $pdo->inTransaction()) { $pdo->commit(); }
            return true;
        } catch (\Throwable $e) {
            if ($manageTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::$lastError = 'exception:' . substr($e->getMessage(), 0, 120);
            return false;
        }
    }
}
