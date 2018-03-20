<?php declare(strict_types=1);

namespace BeBound\SDK\Push;

class PushRequest implements \JsonSerializable
{
    public const TYPE_PUSH = 0;
    public const TYPE_PIGGYBACK = 1;

    private $operationName;
    private $operationParams;
    private $transportType;
    private $users;

    /**
     * @throws \OutOfRangeException
     */
    public function __construct(
        string $operationName,
        array $operationParams,
        int $transportType,
        string ...$userID
    ) {
        $this->operationName = $operationName;
        $this->operationParams = $operationParams;
        $this->transportType = $this->validateTransportType($transportType);
        $this->users = $userID;
    }

    public function getOperationName(): string
    {
        return $this->operationName;
    }

    public function getOperationParams(): array
    {
        return $this->operationParams;
    }

    public function getTransportType(): int
    {
        return $this->transportType;
    }

    /**
     * @return string[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @throws \OutOfRangeException
     */
    private function validateTransportType(int $transportType): int
    {
        if (!\in_array($transportType, [
            self::TYPE_PUSH,
            self::TYPE_PIGGYBACK,
        ], true)) {
            throw new \OutOfRangeException('Invalid Push transport type');
        }

        return $transportType;
    }

    public function jsonSerialize()
    {
        return [
            'operation' => $this->getOperationName(),
            'userId' => $this->getUsers(),
            'params' => $this->getOperationParams(),
            'urgency' => $this->getTransportType(),
        ];
    }
}
