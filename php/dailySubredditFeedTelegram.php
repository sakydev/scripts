<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Telegram\Bot\Api;

class SubredditService extends AbstractCommandService
{
    public const array DEFAULT_SUBREDDITS = ['php'];
    public const int DEFAULT_LIMIT = 5;
    public const string DEFAULT_SORT = 'top';
    public const string DEFAULT_TIME = 'day';

    private Client $requestClient;

    public function __construct(Api $telegram, string $chatID)
    {
        parent::__construct($telegram, $chatID);

        $this->requestClient = new Client([
            'base_uri' => 'https://www.reddit.com/r/',
            'timeout' => 5.0,
            'headers' => [
                'User-Agent' => 'MyRedditBot/1.0',
            ]
        ]);
    }

    public function handle(array $arguments): void
    {
        $subreddits = explode(',', $arguments['subreddits'] ?? $_ENV['REDDIT_DEFAULT_SUBREDDITS']);
        $limit = $arguments['limit'] ?? $_ENV['REDDIT_DEFAULT_LIMIT'];
        $sort = $arguments['sort'] ?? $_ENV['REDDIT_DEFAULT_SORT'];
        $time = $arguments['time'] ?? $_ENV['REDDIT_DEFAULT_TIME'];
        $message = '';

        try {
            foreach ($subreddits as $subreddit) {
                $response = $this->requestClient->get("{$subreddit}/{$sort}.json", [
                    'query' => [
                        'limit' => $limit,
                        't' => $time,
                    ]
                ]);

                $data = json_decode($response->getBody()->getContents());

                if (empty($data->data->children)) {
                    $this->sendMessage('Unable to fetch posts from Reddit. Please try again later.');

                    return;
                }

                $message .= $this->buildMessage($data->data->children, $subreddit, $time, $limit);
            }

            $this->sendMessage(message: $message, htmlMode: true);
        } catch (RequestException $e) {
            $this->sendMessage('Error fetching posts from Reddit: ' . $e->getMessage());
        }
    }

    private function buildMessage(array $posts, string $subreddit, string $time, string $limit): string
    {
        $message = "\n\n🔥 <b>Top {$limit} from r/{$subreddit}</b> ({$time})\n";
        foreach ($posts as $post) {
            $redditLink = "https://reddit.com{$post->data->permalink}";

            $message .= sprintf(
                "- [%d]: <a href=\"%s\">%s</a>\n\n",
                $post->data->score,
                $redditLink,
                $this->buildTitle($post->data->title),
            );
        }

        return $message;
    }

    private function buildTitle(string $title): string
    {
        return htmlspecialchars(mb_strimwidth($title, 0, 125, '…'), ENT_QUOTES, 'UTF-8');
    }
}
