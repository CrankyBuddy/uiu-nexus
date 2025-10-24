<?php
return [
    'app' => [
        'name' => 'UIU NEXUS',
        'env' => 'local',
        'debug' => true,
        'url' => 'http://localhost',
        'timezone' => 'Asia/Dhaka',
        // Simple shared key for dev-only maintenance endpoints (seed/rebuild). Change in production.
        'maintenance_key' => 'changeme-key',
        // Phase 2: Coins & Chat knobs (safe defaults)
        'coins' => [
            // Priority = free_baseline + bid_amount * weight
            'priority_weight' => 1,
            'free_priority_baseline' => 1,
            // Bid caps
            'max_bid_per_request' => 1000,
            'max_daily_spend' => 2000, // cap per user per day for mentorship escrow holds
            // Reservation window (minutes)
            'reservation_minutes' => 10,
            // Max number of reservation extensions
            'max_reservation_extensions' => 1,
        ],
        'chat' => [
            // Daily DM send quota for non-admin users in direct conversations
            'daily_quota' => 20,
        ],
        'gamify' => [
            'forum' => [
                // Reputation weights
                'answer_upvote_rep' => 2, // default: +2 for answers
                'question_upvote_rep' => 1, // default: +1 for questions/discussions
                'author_downvoted_rep' => -1, // default: -1 to author on downvote
                'voter_downvote_cost_rep' => -1, // default: -1 to voter on downvote
                'accepted_answer_rep_answerer' => 5, // default: +5 to answerer
                'accepted_answer_rep_asker' => 2, // default: +2 to asker for selecting
                // Coins
                'accepted_answer_coins' => 10, // default: +10 coins to answerer
                'vote_up_coins' => 1, // default: +1 coin to author on upvote
                'vote_down_penalty_coins' => 1, // default: -1 coin from author on downvote
                'comment_post_coins' => 1, // default: +1 coin to student for posting a comment
                'comment_upvote_coins' => 2, // default: +2 coins to student comment author per upvote
                'comment_downvote_penalty_coins' => 1, // default: -1 coin from student comment author on downvote
                // Thresholds
                'min_rep_to_downvote' => 50,
            ],
        ],
        'features' => [
            'gpt5_codex_preview' => true,
        ],
    ],
];
