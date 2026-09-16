<?php

declare(strict_types=1);

namespace hipanel\module\SmartRedirect\tests\Infrastructure;

use hipanel\actions\Action;
use hipanel\base\Controller;
use hipanel\module\SmartRedirect\Infrastructure\ReferrerUrlMemoryBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Covers the pure helpers only (isSuitableReferrer, getUrlKey).
 * rememberUrl() needs a bootstrapped Yii app (Yii::$app->getSession()),
 * which this suite doesn't set up — verify it via the app's E2E suite.
 */
class ReferrerUrlMemoryBehaviorTest extends TestCase
{
    #[DataProvider('suitableReferrerCases')]
    public function testIsSuitableReferrer(array $patterns, ?string $referrer, bool $expected): void
    {
        $behavior = new ReferrerUrlMemoryBehavior();
        $behavior->suitableReferrerPatterns = $patterns;

        $this->assertSame($expected, $this->invokePrivate($behavior, 'isSuitableReferrer', $referrer));
    }

    public static function suitableReferrerCases(): iterable
    {
        $defaults = ['/index', '/view'];

        yield 'referrer matching the /index pattern is suitable' => [$defaults, '/entity/index', true];
        yield 'referrer matching the /view pattern is suitable' => [$defaults, '/entity/view?id=1', true];
        yield 'referrer matching neither pattern is not suitable' => [$defaults, '/entity/create', false];
        yield 'null referrer is never suitable, regardless of patterns' => [$defaults, null, false];
        yield 'custom patterns override the defaults' => [['/custom'], '/entity/custom', true];
        yield 'referrer only matching an overridden default pattern is not suitable' => [['/custom'], '/entity/index', false];
    }

    #[DataProvider('urlKeyCases')]
    public function testGetUrlKey(?string $parentId, string $expectedKey): void
    {
        $behavior = new ReferrerUrlMemoryBehavior();
        $controller = $this->makeController('server');
        $action = $this->makeAction('assign-hubs', $parentId);

        $this->assertSame($expectedKey, $this->invokePrivate($behavior, 'getUrlKey', $controller, $action));
    }

    public static function urlKeyCases(): iterable
    {
        yield "action with no parent: key uses the action's own id" => [null, 'server.assign-hubs'];
        yield "action with a parent: key uses the parent's id, not the action's own" => ['update-html', 'server.update-html'];
    }

    private function makeAction(string $id, ?string $parentId): Action
    {
        $action = new class extends Action { public function __construct() {} };
        $action->id = $id;
        $action->parent = $parentId !== null ? $this->makeAction($parentId, null) : null;

        return $action;
    }

    private function makeController(string $id): Controller
    {
        $controller = new class extends Controller { public function __construct() {} };
        $controller->id = $id;

        return $controller;
    }

    private function invokePrivate(object $object, string $method, mixed ...$args): mixed
    {
        return (new ReflectionMethod($object, $method))->invoke($object, ...$args);
    }
}
