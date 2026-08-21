<?php

namespace SmartDato\BrtDocumentoDiRitiro\Data;

use Spatie\LaravelData\Data;

class AddressData extends Data
{
    public function __construct(
        public ?string $personName = null,
        public ?string $companyName = null,
        public ?string $street = null,
        public ?string $zipcode = null,
        public ?string $city = null,
        public string $countryCode = '',
        public ?string $countryName = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $province = null,
    ) {}

    public function personOrCompanyName(): ?string
    {
        if (($this->personName ?? '') === '') {
            return $this->companyName;
        }

        return $this->personName;
    }

    public function companyOrPersonName(): ?string
    {
        if (($this->companyName ?? '') === '') {
            return $this->personName;
        }

        return $this->companyName;
    }

    public function equals(self $other): bool
    {
        return $this->personName == $other->personName
            && $this->companyName == $other->companyName
            && $this->street == $other->street
            && $this->zipcode == $other->zipcode
            && $this->city == $other->city
            && $this->countryCode == $other->countryCode
            && $this->email == $other->email
            && $this->phone == $other->phone;
    }
}
