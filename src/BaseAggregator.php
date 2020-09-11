<?php

namespace Aggregators\Support;

use Carbon\Carbon;
use Goutte\Client;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

abstract class BaseAggregator
{
    /**
     * The Goutee Client instance.
     *
     * @param Client
     */
    public Client $client;

    /**
     * The uri to retrieve the data from.
     *
     * @param string
     */
    public string $uri = '';

    /**
     * The name of the provider
     *
     * @param string
     */
    public string $provider = '';

    /**
     * The logo file located in src/assets/*.png
     *
     * @param string
     */
    public string $logo = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client(HttpClient::create(['timeout' => 9999999999]));
    }

    /**
     * Run the aggregator to retrieve data.
     *
     * @param boolean $all
     * @param string|null $uri
     * @return void
     */
    public function aggregate(bool $all = false, ?string $uri = null, ?callable $callable = null)
    {
        $crawler = $this->client->request('GET', is_null($uri) ? $this->uri : $uri);
        $nextUrl = $this->nextUrl($crawler);
        $articles = $this->getArticles($crawler);

        if (!is_null($callable)) {
            foreach ($articles as $article) {
                $callable($article);
            }
        }

        if (!is_null($nextUrl) && $all) {
            $this->aggregate($all, $nextUrl, $callable);
        }
    }

    /**
     * Retireve articles from crawler
     *
     * @return array
     */
    public function getArticles(Crawler $crawler): array
    {
        try {
            return $crawler->filter($this->articleIdentifier())->each(fn ($node) => [
                'image' => $this->image($node),
                'title' => $this->title($node) ?: '',
                'content' => $this->content($node) ?: '',
                'provider' => $this->provider,
                'link' => $this->link($node),
                'created_at' => $this->dateCreated($node),
                'updated_at' => $this->dateUpdated($node),
            ]);
        } catch (InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * The single article identifier.
     *
     * @return string
     */
    abstract public function articleIdentifier(): string;

    /**
     * Get the link for the next set of articles.
     *
     * @param Crawler $crawler
     * @return string|null
     */
    abstract public function nextUrl(Crawler $crawler): ?string;

    /**
     * Get the image for the current article.
     *
     * @param Crawler $crawler
     * @return string|null
     */
    abstract public function image(Crawler $crawler): ?string;


    /**
     * Get the title for the current article.
     *
     * @param Crawler $crawler
     * @return string|null
     */
    abstract public function title(Crawler $crawler): ?string;

    /**
     * Get the content for the current article.
     *
     * @param Crawler $crawler
     * @return string|null
     */
    abstract public function content(Crawler $crawler): ?string;

    /**
     * Get the link for the current article.
     *
     * @param Crawler $crawler
     * @return string|null
     */
    abstract public function link(Crawler $crawler): ?string;

    /**
     * Get the date created for current article.
     *
     * @param Crawler $crawler
     * @return Carbon
     */
    abstract public function dateCreated(Crawler $crawler): Carbon;

    /**
     * Get the date updated for the current article.
     *
     * @param Crawler $crawler
     * @return Carbon
     */
    abstract public function dateUpdated(Crawler $crawler): Carbon;
}
