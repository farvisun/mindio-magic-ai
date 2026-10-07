<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Deployments\BetaManagedAgentsGitHubRepositoryResourceConfig;

use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsBranchCheckout;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsCommitCheckout;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Branch or commit to check out. Defaults to the repository's default branch.
 *
 * @phpstan-import-type BetaManagedAgentsBranchCheckoutShape from \Anthropic\Beta\Sessions\BetaManagedAgentsBranchCheckout
 * @phpstan-import-type BetaManagedAgentsCommitCheckoutShape from \Anthropic\Beta\Sessions\BetaManagedAgentsCommitCheckout
 *
 * @phpstan-type CheckoutVariants = BetaManagedAgentsBranchCheckout|BetaManagedAgentsCommitCheckout
 * @phpstan-type CheckoutShape = CheckoutVariants|BetaManagedAgentsBranchCheckoutShape|BetaManagedAgentsCommitCheckoutShape
 */
final class Checkout implements ConverterSource
{
    use SdkUnion;

    public static function discriminator(): string
    {
        return 'type';
    }

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            'branch' => BetaManagedAgentsBranchCheckout::class,
            'commit' => BetaManagedAgentsCommitCheckout::class,
        ];
    }
}
