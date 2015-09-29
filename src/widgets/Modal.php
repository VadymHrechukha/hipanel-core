<?php

namespace hipanel\widgets;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;

class Modal extends \yii\bootstrap\Modal
{
    public $scenario;

    public $actionUrl = [];

    public $modalFormId;

    public $errorText;

    public $successText;

    public function init()
    {
        if (!$this->scenario) {
            throw new InvalidConfigException("Attribute 'scenario' is required");
        }

        $this->initAdditionalOptions();
        $this->initPerformPjaxSubmit();
        parent::init();
    }

    private function getActionUrl()
    {
        return $this->actionUrl ? Url::to($this->actionUrl) : Url::to($this->scenario);
    }

    private function getModalFormId()
    {
        return $this->modalFormId ? : $this->scenario . '-form';
    }

    protected function initPerformPjaxSubmit()
    {
        $view = Yii::$app->view;
        $buttonLoadingText = Yii::t('app', 'loading');
        $formId = $this->getModalFormId();
        $formActionUrl = $this->getActionUrl();
        $modalId = $this->getId();
        $errorText = ($this->errorText) ? : Yii::t('app', 'An error occurred. Try again later.');
        $successText = ($this->successText) ? : Yii::t('app', 'The settings saved');
        $view->registerJs(<<<JS
//            jQuery(document).on('pjax:beforeSend', function() {
//                jQuery('form[data-pjax] button').button('{$buttonLoadingText}');
//            });
//            jQuery(document).on('pjax:end', function() {
//                jQuery('form[data-pjax] button').button('reset');
//            });

            jQuery(document).on('submit', '#{$formId}', function(event) {
                event.preventDefault();
                var form = jQuery(this);
                var btn = jQuery('form[data-pjax] button').button('{$buttonLoadingText}');
                jQuery.ajax({
                    url: '{$formActionUrl}',
                    type: 'POST',
                    //dataType: 'json',
                    timeout: 0,
                    data: form.serialize(),
                    error: function() {
                        new PNotify({
                            title: 'Error',
                            text: "{$errorText}",
                            type: 'error',
                            buttons: {
                                sticker: false
                            },
                            icon: false
                        });
                    },
                    success: function() {
                        jQuery('#$modalId').modal('hide');
                        new PNotify({
                            title: 'Success',
                            text: "{$successText}",
                            type: 'info',
                            buttons: {
                                sticker: false
                            },
                            icon: false
                        });
                        btn.button('reset');
                    }
                });
            });
JS
        );
    }

    protected function initAdditionalOptions()
    {
        $actionUrl = $this->getActionUrl();
        $modalId = $this->getId();
        $loadingHtml = Json::htmlEncode($this->getLoadHtml());
        $this->clientEvents['show.bs.modal'] = new JsExpression("function() {
            jQuery('#{$modalId} .modal-body').load('{$actionUrl}');
        }");
        $this->clientEvents['hidden.bs.modal'] = new JsExpression("function() {
            jQuery('#{$modalId} .modal-body').html({$loadingHtml});
        }");
    }

    protected function renderBodyBegin()
    {
        return Html::beginTag('div', ['class' => 'modal-body']) . $this->getLoadHtml();
    }

    protected function getLoadHtml()
    {
        return '
        <div class="progress">
            <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                <span class="sr-only">' . Yii::t('app', 'loading') . '...</span>
            </div>
        </div>
        ';
    }
}