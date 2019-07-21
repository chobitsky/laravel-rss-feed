<?php
namespace App\Http\Controllers\Api;

use \Amp\Delayed;
use \Amp\Loop;
use function \Amp\asyncCall;
use \Feed;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ContentsController extends Controller
{
    public function feed()
    {
        $urls = [
            //"hatena"    => "http://feeds.feedburner.com/hatena/b/hotentry",
            "lifehacker"    => "http://feeds.lifehacker.jp/rss/lifehacker/index.xml",
            "niconico"      => "https://news.nicovideo.jp/ranking/comment?rss=2.0",
            "dpz"           => "http://portal.nifty.com/rss/headline.rdf",
        ];

        $data = [];
        foreach ($urls as $name => $url) {
            $rss = Feed::loadRss($url);
            $data[$name] = $this->getItems($rss);
        }

        return \Response::json($data);
    }

    public function amp()
    {
        \Amp\Loop::run(function () {
            $urls = [
                "hatena"        => "http://b.hatena.ne.jp/",
                "niconico"      => "https://news.nicovideo.jp/ranking/comment?rss=2.0",
                "lifehacker"    => "http://feeds.lifehacker.jp/rss/lifehacker/index.xml",
                "dpz"           => "http://portal.nifty.com/rss/headline.rdf",
            ];

            $client = new \Amp\Artax\DefaultClient;
            $client->setOption(\Amp\Artax\Client::OP_DISCARD_BODY, true);
        
            try {
                foreach ($urls as $name => $url) {
                    $promises[$url] = $client->request($url);
                    $responses = yield $promises;
                }
                
                foreach ($responses as $url => $response) {
                    echo $url . " - " . $response->getStatus() . $response->getReason() . PHP_EOL;
                    echo "<br>";
                }
            } catch (\Amp\Artax\HttpException $error) {
                // If something goes wrong Amp will throw the exception where the promise was yielded.
                // The Client::request() method itself will never throw directly, but returns a promise.
                print $error->getMessage() . PHP_EOL;
            }
        });
    }

    private function getItems($rss) {
        $rssFeeds = [];
        foreach ($rss->item as $item) {
            $rssFeeds[] = [
                "title"         => (string)$item->title,
                "Link"          => (string)$item->link,
                "Timestamp"     => (string)$item->timestamp,
                "dateTime"      => date("Y-m-d H:i:s", (string)$item->timestamp),
                "Description"   => (string)$item->description,
            ];
        }
        return $rssFeeds;
    }

}
