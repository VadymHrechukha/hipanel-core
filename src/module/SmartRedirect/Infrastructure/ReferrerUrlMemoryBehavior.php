<?php

declare(strict_types=1);

namespace hipanel\module\SmartRedirect\Infrastructure;

use hipanel\actions\SmartUpdateAction;
use hipanel\base\Controller;
use Yii;
use yii\base\Action;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\helpers\Url;

/**
 *
 * @property-read null|string $previousUrl
 */
class ReferrerUrlMemoryBehavior extends Behavior
{
    /**
     * Substrings that mark a referrer URL as suitable to be remembered as "previous URL".
     *
     * @var string[]
     */
    public array $suitableReferrerPatterns = ['/index', '/view'];

    public function events(): array
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'rememberUrl',
        ];
    }

    public function rememberUrl(ActionEvent $actionEvent): void
    {
        if (!$actionEvent->action instanceof SmartUpdateAction) {
            return;
        }

        $key = $this->getUrlKey($this->owner, $this->owner->action);
        $referrer = $actionEvent->sender->request->referrer;

        if ($this->isSuitableReferrer($referrer)) {
            $this->rememberReferrer($referrer, $key);

            return;
        }

        // Only a GET can safely forget: a POST's referrer is the form's own
        // URL, which never matches — clearing on every save would erase what
        // a preceding GET just correctly remembered.
        if (!$actionEvent->sender->request->isPost) {
            $this->forgetReferrer($key);
        }
    }

    private function isSuitableReferrer(?string $referrer): bool
    {
        if ($referrer === null) {
            return false;
        }

        return $this->matchesAnySuitablePattern($referrer);
    }

    private function matchesAnySuitablePattern(string $referrer): bool
    {
        foreach ($this->suitableReferrerPatterns as $pattern) {
            if (str_contains($referrer, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function rememberReferrer(string $referrer, string $key): void
    {
        Url::remember($referrer, $key);
    }

    /**
     * Clears stale memory instead of letting it linger and get replayed
     * for an unrelated later visit.
     */
    private function forgetReferrer(string $key): void
    {
        Yii::$app->getSession()->remove($key);
    }

    private function getUrlKey(Controller $controller, Action $action): string
    {
        return implode('.', [$controller->id, $action->parent->id ?? $action->id]);
    }

    public function getPreviousUrl(Action $action): ?string
    {
        $key = $this->getUrlKey($this->owner, $action);

        return Url::previous($key);
    }
}
