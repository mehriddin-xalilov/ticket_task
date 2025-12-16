<?php

namespace App\DTO;

class SocialLoginDto
{
    public string $provider;
    public string $provider_id;
    public ?string $full_name = null;
    public ?string $avatar = null;
    public ?string $email = null;
    public ?string $phone = null;

    public function __construct(array $payload)
    {
        $required = [
            'provider',
            'provider_id',
        ];
        $missingFields = array_diff($required, array_keys($payload));
        if (!empty($missingFields)) {
            throw new \InvalidArgumentException('The following fields are required: ' . implode(', ', $missingFields), 422);
        }
        foreach ($payload as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,
            'name' => $this->full_name,
            'avatar' => $this->avatar,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

}
