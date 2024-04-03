<?php
/**
 * HiPanel core package
 *
 * @link      https://hipanel.com/
 * @package   hipanel-core
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2014-2019, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\assets;

use yii\web\AssetBundle;

class LessSpaceAsset extends AssetBundle
{
    public $sourcePath = '@bower/less-space/dist';
    public $css = ['less-space.min.css'];
}
