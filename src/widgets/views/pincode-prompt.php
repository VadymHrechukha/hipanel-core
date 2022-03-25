<?php

/**
 * @var \yii\web\View
 */
use yii\helpers\Html;

?>

<div class="modal fade pincode-modal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= Yii::t('hipanel', 'Enter pincode') ?></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <?php if (!$this->context->isPINFailed()) : ?>
                        <?= Html::passwordInput('pincode-modal-input', null, [
                            'class' => 'form-control pincode-input',
                            'placeholder' => '****',
                            'autocomplete' => 'new-password',
                        ]) ?>
                        <p class="help-block"></p>
                    <?php else : ?>
                        <p class="bg-danger" style="padding: 1rem">
                            <?= Yii::t('hipanel', 'You have not set a PIN code! Contact our support by e-mail {email}.', [
                                'email' => Html::mailto(Yii::$app->params['supportEmail'], Yii::$app->params['supportEmail']),
                            ]) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?= Yii::t('hipanel', 'Close'); ?>
                </button>

                <?php if (!$this->context->isPINFailed()) : ?>
                    <?= Html::button(Yii::t('hipanel', 'Send'), array_filter([
                        'class' => 'btn btn-primary pincode-submit',
                        'data-toggle' => 'button',
                        'data-loading-text' => $widget->loadingText ?? null,
                    ])) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

