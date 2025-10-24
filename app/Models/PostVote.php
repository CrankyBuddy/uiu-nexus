<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class PostVote
{
    public static function get(Config $config, int $userId, int $postId): ?string
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT vote_type FROM post_votes WHERE user_id = :u AND post_id = :p');
        $stmt->execute([':u' => $userId, ':p' => $postId]);
        $v = $stmt->fetchColumn();
        return $v ? (string)$v : null;
    }

    public static function set(Config $config, int $userId, int $postId, string $voteType): string
    {
        $voteType = $voteType === 'downvote' ? 'downvote' : 'upvote';
        $pdo = Database::pdo($config);
        $current = self::get($config, $userId, $postId);
        if ($current === $voteType) {
            return 'noop';
        }
        if ($current === null) {
            $stmt = $pdo->prepare('INSERT INTO post_votes (user_id, post_id, vote_type) VALUES (:u, :p, :t)');
            $stmt->execute([':u' => $userId, ':p' => $postId, ':t' => $voteType]);
            return $voteType === 'upvote' ? 'up' : 'down';
        }
        // switch
        $stmt = $pdo->prepare('UPDATE post_votes SET vote_type = :t WHERE user_id = :u AND post_id = :p');
        $stmt->execute([':t' => $voteType, ':u' => $userId, ':p' => $postId]);
        return $voteType === 'upvote' ? 'switch_up' : 'switch_down';
    }
}
