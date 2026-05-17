<?php

namespace App\Services\Listing\Upgrade\Benefits;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Package;

final class PackageBenefitStrategyFactory
{
    public function __construct(
        private readonly DataDrivenBenefitStrategy $dataDrivenBenefitStrategy,
    ) {
    }

    public function make(Package $package): PackageBenefitStrategy
    {
        return match ($package->benefit_strategy_key ?? 'data_driven') {
            'data_driven' => $this->dataDrivenBenefitStrategy,
            default => throw new BusinessException(ErrorCode::BadRequest),
        };
    }
}
