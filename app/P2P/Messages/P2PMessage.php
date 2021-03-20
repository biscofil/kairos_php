<?php


namespace App\P2P\Messages;

use App\Jobs\SendP2PMessage;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class P2PMessage
 * @package App\P2P\Messages
 * @property string $from
 * @property string $to
 * @property string $name
 */
abstract class P2PMessage
{

    public $from;
    public $to;
    protected $name;

    /**
     * P2PMessage constructor.
     * @param string $from
     * @param string $to
     * @throws Exception
     */
    public function __construct(string $from, string $to)
    {
        $this->from = extractDomain($from);
        $this->to = extractDomain($to);
    }

    /**
     * @param array $messageData
     * @param bool $isResponse // TODO
     * @return P2PMessage|null
     * @throws ValidationException
     * @throws Exception
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static final function parse(array $messageData, bool $isResponse = false): ?P2PMessage
    {

        $data = Validator::make($messageData, [
            'message' => ['required', 'string'], // TODO check not self
            'sender' => ['required', 'string']
        ])->validated();

        $message = $data['message'];

        switch ($message) {

            case MessageReceived::$name: // response
                return MessageReceived::fromRequest($messageData)->onMessageReceived();

            // ###########################

            case AddMeToYourPeers::$name:
                return AddMeToYourPeers::fromRequest($messageData)->onMessageReceived();

            // ###########################

            case WillYouBeAElectionTrusteeForMyElection::$name:
                return WillYouBeAElectionTrusteeForMyElection::fromRequest($messageData)->onMessageReceived();

            case OkIWillBeAnElectionTrustee::$name:
                return OkIWillBeAnElectionTrustee::fromRequest($messageData)->onMessageReceived();

            //####################################### MOCK
            case SendMeBackNInMSeconds::$name:
                return SendMeBackNInMSeconds::fromRequest($messageData)->onMessageReceived();

            case TakeBackN::$name:
                return TakeBackN::fromRequest($messageData)->onMessageReceived();

            default:
                Log::error("Unknown message name $message");
                throw new Exception("Unknown message name $message");

        }
    }

    /**
     * @param array $messageData
     * @return static
     * @throws Exception
     */
    public static function fromRequest(array $messageData): P2PMessage
    {
        return new static($messageData['sender'], config('app.url'));
    }

    /**
     * Sends the message in a blocking way, should only be called by queues and not during requests
     * @return array
     */
    public function getRequestData(): array{
        return [];
    }

    /**
     * Sends the message in a blocking way, should only be called by queues (and tasks) and not during requests
     * @throws ValidationException
     */
    public final function sendSync(): bool
    {
        $url = "http://" . $this->to . '/api/p2p';

        Log::debug($this->from . " > I am sending a message to " . $url);

        //$this->ping($url);

        $data = array_merge_recursive($this->getRequestData(), [
            'sender' => "http://" . $this->from,
            'message' => $this->name
        ]);

        // ############################################## Response

        $response = Http::post($url, $data);

        Log::debug("I received a response with status  " . $response->status());

        if ($response->status() >= 200 && $response->status() < 300) {

            $json = $response->json();
            Log::debug($json);

            // TODO async?
            if ($json !== []) { // TODO const, class

                $newRequestData = array_merge($json, ['sender' => $this->to]);
                Log::debug($newRequestData);

                Log::debug("SELF::PARSE");
                self::parse($newRequestData, true); // TODO async?

                //response()->json(is_null($out) ? [] : $out->getRequestData()); // TODO duplicate
                return true;
            } else {
                return true;
            }
        } else {
            Log::error($response->body());
            return false;
        }

    }

    /**
     * @return mixed
     */
    public final function sendAsync()
    {
        return SendP2PMessage::dispatch($this);
    }

    /**
     * @return P2PMessage|null
     */
    public abstract function onMessageReceived(): ?P2PMessage;

    /**
     * @param string $ip
     * @param int $port
     * @return bool
     */
    private function ping(string $ip, int $port = 80): bool
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

    /**
     * @return MessageReceived
     */
    protected function getDefaultResponse(): MessageReceived
    {
        return new MessageReceived($this->to, $this->from);
    }


}
