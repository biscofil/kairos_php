<?php


namespace App\P2P\Messages;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class TakeBackN
 * @package App\P2P\Messages
 * @property int $n
 * @property int $m
 */
class TakeBackN extends P2PMessage
{

    protected $name = 'take_back_n_in_m_seconds';

    private $n;
    private $m;

    /**
     * TakeBackN constructor.
     * @param int $n
     * @param int $m
     * @param string $from
     * @param string $to
     * @throws \Exception
     */
    public function __construct(int $n, int $m, string $from, string $to)
    {
        parent::__construct($from, $to);
        $this->n = $n;
        $this->m = $m;
    }

    /**
     * @param array $messageData
     * @return static
     * @throws ValidationException
     * @throws \Exception
     */
    public static function fromRequest(array $messageData): P2PMessage
    {
        $data = Validator::make($messageData, [
            'n' => ['required', 'integer'],
            'm' => ['required', 'integer'],
        ])->validated();

        return new static($data['n'], $data['m'], $messageData['sender'], config('app.url'));
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return [
            'n' => $this->n,
            'm' => $this->m
        ];
    }

    /**
     * @return P2PMessage|null
     */
    public function onMessageReceived(): ?P2PMessage
    {
        // TODO call job for task
        Log::debug(config('app.url') . " > TakeBackN message received from " . $this->from);
        Log::debug(config('app.url') . " > " . $this->n);

        return $this->getDefaultResponse();
    }


}
