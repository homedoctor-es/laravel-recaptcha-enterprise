<?php

declare(strict_types=1);

namespace Oneduo\RecaptchaEnterprise\Rules;

use Carbon\CarbonInterval;
use Google\ApiCore\ApiException;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Oneduo\RecaptchaEnterprise\Exceptions\InvalidTokenException;
use Oneduo\RecaptchaEnterprise\Facades\RecaptchaEnterprise;

class Recaptcha implements Rule, DataAwareRule
{

    protected const DEFAULT_PLATFORM_KEY = 'platform-type';

    protected array $data = [];
    protected ?string $reasonMsg = null;

    public function __construct(
        public ?string $platformKey = self::DEFAULT_PLATFORM_KEY,
        public ?string $action = null,
        public ?CarbonInterval $interval = null
    ) {
    }

    /**
     * @param string $attribute
     * @param string $value
     * @return bool
     *
     * @throws \Google\ApiCore\ApiException
     * @throws \Oneduo\RecaptchaEnterprise\Exceptions\MissingPropertiesException
     */
    public function passes($attribute, $value): bool
    {
        try {
            $recaptcha = RecaptchaEnterprise::assess($value, $this->getPlatform());
        } catch (InvalidTokenException $exception) {
            $this->reasonMsg = InvalidReason::name($exception->reason);

            return false;
        } catch (ApiException $exception) {
            $this->reasonMsg = $exception->getReason();

            return false;
        }

        $validAction = true;
        $validInterval = true;

        if ($this->action) {
            $validAction = $recaptcha->validateAction($this->action);
        }

        if ($this->interval) {
            $validInterval = $recaptcha->validateCreationTime($this->interval);
        }

        return $recaptcha->validateScore() && $validAction && $validInterval;
    }

    public function getPlatform(): ?string
    {
        return $this->data[$this->platformKey] ?? null;
    }

    public function action(?string $action = null): static
    {
        $this->action = $action;

        return $this;
    }

    public function validity(?CarbonInterval $interval = null): static
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function message(): string
    {
        return __('recaptcha-enterprise::validation.recaptcha', [
            'reason' => $this->reasonMsg ?? 'Unknown reason',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function setData($data): self
    {
        $this->data = [$this->platformKey => $data[$this->platformKey] ?? null];

        return $this;
    }

}
