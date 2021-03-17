<?php


namespace App\P2P\Messages;

use App\Jobs\SendP2PMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class P2PMessage
 * @package App\P2P\Messages
 * @property string $from
 * @property string $to
 */
abstract class P2PMessage
{

    protected $to;
    protected $from;

    /**
     * @param string $message
     * @param Request $request
     */
    public static function parse(string $message, Request $request)
    {
        switch ($message) {
            case AddMeToYourPeers::NAME:
                return AddMeToYourPeers::fromRequest($request)->onMessageReceived();
            case AddMeToYourElectionPeers::NAME:
                return AddMeToYourElectionPeers::fromRequest($request)->onMessageReceived();
            case SendMeBackNInMSeconds::NAME:
                return SendMeBackNInMSeconds::fromRequest($request)->onMessageReceived();
            case TakeBackN::NAME:
                return TakeBackN::fromRequest($request)->onMessageReceived();
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public abstract static function fromRequest(Request $request);

    /**
     * Sends the message in a blocking way, should only be called by queues and not during requests
     * @return mixed
     */
    public abstract function getRequestData() : array;

    /**
     * Sends the message in a blocking way, should only be called by queues (and tasks) and not during requests
     */
    public function sendSync() : bool
    {
        $url = $this->to . '/api/p2p';

        // Log::debug(config('app.url') . " > I am sending a message to " . $url);

        $data = array_merge($this->getRequestData(), [
            'sender' => config('app.url'),
        ]);

        $response = Http::post($url, $data);

        if ($response->status() >= 200 && $response->status() < 300) {
            dump($response->json());
            return true;
        } else {
            dump($response->status());
            dump($response->body());
            return false;
        }

    }

    /**
     * @return mixed
     */
    public function sendAsync()
    {
        return SendP2PMessage::dispatch($this);
    }

    /**
     * @return mixed
     */
    public abstract function onMessageReceived();

    /**
     * @param string $ip
     * @param int $port
     * @return bool
     */
    public function ping(string $ip, int $port = 80): bool
    {
        $url = $ip . ':' . $port;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $health = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        dump($health);
        return boolval($health);
    }


}
