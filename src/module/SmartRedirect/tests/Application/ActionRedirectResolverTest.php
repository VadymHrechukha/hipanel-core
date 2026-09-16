<?php

declare(strict_types=1);

namespace hipanel\module\SmartRedirect\tests\Application;

use hipanel\actions\Action;
use hipanel\module\SmartRedirect\Application\ActionRedirectResolver;
use hipanel\module\SmartRedirect\Domain\PostActionRedirectPolicy;
use hipanel\module\SmartRedirect\Domain\PreferPreviousRedirectPolicy;
use hipanel\module\SmartRedirect\Domain\PreferSearchRedirectPolicy;
use hipanel\module\SmartRedirect\Domain\PreferViewRedirectPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ActionRedirectResolverTest extends TestCase
{
    private const VIEW_URL = '/entity/view?id=42';
    private const SEARCH_URL = '/entity/index';
    private const PREVIOUS_URL = '/entity/index?previous=1';

    #[DataProvider('resolveCases')]
    public function testResolve(?PostActionRedirectPolicy $policy, int $itemCount, ?string $previousUrl, string $expectedUrl): void
    {
        $resolver = new ActionRedirectResolver($policy !== null ? ['policy' => $policy] : []);

        $this->assertSame($expectedUrl, $resolver->resolve($this->makeAction($itemCount, $previousUrl)));
    }

    public static function resolveCases(): iterable
    {
        // --- Waterfall (no policy): view → previous → search ---
        yield 'waterfall: single item resolves to view' => [
            null, 1, null, self::VIEW_URL,
        ];
        yield 'waterfall: view wins over previous for single item' => [
            null, 1, self::PREVIOUS_URL, self::VIEW_URL,
        ];
        yield 'waterfall: multiple items fall back to previous' => [
            null, 2, self::PREVIOUS_URL, self::PREVIOUS_URL,
        ];
        yield 'waterfall: multiple items with no previous fall back to search' => [
            null, 2, null, self::SEARCH_URL,
        ];

        // --- PreferViewRedirectPolicy: explicit policy means no waterfall fallback to previous ---
        yield 'prefer view: single item resolves to view' => [
            new PreferViewRedirectPolicy(), 1, null, self::VIEW_URL,
        ];
        yield 'prefer view: single item ignores previous even when remembered' => [
            new PreferViewRedirectPolicy(), 1, self::PREVIOUS_URL, self::VIEW_URL,
        ];
        yield 'prefer view: multiple items with no previous fall back to search' => [
            new PreferViewRedirectPolicy(), 2, null, self::SEARCH_URL,
        ];
        yield 'prefer view: multiple items fall back straight to search' => [
            new PreferViewRedirectPolicy(), 2, self::PREVIOUS_URL, self::SEARCH_URL,
        ];

        // --- PreferPreviousRedirectPolicy ---
        yield 'prefer previous: resolves to previous url' => [
            new PreferPreviousRedirectPolicy(), 1, self::PREVIOUS_URL, self::PREVIOUS_URL,
        ];
        yield 'prefer previous: single item falls back to view when no previous remembered' => [
            new PreferPreviousRedirectPolicy(), 1, null, self::VIEW_URL,
        ];
        yield 'prefer previous: multiple items fall back to search when no previous remembered' => [
            new PreferPreviousRedirectPolicy(), 2, null, self::SEARCH_URL,
        ];
        yield 'prefer previous: multiple items resolve to previous url regardless of count' => [
            new PreferPreviousRedirectPolicy(), 2, self::PREVIOUS_URL, self::PREVIOUS_URL,
        ];

        // --- PreferSearchRedirectPolicy ---
        yield 'prefer search: always resolves to search' => [
            new PreferSearchRedirectPolicy(), 1, self::PREVIOUS_URL, self::SEARCH_URL,
        ];
    }

    private function makeAction(int $itemCount, ?string $previousUrl): Action
    {
        $collection = new class($itemCount) {
            public object $first;

            public function __construct(private int $itemCount)
            {
                $this->first = (object)['id' => 42];
            }

            public function count(): int
            {
                return $this->itemCount;
            }
        };

        $controller = new class(self::VIEW_URL, self::SEARCH_URL, $previousUrl) {
            public function __construct(
                private string $viewUrl,
                private string $searchUrl,
                private ?string $previousUrl,
            ) {}

            public function getActionUrl(string $action, array $params = []): string
            {
                return $this->viewUrl;
            }

            public function getSearchUrl(): string
            {
                return $this->searchUrl;
            }

            public function getPreviousUrl(Action $action): ?string
            {
                return $this->previousUrl;
            }
        };

        $action = new class($collection) extends Action {
            public function __construct(private object $collectionStub)
            {
            }

            public function getCollection(): object
            {
                return $this->collectionStub;
            }

            public function run(): mixed
            {
                return null;
            }
        };

        $action->controller = $controller;

        return $action;
    }
}
