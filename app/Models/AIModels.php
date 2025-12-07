<?php

namespace App\Models;

use CodeIgniter\Model;

class AIInteractionModel extends Model
{
    protected $table = 'ai_interactions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'user_message',
        'ai_response',
        'provider',
        'response_time',
        'tokens_used',
        'sentiment',
        'created_at'
    ];
    protected $useTimestamps = false;
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = true;

    /**
     * Get recent interactions for user
     */
    public function getRecentInteractions(int $userId, int $limit = 10)
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get interactions by provider
     */
    public function getByProvider(int $userId, string $provider, int $limit = 10)
    {
        return $this->where('user_id', $userId)
            ->where('provider', $provider)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get average response time for user
     */
    public function getAverageResponseTime(int $userId): float
    {
        $result = $this->where('user_id', $userId)
            ->selectAvg('response_time')
            ->first();

        return (float)($result['response_time'] ?? 0);
    }

    /**
     * Get total interactions count
     */
    public function getInteractionCount(int $userId): int
    {
        return $this->where('user_id', $userId)->countAllResults();
    }

    /**
     * Get sentiment distribution
     */
    public function getSentimentDistribution(int $userId): array
    {
        $result = $this->where('user_id', $userId)
            ->select('sentiment, COUNT(*) as count')
            ->groupBy('sentiment')
            ->findAll();

        return $result ?? [];
    }

    /**
     * Get interactions by date range
     */
    public function getByDateRange(int $userId, string $startDate, string $endDate, int $limit = 100)
    {
        return $this->where('user_id', $userId)
            ->where('created_at >=', $startDate)
            ->where('created_at <=', $endDate)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}

class AIChatLogModel extends Model
{
    protected $table = 'ai_chat_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['user_id', 'message', 'created_at'];
    protected $useTimestamps = false;
    protected $skipValidation = true;

    /**
     * Get recent chats
     */
    public function getRecentChats(int $userId, int $limit = 50)
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get chat count by user
     */
    public function getChatCount(int $userId): int
    {
        return $this->where('user_id', $userId)->countAllResults();
    }
}

class AIConversationModel extends Model
{
    protected $table = 'ai_conversations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'user_message',
        'ai_response',
        'created_at'
    ];
    protected $useTimestamps = false;
    protected $skipValidation = true;

    /**
     * Get conversation history
     */
    public function getHistory(int $userId, int $limit = 20)
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get conversation context (recent messages)
     */
    public function getContext(int $userId, int $messages = 5): string
    {
        $history = $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($messages)
            ->findAll();

        $context = "";
        foreach (array_reverse($history) as $conv) {
            $context .= "User: " . $conv['user_message'] . "\n";
            $context .= "AI: " . $conv['ai_response'] . "\n\n";
        }

        return $context;
    }

    /**
     * Clear conversation history for user
     */
    public function clearHistory(int $userId): bool
    {
        return $this->where('user_id', $userId)->delete();
    }
}

class AIPreferenceModel extends Model
{
    protected $table = 'ai_preferences';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'language',
        'tone',
        'notification_enabled',
        'weekly_summary_enabled',
        'updated_at'
    ];
    protected $useTimestamps = false;
    protected $skipValidation = true;

    /**
     * Get or create user preferences
     */
    public function getOrCreate(int $userId): array
    {
        $prefs = $this->where('user_id', $userId)->first();

        if (!$prefs) {
            $this->insert([
                'user_id' => $userId,
                'language' => 'id',
                'tone' => 'friendly',
                'notification_enabled' => 1,
                'weekly_summary_enabled' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $prefs = $this->where('user_id', $userId)->first();
        }

        return $prefs;
    }

    /**
     * Update user preference
     */
    public function updatePreference(int $userId, string $key, $value): bool
    {
        $prefs = $this->getOrCreate($userId);

        return $this->update($prefs['id'], [
            $key => $value,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}

class AIUserContextCacheModel extends Model
{
    protected $table = 'ai_user_context_cache';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['user_id', 'context_data', 'last_updated'];
    protected $useTimestamps = false;
    protected $skipValidation = true;

    /**
     * Get cached context
     */
    public function getCachedContext(int $userId, int $cacheTTL = 3600): ?array
    {
        $cache = $this->where('user_id', $userId)->first();

        if (!$cache) {
            return null;
        }

        $lastUpdate = strtotime($cache['last_updated']);
        $now = time();

        if (($now - $lastUpdate) > $cacheTTL) {
            // Cache expired
            return null;
        }

        return json_decode($cache['context_data'], true);
    }

    /**
     * Save or update cache
     */
    public function saveCache(int $userId, array $contextData): bool
    {
        $existing = $this->where('user_id', $userId)->first();

        if ($existing) {
            return $this->update($existing['id'], [
                'context_data' => json_encode($contextData),
                'last_updated' => date('Y-m-d H:i:s')
            ]);
        } else {
            return $this->insert([
                'user_id' => $userId,
                'context_data' => json_encode($contextData),
                'last_updated' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Clear cache for user
     */
    public function clearCache(int $userId): bool
    {
        return $this->where('user_id', $userId)->delete();
    }
}
