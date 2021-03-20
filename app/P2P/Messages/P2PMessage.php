<?php


namespace App\P2P\Messages;

use App\Jobs\SendP2PMessage;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ReflectionClass;

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
    public static final function fromRequestData(array $messageData, bool $isResponse = false): array
    {

        $data = Validator::make($messageData, [
            'message' => ['required', 'string'], // TODO check not self
            'sender' => ['required', 'string']
        ])->validate();

        $message = $data['message'];
        $className = self::getClass($message);

        $r = new \ReflectionClass($className);
        /** @var static $instance */
        $instance = $r->newInstanceWithoutConstructor();
        $messageObj = $instance->fromRequest($messageData);

        return $messageObj->onRequestReceived();

    }

    /**
     * @param string $message
     * @return string
     * @throws Exception
     */
    protected static function getClass(string $message): string
    {
        switch ($message) {
            case AddMeToYourPeers::name:
                return AddMeToYourPeers::class;
            // ###########################
            case WillYouBeAElectionTrusteeForMyElection::name:
                return WillYouBeAElectionTrusteeForMyElection::class;
            //####################################### MOCK
            case SendMeBackNInMSeconds::name:
                return SendMeBackNInMSeconds::class;
            case TakeBackN::name:
                return TakeBackN::class;
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
    public function getRequestData(): array
    {
        return [];
    }

    // #############################################################

    /**
     * Sends the message in a blocking way, should only be called by queues (and tasks) and not during requests
     * @throws ValidationException|\ReflectionException
     */
    public final function sendSync(): void
    {
        $url = "http://" . $this->to . '/api/p2p';

        Log::debug($this->from . " > I am sending a message to " . $url);

        //$this->ping($url);

        $messageName = (new ReflectionClass(get_class($this)))->getConstant('name');

        $data = array_merge_recursive($this->getRequestData(), [
            'sender' => "http://" . $this->from,
            'message' => $messageName // TODO check
        ]);

        $response = Http::post($url, $data);  // ############################################## Response

        Log::debug("I received a response with status " . $response->status());

        if ($response->status() >= 200 && $response->status() < 300) {

            $json = $response->json();
            Log::debug($json);

            try {
                $this->onResponseReceived($response->json());
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                Log::error($e->getFile());
                Log::error($e->getLine());
                throw $e;
            }

        } else {
            Log::error($response->body());
        }

    }

    /**
     * @return mixed
     */
    public final function sendAsync(): void
    {
        SendP2PMessage::dispatch($this);
    }

    // #############################################################
    // #############################################################

    // The output of onRequestReceived() should match the input of onResponseReceived();

    /**
     * @return array
     */
    public abstract function onRequestReceived(): array;

    /**
     * @param array $data
     * @return void
     */
    public function onResponseReceived(array $data): void
    {
    }

    // #############################################################
    // #############################################################

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

}
