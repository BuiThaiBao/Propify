<?php

namespace Tests\Unit\Listing;

use App\Exceptions\BusinessException;
use App\Models\Package;
use App\Services\Listing\Upgrade\Benefits\DataDrivenBenefitStrategy;
use App\Services\Listing\Upgrade\Benefits\PackageBenefitStrategyFactory;
use PHPUnit\Framework\TestCase;

final class PackageBenefitStrategyFactoryTest extends TestCase
{
    public function test_returns_data_driven_strategy_for_default_key(): void
    {
        $package = new Package();
        $package->benefit_strategy_key = 'data_driven';

        $strategy = $this->factory()->make($package);

        $this->assertInstanceOf(DataDrivenBenefitStrategy::class, $strategy);
    }

    public function test_rejects_unknown_strategy_key(): void
    {
        $package = new Package();
        $package->benefit_strategy_key = 'unknown';

        $this->expectException(BusinessException::class);

        $this->factory()->make($package);
    }

    private function factory(): PackageBenefitStrategyFactory
    {
        return new PackageBenefitStrategyFactory(new DataDrivenBenefitStrategy());
    }
}
